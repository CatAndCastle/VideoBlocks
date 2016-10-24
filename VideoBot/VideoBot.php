<?php

require_once '.config.php';
require_once 'ffmpeg/FFmpeg.php';
require_once 'ffmpeg/FFprobe.php';
require_once 'helpers.php';
require_once 'Phantom.php';
require_once 'Story.php';

abstract class VideoStatus
{
    const QUEUE = 0;
    const RENDERING = 1;
    const DONE = 2;
    const ERROR = 3;
}

abstract class VideoError
{
    const STORY_ERROR = 0;
    const RENDER_ERROR = 1;
    const RENDER_TIMEOUT_ERROR = 2;

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
		try { $this->prepare();
		}catch (Exception $e) {
		    logme('CAUGHT EXCEPTION: '.  $e->getMessage());
		    return ['status'=>'error', 'error'=>VideoError::STORY_ERROR, 'video'=>null];
		}
// exit(0);
		// render
		try { 
			$this->makeVideo();
		}catch (PhantomException $e){
			logme('CAUGHT EXCEPTION: '.  $e->getMessage());
			if ($e->getCode() == PhantomException::TIMEOUT){
				return ['status'=>'error', 'error'=>VideoError::RENDER_TIMEOUT_ERROR, 'video'=>null];
			}
			else if ($e->getCode() == PhantomException::PAGE_ERROR){
				return ['status'=>'error', 'error'=>VideoError::RENDER_ERROR, 'video'=>null];
			}
			else if ($e->getCode() == PhantomException::RENDER_ERROR){
				return ['status'=>'error', 'error'=>VideoError::RENDER_ERROR, 'video'=>null];
			}
		}

		if(file_exists($this->finalFile)){
			$this->saveThumbnail();
			return ['status'=>'success', 'video'=>$this->finalFile, 'thumb'=>$this->thumb];
		}
		else{
			return ['status'=>'error', 'error'=>VideoError::RENDER_ERROR, 'video'=>null];
		}
	}

	function prepare(){
		$s = new Story($this->storyId, dirname(__DIR__, 1)."/.data/");
		if($s->error !== false){
			throw new Exception($s->error);
		}
		$s->writeJSON($this->dir."/story.json");
		$s->writeHashtags($this->dir."/data.json");
		$this->audio = $s->saveMainAudio();
	}

	function makeVideo(){
		$phantom = new Phantom();
		$this->video = $this->dir."/video.mp4";
		$phantom->renderVideo($this->storyId, $this->video);

		// check if phantom printed any error logs
		if(file_exists($this->dir."/phantomjs-timeout.log")){
			throw new PhantomException('Phantomjs timed out loading page resources', PhantomException::TIMEOUT);
		}
		else if(file_exists($this->dir."/phantomjs-error.log")){
			throw new PhantomException('Phantomjs: there was an error loading the video page', PhantomException::PAGE_ERROR);
		}
		else if(!file_exists($this->video)){
			throw new PhantomException('Phantomjs: there was an error rendering the video', PhantomException::RENDER_ERROR);
		}

		$ffmpeg = new FFmpeg();
		$this->finalFile = $this->dir."/".$this->finalName();
		$ffmpeg->combineAV($this->video, $this->audio, $this->finalFile, $this->dir);
	}

	function saveThumbnail(){
		$ffmpeg = new FFmpeg();
		$this->thumb = $this->dir."/thumb.jpg";
		$ffmpeg->saveThumbnail($this->finalFile, $this->thumb);
	}

	function finalName(){
		// get story info
		$info = json_decode(file_get_contents($this->dir."/story.json"), true);
		$name =$info['name'];

		return preg_replace("/[\s&-+.^:,#*?>%\\/]/","",$name)."_1920x1080_blocks.mp4";
	}

	function cleanup(){
		rrmdir($this->dir);
	}
}

?>