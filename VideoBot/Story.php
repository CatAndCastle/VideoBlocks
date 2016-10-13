<?php

class Story{

	public $_MAX_V = 7;
	public $_MAX_A = 50;
	public $error = false;

	public $hashtags = [];

	function __construct($storyId, $dataDir)
	{
		
		// local dir for story
		makedir($dataDir.$storyId, 0777);
		makedir($dataDir.$storyId.'/videos/', 0777);

		$this->dir = $dataDir.$storyId;
		$this->ffmpeg = new FFmpeg();

		$this->storyId = $storyId;
		$this->load();
	}

	function load(){
		$data = file_get_contents(API_URL . 'story/' . $this->storyId . '?q=keywords,hashtags');
		$this->story = json_decode($data, true);

		if(count($this->story['body'])<2){
			$this->error = true;
		} 
	}

	function writeJSON($f){
		writeToFile($f,  $this->story);
	}

	function writeHashtags($f){
		$write = [
			"assets" => [],
			"hashtags" => $this->story['hashtags'],
			"users" => [],
		];
		writeToFile($f,  $write);
	}

	function saveMainAudio(){
		$asset = $this->getAsset(0);
		if($asset['type']=='video' && $asset['has_audio']){
			$url = $asset['videos']['standard_resolution']['url'];
			$a_file = $this->dir."/audio.aac";
			$this->ffmpeg->extractAudio($url, $a_file);
			return $a_file;
		}
		return null;
	}

	function getAsset($idx){
		return $this->story['body'][$idx];
	}


	function processAssets(){

		$videos = [];
		$images = [];
		$numvids = 0;
		foreach ($this->story['body'] as $idx => $asset) {
			if($idx > $this->_MAX_A || $numvids>$this->_MAX_V){break;}
			
			if(!$this->isValid($asset)){
				continue;
			}

			// clean caption
			$asset['text'] = $this->cleanText($asset['text']);

			if($asset['type'] == 'video')
			{
				$id = $asset['id'];
				$url = $asset['videos']['standard_resolution']['url'];
				$fileExists = checkRemoteFile($url);
				// echo "video $url $fileExists\n";
				
				if($fileExists){
					// get file info
					$ffprobe = new FFprobe($url);
					$info = $ffprobe->getInfo();
					
					$asset['duration'] = $info->video->duration;
					$asset['frame_rate'] = $info->video->frame_rate;
					$asset['n_frames'] = min($info->video->n_frames, floor($info->video->frame_rate * $this->ffmpeg->max_vid_t));
					$asset['has_audio'] = $info->audio != null;
					
					$dir = $this->dir.'/videos/'.$id;
					$this->ffmpeg->splitIntoFrames($url, $dir);
					$asset['dir'] = '.data/'.$this->storyId.'/videos/'.$id."/";

					if(count($videos)>0 && $info->video->duration > $videos[0]['duration'] && $asset['has_audio']){
						array_unshift($videos, $asset);
					}else{
						array_push($videos, $asset);
					}

					$numvids ++;
				}
				

			}
			
			else if($asset['type'] == 'image')
			{
				$id = $asset['id'];
				$url = $asset['images']['standard_resolution']['url'];
				$fileExists = checkRemoteFile($url);
				if($fileExists){
					array_push($images, $asset);
				}
			}

			// count hashtags
			if(count($this->story['hashtags']) == 0){
				$this->countHashtags($asset);
			}
		}


		$this->story['body'] = array_merge($videos, $images);
		// count hashtags
		if(count($this->story['hashtags']) == 0){
			arsort($this->hashtags);
			$this->story['hashtags'] = array_keys(array_slice($this->hashtags, 0, 10));
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

	function isValid($asset){
		
		// must be image or video
		if($asset['type'] != 'video' && $asset['type'] != 'image'){
			return false;
		}

		// Require a caption
		if( empty($asset['text']) ){
			return false;
		}

		// Require text, not just tags
		$a = explode(" ", $asset['text']);
		if(count($a) <= count($asset['tags'])){
			return false;
		}

		// Require keywords
		if(count($this->story['keywords']) > 0){
			foreach ($this->story['keywords'] as $kw) {
				if (strpos(strtolower($asset['text']), strtolower($kw)) !== false) {
				    return true;
				} 
			}
		}

		return false;

	}

	function countHashtags($asset){
		foreach ($asset['tags'] as $idx => $tag) {
			$tag = "#".$tag;
			if(array_key_exists($tag, $this->hashtags)){
				$this->hashtags[$tag] ++;
			}else{
				$this->hashtags[$tag] = 0;
			}
		}
	}

}

?>