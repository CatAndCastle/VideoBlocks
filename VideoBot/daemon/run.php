<?php

require_once dirname(__DIR__, 1).'/.config.php';
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__DIR__, 1).'/VideoBot.php';
require_once __DIR__.'/includes/SQSClient.php';
require_once __DIR__.'/includes/S3Client.php';
require_once __DIR__.'/includes/Mysql.php';
require_once dirname(__DIR__, 1).'/exceptions/PhantomException.php';
require_once dirname(__DIR__, 1).'/exceptions/MysqlException.php';

function logme($txt){
	echo "[" . date("Y-m-d H:i:s") ."] " . $txt ."\n";
}

function setVideoStatus($storyId, $status, $url, $i){
	// return true;
	try{
		$mysql = new Mysql();
		$mysql->setVideoStatus($storyId, $status, $url);
		$mysql->close();
		return true;
	}
	catch (MysqlException $e){
		logme($e->getMessage());

		if($i < 5){
			usleep(5000000);
			setVideoStatus($storyId, $status, $url, $i++);
		}else{
			return false;
		}
	}
}

// The worker will execute every X seconds:
$seconds = 5;
$micro = $seconds * 1000000;

// init sqs
$sqs = new SQS();
$s3 = new AWSS3();
// $sqs->pushToVideoQueue("mKUG72wO58Ji");
while(true){
	// Fetch storyId from SQS
	$msgs = $sqs->receiveMessages(SQSQueue::Video, 1);
	if(!$msgs['Messages']){
		usleep($micro);
		continue;
	}

	$msg = $msgs['Messages'][0];
	$storyId = $msg['Body'];

	logme("rendering story $storyId");
	$time_start = microtime(true);
	
	// Remove from queue
	$sqs->deleteMessage(SQSQueue::Video, $msg);

	// Set rendering status
	if(!setVideoStatus($storyId, VideoStatus::RENDERING, null, 0)){	
		$sqs->pushToVideoQueue($storyId);
		usleep($micro);
		continue;
	}

	// Render video
	$bot = new VideoBot($storyId);
	$res = $bot->render();
	// - handle errors
	if($res['status']=='error'){
		$e = $res['error'];
		if($e == VideoError::RENDER_TIMEOUT_ERROR){
			// page hung up -> try again
			setVideoStatus($storyId, VideoStatus::QUEUE, null, 0);
			$bot->cleanup();
			$sqs->pushToVideoQueue($storyId);
		}else{
			setVideoStatus($storyId, VideoStatus::ERROR, null, 0);	
			$bot->cleanup();
		}

		usleep($micro);
		continue;
	}
	$v = $res['video'];

	// Upload vid + data to AWS
	$uploadedUrl = $s3->upload(S3Bucket::Video, $v, $storyId."/".pathinfo($v)['basename'], true);
	if(file_exists($bot->dir."/data.json")){
		$s3->upload(S3Bucket::Video, $bot->dir."/data.json", $storyId."/data.json", true);
	}
	// $uploadedUrl = $s3->upload(S3Bucket::Video, $v, 'test.mp4', true);
	// if(file_exists($bot->dir."/data.json")){
	// 	$s3->upload(S3Bucket::Video, $bot->dir."/data.json", "test.json", true);
	// }

	// Update status
	setVideoStatus($storyId, VideoStatus::DONE, $uploadedUrl, 0);	
	
	// Delete working dir
	$bot->cleanup();

	// logme time
	$time_end = microtime(true);
	$time = ceil($time_end - $time_start);
	logme("$storyId t = $time");

	// Sleep before next cycle
	usleep($micro);
}


?>