<?php
// input storyId - loop through files in livestream folder
// 
require_once __DIR__.'/LiveStream.php';
require_once __DIR__.'/StoryInfo.php';
require_once dirname(__DIR__, 1).'/src/connections/FacebookManager.php';


$storyId = $argv[1];
$endtime = $argv[2];
// $endtime = time() + 2*3600;
// $stream_url = "rtmp://rtmp-api.facebook.com:80/rtmp/2115359908689827?ds=1&s_l=1&a=AaahP-3XVumEJyz8"; //$argv[2];

$story = new StoryInfo($storyId);

$title 			= $story->title();
$description 	= $story->description();
$content_tags 	= $story->content_tags();


// Start Facebook Live Post
$fb = new FacebookManager();
$res = $fb->live_video_start($story->title(), $story->description(), $story->content_tags());
if($res == null){ exit(0);}

$video_id = $res['id'];
$stream_url = $res['stream_url'];
echo "VIDEO ID: $video_id\n";
echo "STREAM URL: $stream_url\n";

// $preview = $fb->node($video_id, ['fields'=>'preview_url']);
// $preview_url = $preview['preview_url'];
// echo "\nPREVIEW URL = $preview_url\n\n";
// exit(0);

// Stream
$stream = new LiveStream($storyId, $stream_url, $endtime);
$stream->start();

?>