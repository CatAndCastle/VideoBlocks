<?php

require_once __DIR__.'/ffmpeg/FFmpeg.php';
require_once __DIR__.'/ffmpeg/FFprobe.php';
require_once __DIR__.'/helpers.php';

abstract class AssetError
{
    const NOT_VALID 	= "Asset not valid.";
    const DELETED 		= "Media file does not exist.";

}

class Asset{

	public $error = false;
	public $asset = [];
	
	function __construct($data, $dataDir)
	{
		$this->asset = $data;
		$this->ffmpeg = new FFmpeg();
		// local dir for asset data
		$this->dir = $dataDir; //$dataDir ."/". $this->asset['storyId'] . "/assets/" . $this->asset['id'];
		// process asset
		$this->process();
	}

	function writeJSON(){
		writeToFile($this->dir."/asset.json",  $this->asset);
	}

	function process(){

		// clean caption
		$this->asset['text'] = $this->cleanText($this->asset['text']);

		if(!$this->isValid()){ return; }

		if($this->asset['type'] == 'video')
		{
			$id = $this->asset['id'];
			$url = $this->asset['videos']['standard_resolution']['url'];
			
			// get file info
			$ffprobe = new FFprobe($url);
			$info = $ffprobe->getInfo();
			
			$this->asset['duration'] = $info->video->duration;
			$this->asset['frame_rate'] = $info->video->frame_rate;
			$this->asset['n_frames'] = min($info->video->n_frames, floor($info->video->frame_rate * $this->ffmpeg->max_vid_t));
			$this->asset['has_audio'] = $info->audio != null;
			
			//save frames
			$this->ffmpeg->splitIntoFrames($url, $this->dir);
			$this->asset['dir'] = '.data/'.$this->asset['storyId'].'/assets/'.$id."/";
		}

		//save audio
		if($this->asset['type'] == 'video' && $this->asset['has_audio']){
			$url = $this->asset['videos']['standard_resolution']['url'];
			$this->audio_file = $this->dir."/audio.aac";
			$this->ffmpeg->extractAudio($url, $this->audio_file);
		}
		else{
			//save silent audio
			$this->asset['has_audio'] = false;
			$this->audio_file = null;
			// $this->audio_file = $this->dir."/audio.aac";
			// $this->ffmpeg->silentAudio(20, $this->audio_file);
		}
	
	}

	function cleanText($text){
		$text = $this->removeEmojis($text);
		$text = $this->cleanHashtags($text);

		return $text;
	}

	function cleanHashtags($text){
		$arr = explode(".", $text);
		$last = count($arr)-1;
		if($last > 0){
			$arr[$last] = preg_replace("/ #\p{L}+\b(?!\s+\p{L})/u", '', $arr[$last]);
			$text= implode(".", $arr);
		}
		return $text;
	}

	function removeEmojis($text){
		$text = preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
		// some emojis are passed in as ??
		return preg_replace('/(\?+)(\s+)/', '', $text);
	}

	function isValid(){
		// must be image or video
		if($this->asset['type'] != 'video' && $this->asset['type'] != 'image'){
			// logme("error 1");
			$this->error = AssetError::NOT_VALID;
			return false;
		}

		// check media file exists
		$fileExists = true;
		if($this->asset['type'] == 'video'){
			$fileExists = checkRemoteFile($this->asset['videos']['standard_resolution']['url']);
		}
		else if($this->asset['type'] == 'image'){
			$fileExists = checkRemoteFile($this->asset['images']['standard_resolution']['url']);
		}
		if(!$fileExists){
			$this->error = AssetError::DELETED;
			return false;
		}

		return true;

		// // Require a caption
		// if( empty($this->asset['text']) ){
		// 	// logme("error 2");
		// 	$this->error = AssetError::NOT_VALID;
		// 	return false;
		// }
		// // Require text, not just tags
		// if(array_key_exists('tags', $this->asset)){
		// 	$a = explode(" ", $this->asset['text']);
		// 	if(count($a) <= count($this->asset['tags'])){
		// 		// logme("error 3");
		// 		$this->error = AssetError::NOT_VALID;
		// 		return false;
		// 	}
		// }
		// Require keywords
		// if(count($this->story['keywords']) > 3){
		// 	foreach ($this->story['keywords'] as $kw) {
		// 		if (strpos(strtolower($this->asset['text']), strtolower($kw)) !== false) {
		// 		    return true;
		// 		} 
		// 	}
		// 	// logme("error 4");
		// 	$this->error = AssetError::NOT_VALID;
		// 	return false;
		// }else{
		// 	return true;
		// }
		
		// $this->error = AssetError::NOT_VALID;
		// return false;

	}

	function getTags(){
		if(array_key_exists('tags', $this->asset)){
			return $this->asset['tags'];
		}
		return [];
	}

	function getTimestamp(){
		return $this->asset['created_time'];
	}
}

?>