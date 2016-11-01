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

	function __construct($storyId, $streamUrl){
		$this->storyId 		= $storyId;
		$this->dir 			= __DIR__."/_data/".$storyId."/live/";
		$this->streamUrl 	= $streamUrl;

		$this->endTime 		= time() + 60; // run for 1 min
	}

	// stream
	function start(){
		$stream = new FFmpegConcatStream($this->storyId, $this->streamUrl, $this->dir);
		$path = $this->getNextSegment();
		do{
			echo " + add file $path\n";
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
			$mysql = new Mysql();
			$blocks =  $mysql->getStoryBlocks($this->storyId, $this->maxtimestamp, 1);
			if(count($blocks) > 0){
				// advance current idx to new segment
				$this->currentIdx = count($this->blocks);
				// add new blocks
				foreach ($blocks as $key => $obj) {
					$this->blocks[] = $this->getLocalPath($obj['mp4_url']);
					$this->maxtimestamp = $obj['time_added'];
				}
			}else{
				$this->currentIdx = ($this->currentIdx+1) >= count($this->blocks) ? (max(0,count($this->blocks)-20)) : $this->currentIdx+1;
			}
			
		}
		catch (MysqlException $e){
			logme($e->getMessage());
			$this->currentIdx = ($this->currentIdx+1) >= count($this->blocks) ? (max(0,count($this->blocks)-20)) : $this->currentIdx+1;
		}

		// no blocks rendered yet
		if(count($this->blocks) < 1){
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

	// function getNextSegment(){
	// 	if($this->numSegments >= $this->maxSegments){
	// 		return null;
	// 	}
	// 	$videos = $this->getAvailableVideos();
	// 	if(count($videos) < 1){
	// 		return null;
	// 	}
	// 	// print_r($videos);
	// 	// next idx
	// 	$this->currentIdx = ($this->currentIdx+1) >= count($videos) ? 0 : $this->currentIdx+1;
	// 	$path = $videos[$this->currentIdx];
	// 	$this->numSegments ++;

	// 	return $this->dir . $path;


	// }
	// function getAvailableVideos(){
	// 	$arr = [
	// 	'http://s3.amazonaws.com/video.zeroslant.com/jEpUejt3X4Oh/1369297903940285690_4293858.mp4',
	// 	'http://s3.amazonaws.com/video.zeroslant.com/jEpUejt3X4Oh/1369326853957344300_1633884196.mp4',
	// 	'http://s3.amazonaws.com/video.zeroslant.com/jEpUejt3X4Oh/1369336222547462029_334706680.mp4',
	// 	'http://s3.amazonaws.com/video.zeroslant.com/jEpUejt3X4Oh/1369335530789296699_15163357.mp4',
	// 	'http://s3.amazonaws.com/video.zeroslant.com/jEpUejt3X4Oh/1369337474127800406_1633884196.mp4'
	// 	];
	// 	return $arr;
	// 	// $files = scandir($this->dir); //returns files sorted in ascending order
	// 	// $videos = array();
	// 	// foreach ($files as $path) {
	// 	// 	if (pathinfo($path, PATHINFO_EXTENSION) == "mp4") {
	// 	// 		$videos[] = $path;
	// 	// 	}
	// 	// }
	// 	// return $this->clean($videos);
	// }

	function clean($videos){
		if(count($videos) > 20){
			$toDelete = array_splice($this->blocks, 0, count($this->blocks)-20);
			foreach ($toDelete as $path) {
				unlink($path);
			}
			// offset currentIdx
			$this->currentIdx = $this->currentIdx - (count($this->blocks)-20);
		}
		return $this->blocks;
	}


}

?>