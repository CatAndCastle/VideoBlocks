<?php

class Phantom{

	function __construct()
	{
		$this->phantom = PHANTOM_BIN;
		$this->exec = $this->phantom . " " . __DIR__ . "/phantomRender.js";
		// $this->exec = $this->phantom . " " . __DIR__ . "/phantomjs/phantomRender.js " . dirname(__DIR__, 3) . "/.data ";
	}

	function execute($args, $f){
		$ffmpeg = new FFMpeg();	
		$command = $this->exec . $this->argsToString($args) . " | " . $ffmpeg->framesToVideoCMD($f, $args['-s']);
		// echo $command."\n";
		shell_exec($command);
	}

	/**
		render video
	**/
	function renderVideo($storyId, $f){
		$this->execute([
			'-dir' 		=> dirname(__DIR__, 3) . "/.data",
			'-storyId' 	=> $storyId,
			'-s'		=> '1920x1080'
		], $f);
	}

	/**
		render single block
	**/
	function renderBlock($storyId, $assetId, $f){
		$this->execute([
			'-dir' 		=> dirname(__DIR__, 3) . "/.data",
			'-storyId' 	=> $storyId,
			'-assetId' 	=> $assetId,
			'-s'		=> '1280x720'
		], $f);
	}

	function argsToString($arr){
		$str = "";
		foreach ($arr as $key => $v) {
			$str .= " $key $v";
		}
		return $str;
	}




	
}


?>