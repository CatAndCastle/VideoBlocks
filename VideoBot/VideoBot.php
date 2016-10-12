<?php

require_once '.config.php';
require_once 'ffmpeg/FFmpeg.php';
require_once 'ffmpeg/FFprobe.php';
require_once 'helpers.php';
require_once 'Phantom.php';
require_once 'Story.php';

abstract class VideoStatus
{
    const queue = 0;
    const rendering = 1;
    const done = 2;
    const error = 3;
}

class VideoBot{

	public $storyId;
	public $audio;
	public $video;
	public $dir;

	function __construct($storyId){
		$this->storyId 	= $storyId;
		$this->dir 		= dirname(__DIR__, 1)."/.data/".$storyId;
		$this->audio 	= $this->dir."/audio.aac";
		$this->videos 	= $this->dir."/video.mp4";
	}

	function render(){
		// prepare
		try {
			$this->prepare();
		}catch (Exception $e) {
		    echo 'Caught exception: ',  $e->getMessage(), "\n";
		    return null;
		}

		// render
		$this->makeVideo();

		if(file_exists($this->dir."/".$this->finalName())){
			return $this->dir."/".$this->finalName();
		}
		else{
			return null;
		}
	}

	function prepare(){
		$s = new Story($this->storyId, dirname(__DIR__, 1)."/.data/");
		if($s->error){
			throw new Exception('Story is empty.');
		}
		$s->processAssets();
		$s->writeJSON($this->dir."/story.json");
		$s->writeHashtags($this->dir."/data.json");
		$this->audio = $s->saveMainAudio();
	}

	function makeVideo(){
		$phantom = new Phantom();
		$this->video = $this->dir."/video.mp4";
		$phantom->renderVideo($this->storyId, $this->video);

		$ffmpeg = new FFmpeg();
		$ffmpeg->combineAV($this->video, $this->audio, $this->dir."/".$this->finalName(), $this->dir);
	}

	function finalName(){
		// get story info
		$info = json_decode(file_get_contents($this->dir."/story.json"), true);
		$name =$info['name'];

		return preg_replace("/[\s&-+.^:,#*?>%\\/]/","",$name)."_1920x1080_blocks.mp4";
	}

	function cleanup(){
		// rrmdir($this->dir);
	}
}

?>