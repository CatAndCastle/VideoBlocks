<?php

require_once dirname(__DIR__, 1).'/src/utils/ffmpeg/FFmpegConcatStream.php';
require_once dirname(__DIR__, 1).'/src/connections/Mysql.php';

class LiveStream{

	public $currentIdx 		= -1;
	public $numSegments 	= 0;
	public $maxSegments 	= 5;
	public $endTime			= 0;
	public $maxtimestamp	= 0;

	public $blocks = [];

	function __construct($storyId, $streamUrl, $endtime=0){
		$this->storyId 		= $storyId;
		$this->dir 			= __DIR__."/_data/".$storyId."/live/";
		$this->streamUrl 	= $streamUrl;

		$this->endTime 		= $endtime; // run for 1 min
	}

	// stream
	function start(){
		$stream = new FFmpegConcatStream($this->storyId, $this->streamUrl, $this->dir);
		$path = $this->getNextSegment();
		do{
			echo " + queue file $path\n";
			if($path != null){
				$stream->addFile($path);
			}
			$path = $this->getNextSegment();
		}while(time() <= $this->endTime);
		// while($path != null);
		// continue while timestamp < X

		$stream->stop();
		echo "\nDONE WITH STREAM\n";

	}

	function getNextSegment(){
		try{
			if($this->currentIdx > count($this->blocks)-2){
				echo "-> fetch more blocks from mysql...";
				$mysql = new Mysql();
				$blocks =  $mysql->getStoryBlocks($this->storyId, $this->maxtimestamp, 5);
				echo "...found ".count($blocks)."\n";
				if(count($blocks) > 0){
					// advance current idx to new segment
					// $this->currentIdx = count($this->blocks);
					// add new blocks
					foreach ($blocks as $key => $obj) {
						$this->blocks[] = $this->getLocalPath($obj['mp4_url']);
						$this->maxtimestamp = $obj['time_added'];
					}
					// delete old blocks if accumulates > 20 video segments - to free space on disk
					$this->clean();
				}
			}
			// else{
			$this->currentIdx = ($this->currentIdx+1) >= count($this->blocks) ? (max(0,count($this->blocks)-20)) : $this->currentIdx+1;
			// }
			
		}
		catch (MysqlException $e){
			logme($e->getMessage());
			$this->currentIdx = ($this->currentIdx+1) >= count($this->blocks) ? (max(0,count($this->blocks)-20)) : $this->currentIdx+1;
		}

		// no blocks rendered yet
		if(count($this->blocks) < 1 || $this->currentIdx < 0){
			return null;
		}

		return $this->blocks[$this->currentIdx];
	}

	function getLocalPath($remote){
		// download to local
		$local = pathinfo($remote)['basename'];
		file_put_contents($this->dir . $local, file_get_contents($remote));
		return $local;


	}

	function clean(){
		if(count($this->blocks) > 20){
			$numDelete = count($this->blocks)-20;
			$toDelete = array_splice($this->blocks, 0, $numDelete);
			foreach ($toDelete as $path) {
				unlink($this->dir.$path);
			}
			// offset currentIdx
			$this->currentIdx = $this->currentIdx - $numDelete;
			echo " -> cleaned up!\n";
		}

		return $this->blocks;
	}


}

?>