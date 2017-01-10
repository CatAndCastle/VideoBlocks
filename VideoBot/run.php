<?php

require_once __DIR__.'/.config.php';
require_once __DIR__.'/src/vendor/autoload.php';
require_once __DIR__.'/src/VideoBot.php';
require_once __DIR__.'/src/connections/SQSClient.php';
require_once __DIR__.'/src/connections/S3Client.php';
require_once __DIR__.'/src/connections/Mysql.php';
require_once __DIR__.'/src/exceptions/PhantomException.php';
require_once __DIR__.'/src/exceptions/MysqlException.php';
require_once __DIR__.'/src/exceptions/SQSException.php';
require_once __DIR__.'/src/errors/VideoError.php';
require_once __DIR__.'/src/utils/Http.php';


function setVideoStatus($storyId, $status, $url, $i){
	// return true;
	try{
		$mysql = new Mysql();
		$mysql->setVideoStatus($storyId, $status, $url);
		return true;
	}
	catch (MysqlException $e){
		logme($e->getMessage());
		return false;
	}
}

function setVideoDistributed($storyId, $status){
	// return true;
	try{
		$mysql = new Mysql();
		$mysql->setVideoDistributed($storyId, $status);
		return true;
	}
	catch (MysqlException $e){
		logme($e->getMessage());
		return false;
	}
}

// The worker will execute every X seconds:
$seconds = 5;
$micro = $seconds * 1000000;

// init sqs
$sqs = new SQS();
$s3 = new AWSS3();
// $sqs->pushToVideoQueue("U5i0Zz0V6580");
while(true){
	// Fetch storyId from SQS
	try{
		$msgs = $sqs->receiveMessages(SQSQueue::Video, 1);
	}catch (SQSException $e){
		logme($e->getMessage());
		// sleep 10 secs
		usleep(10000000);
		continue;
	}
	
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
	$thumb = $res['thumb'];

	// // Upload vid + data to AWS
	$uploadedUrl = $s3->upload(S3Bucket::Video, $v, $storyId."/".pathinfo($v)['basename'], true);
	if(file_exists($thumb)){
		$s3->upload(S3Bucket::Video, $thumb, $storyId."/poster.jpg", true);
	}
	if(file_exists($bot->dir."/data.json")){
		$s3->upload(S3Bucket::Video, $bot->dir."/data.json", $storyId."/data.json", true);
	}

	//DEV
	// $uploadedUrl = $s3->upload(S3Bucket::Video, $v, $storyId."/".pathinfo($v)['filename']."_es.mp4", true);
	// echo $uploadedUrl."\n";
	// $uploadedUrl = $s3->upload(S3Bucket::Video, $v, "test/".$storyId."/".pathinfo($v)['basename'], true);
	// if(file_exists($bot->dir."/data.json")){
	// 	$s3->upload(S3Bucket::Video, $bot->dir."/data.json", "test/".$storyId."/data.json", true);
	// }

	// Update status
	setVideoStatus($storyId, VideoStatus::DONE, $uploadedUrl, 0);	
	
	// Delete working dir
	$bot->cleanup();

	// logme time
	$time_end = microtime(true);
	$time = ceil($time_end - $time_start);
	logme("$storyId t = $time");
	// logme($uploadedUrl);

	// post to social platforms
	// POST
	$http = new Http();
	$res = $http -> post('http://zeroslant.com/api/v0.2/zerobot/distributeOnSocials', ['storyId' => $storyId]);

	// set as distributed
    setVideoDistributed($storyId, 1);

	// Sleep before next cycle
	usleep($micro);
}


?>