<?php

require_once dirname(__DIR__, 1).'/.config.php';
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__DIR__, 1).'/VideoBot.php';
require_once __DIR__.'/includes/SQSClient.php';
require_once __DIR__.'/includes/S3Client.php';
require_once __DIR__.'/includes/Mysql.php';

// The worker will execute every X seconds:
$seconds = 2;

// We work out the micro seconds ready to be used by the 'usleep' function.
$micro = $seconds * 1000000;

// init sqs
$sqs = new SQS();
$s3 = new AWSS3();
$mysql = new Mysql();

while(true){
	// fetch storyId from SQS
	$msgs = $sqs->receiveMessages(SQSQueue::Video, 1);
	if(!$msgs['Messages']){
		usleep($micro);
		continue;
	}

	$msg = $msgs['Messages'][0];
	$storyId = $msg['Body'];
echo "got story $storyId\n";
	// remove from queue
	$sqs->deleteMessage(SQSQueue::Video, $msg);

	// render video
	// $mysql->setVideoStatus($storyId, VideoStatus::rendering, $url=null);
	
	//render video
echo "rendering...\n";
	$bot = new VideoBot($storyId);
	$v = $bot->render();

	if(!$v){
		echo "ERROR rendering\n";
		$mysql->setVideoStatus($storyId, VideoStatus::error, $url=null);
		// $sqs->pushToVideoQueue($storyId);
		$bot->cleanup();
		continue;
	}

echo "saved file $v";
echo "uploading to s3...";

	// upload $v to AWS
	$s3->upload(S3Bucket::Video, $v, true);

	// update status
	// $mysql->setVideoStatus($storyId, VideoStatus::done, $webUrl);

echo "cleanup...";
	// $bot->cleanup();

	// // Now before we 'cycle' again, we'll sleep for a bit...
	// usleep($micro);
}


?>