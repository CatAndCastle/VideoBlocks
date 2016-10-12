<?php

require_once dirname(__DIR__, 1).'/.config.php';
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__DIR__, 1).'/VideoBot.php';
require_once __DIR__.'/includes/SQSClient.php';
require_once __DIR__.'/includes/S3Client.php';
require_once __DIR__.'/includes/Mysql.php';

// The worker will execute every X seconds:
$seconds = 2;
$micro = $seconds * 1000000;

// init sqs
$sqs = new SQS();
$s3 = new AWSS3();
$mysql = new Mysql();
$sqs->pushToVideoQueue('kdfaT41LVY4l');
while(true){
	// Fetch storyId from SQS
	$msgs = $sqs->receiveMessages(SQSQueue::Video, 1);
	if(!$msgs['Messages']){
		usleep($micro);
		continue;
	}

	$msg = $msgs['Messages'][0];
	$storyId = $msg['Body'];
	echo "rendering story $storyId\n";
	
	// Remove from queue
	$sqs->deleteMessage(SQSQueue::Video, $msg);

	// Set rendering status
	$mysql->setVideoStatus($storyId, VideoStatus::rendering, $url=null);

	// Render video
	$bot = new VideoBot($storyId);
	$v = $bot->render();

	if(!$v){
		echo "ERROR rendering $storyId \n";
		$mysql->setVideoStatus($storyId, VideoStatus::error, $url=null);	
		$bot->cleanup();
		usleep($micro);
		continue;
	}

	// Upload vid + data to AWS
	$uploadedUrl = $s3->upload(S3Bucket::Video, $v, $storyId."/".pathinfo($v)['basename'], true);
	if(file_exists($bot->dir."/data.json")){
		$s3->upload(S3Bucket::Video, $bot->dir."/data.json", $storyId."/data.json", true);
	}

	// Update status
	$mysql->setVideoStatus($storyId, VideoStatus::done, $uploadedUrl);

	// Delete working dir
	$bot->cleanup();

	// Sleep before next cycle
	usleep($micro);
}


?>