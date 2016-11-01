<?php

require_once __DIR__.'/Asset.php';
require_once dirname(__DIR__, 1).'/errors/VideoError.php';
require_once __DIR__.'/helpers.php';
require_once dirname(__DIR__, 1).'/phantomjs/Phantom.php';

class Block{

	public $blockId = "";
	public $storyId = "";
	public $assetId = "";
	public $video = "";
	public $tags = "";

	function __construct($data)
	{
		$this->data 	= $data;
		$this->dir 		= dirname(__DIR__, 3)."/.data/" . $this->data['storyId'] . "/assets/" . $this->data['id'];
		makedir($this->dir);

		$this->storyId = $data['storyId'];
		$this->assetId = $data['id'];
	}

	function render()
	{
		// prepare
		try { 
			$this->prepare();
		}catch (Exception $e) {
		    logme('CAUGHT EXCEPTION: '.  $e->getMessage());
		    return ['status'=>'error', 'error'=>VideoError::DATA_ERROR, 'video'=>null];
		}
		
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

		return ['status'=>'success'];
	}

	function prepare(){
		$this->asset 	= new Asset($this->data, $this->dir );
		if($this->asset->error !== false){
			throw new Exception($this->asset->error);
		}
		$this->asset->writeJSON();
		$this->audio = $this->asset->audio_file;
	}

	function makeVideo(){
		$phantom = new Phantom();
		$this->video = $this->dir."/video.mp4";
		$phantom->renderBlock($this->storyId, $this->assetId, $this->video);

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

		// get block id
		$d = json_decode(file_get_contents($this->dir."/phantomjs-data.log"), true);
		$this->blockId = $d['blocks'][0];

		// add AUDIOOOooooo
		$ffmpeg = new FFmpeg();
		$this->video = $ffmpeg->fillAudio($this->video, $this->audio, $this->dir."/video_audio.mp4");
	}
	
	function cleanup(){
		rrmdir($this->dir);
	}
}

?>