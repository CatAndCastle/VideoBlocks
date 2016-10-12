<?php

class Phantom{

	function __construct()
	{
		$this->phantom = PHANTOM_BIN;
		$this->exec = $this->phantom . " " . __DIR__. "/renderer/phantomRender.js";
	}

	/**
		renderVideo
	**/
	function renderVideo($storyId, $f){
		$ffmpeg = new FFMpeg();	
		$command = $this->exec . " " . $storyId  . " | " . $ffmpeg->framesToVideoCMD($f);
		
		// echo $command."\n";
		shell_exec($command);
	}




	
}


?>