<?php

require_once dirname(__DIR__, 3).'/.config.php';
require_once dirname(__DIR__, 1).'/helpers.php';

class FFmpegConcatStream{

	public $concat_file;
	
	public $prev;
	public $current;
	public $next;

	public $streaming = false;

	function __construct($storyId, $streamUrl, $dir){
		$this->ffmpeg 		= FFMPEG_BIN;// . " -loglevel warning";
		$this->storyId 		= $storyId;
		$this->dir 			= $dir;
		
		$this->log 			= $this->dir . "stream.log";
		$this->error_log 	= $this->dir . "stream_error.log";

		makedir($this->dir);

		$this->concat_file	= $this->dir . "concat.txt.XXXXXXXX";
		// $this->concat_file	= "concat.txt.XXXXXXXX";
		$this->streamUrl 	= $streamUrl;

		$this->init();
	}

	function init(){
		// first file
		$this->current = $this->dir . "concat.txt"; // $this->newConcat();
		$this->next = $this->newConcat();

		$this->stream();

		$str = "ffconcat version 1.0\nfile '".pathinfo($this->next)['basename']."'";
		// file_put_contents($this->current, $str);
		// $str = "ffconcat version 1.0\nfile '$f'\nfile '".$this->next."'";
		$this->write($this->current, $str);

		
	}
	function addFile($f){
		// $this->prev = $this->current;
		$this->delete($this->current);

		$this->current = $this->next;
		$this->next = $this->newConcat();

		$str = "ffconcat version 1.0\nfile '$f'\nfile '".pathinfo($this->next)['basename']."'";
		$this->write($this->current, $str);

		// if(!$this->streaming){
		// 	$this->stream();
		// }
		
	}
	function stop(){
		$str = "ffconcat version 1.0";
		$this->write($this->next, $str);

		// $this->delete($this->current);
		// $this->delete($this->next);

	}

	function newConcat(){
		$f = exec("mktemp -u " . $this->concat_file);
		exec("mkfifo $f");
		// exec("chmod a+rw $f");
		echo "new concat: $f\n";
		return $f;
	}
	function write($f, $data){

		// echo "WRITE $f\n$data\n";
		exec(">&2 echo '$data' > $f");
		
	}
	function delete($f){
		exec("rm $f");
	}

	function stream(){
		$this->streaming = true;
		// $command = $this->ffmpeg . " -y -i $in -f flv $out";
		exec("mkfifo ".$this->current);
		$command = $this->ffmpeg . " -y -re -f concat -safe 0 -i ".$this->current." -c:a copy -c:v copy -f flv \"".$this->streamUrl."\" >".$this->log." 2>".$this->error_log." &";
		echo " -> Starting ffmpeg...\n";
		echo " -> $command\n";
		shell_exec($command);
		echo " -> STARTED ffmpeg...\n";
	}
}

?>