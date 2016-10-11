<?php

	require_once 'VideoBot/.config.php';
	require_once 'VideoBot/ffmpeg/FFmpeg.php';
	require_once 'VideoBot/ffmpeg/FFprobe.php';
	require_once 'VideoBot/helpers.php';
	require_once 'VideoBot/Phantom.php';
	require_once 'VideoBot/Story.php';
	
	$ffmpeg = new FFmpeg();
	$storyId = $argv[1];

	// $s = new Story($storyId, ".data/");
	// $s->processAssets();
	// $s->writeJSON(".data/".$storyId."/story.json");
	// $audio = $s->saveMainAudio();


	// 4) Run phantom
	$phantom = new Phantom();
	$video = ".data/".$storyId ."/video.mp4";
	$phantom->renderVideo($storyId, $video);

	// combine video + audio
	$audio = ".data/".$storyId ."/audio.aac";
	$ffmpeg->combineAV($video, $audio, ".data/".$storyId);

	// 7) send video to AWS

	// 8) save video url in mysql

	//6) delete story folder


?>