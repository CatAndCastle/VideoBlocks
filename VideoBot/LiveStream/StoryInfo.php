<?php

require_once dirname(__DIR__, 1).'/src/connections/FacebookManager.php';
require_once dirname(__DIR__, 1).'/.config.php';

class StoryInfo{

	public $error = false;

	public $hashtags = [];

	function __construct($storyId)
	{
		// local dir for story
		$this->dir = __DIR__."_data/$storyId";
		makedir($this->dir, 0777);

		$this->storyId = $storyId;
		$this->load();
	}

	function load(){
		$data = file_get_contents(API_URL . 'story/' . $this->storyId . '?q=keywords,hashtags,topics');
		$this->story = json_decode($data, true);
	}

	function title(){
		return $this->story['name'];
	}

	function description(){
		$hashtags = array_splice($this->story['keywords'], 0, 10);
		$hashtag_str = "";
		if(count($hashtags) > 0){
			$hashtag_str = "#". join(' #', $hashtags);
		}
		return  "Quick Take: ".$this->story['name']."\n\nSee more: ".$this->story['link']."\n\n".$hashtag_str;
	}

	function location(){
		return $location = $story['location']['name'];
	}

	function content_tags(){
		$fb = new FacebookManager();
		$content_tags = array();
		foreach ($this->story['topics'] as $t) {
		    // get facebook tag
		    $tags = $fb->search(['type'=>'adinterest', 'q'=>$t]);
		    if(array_key_exists('data', $tags) && count($tags['data'])>0 ){
		        // echo $tags['data'][0]['id'] . ' - ' . $tags['data'][0]['name'] . "\n";
		        array_push($content_tags, $tags['data'][0]['id']);
		    }
		}
		return $content_tags;
	}
}

?>