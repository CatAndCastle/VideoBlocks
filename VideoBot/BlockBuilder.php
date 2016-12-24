<?php
/*
BlockBuilder Listens to the video_assets_ SQS queue
- renders mp4 video, aac audio for each available asses
- uploads files to S3 bucket
- saves urls in MySql table
*/
require_once __DIR__.'/.config.php';
require_once __DIR__.'/src/vendor/autoload.php';
require_once __DIR__.'/src/utils/Block.php';
require_once __DIR__.'/src/utils/helpers.php';
require_once __DIR__.'/src/phantomjs/Phantom.php';
require_once __DIR__.'/src/connections/SQSClient.php';
require_once __DIR__.'/src/connections/S3Client.php';
require_once __DIR__.'/src/connections/Mysql.php';
require_once __DIR__.'/src/exceptions/PhantomException.php';
require_once __DIR__.'/src/exceptions/MysqlException.php';
require_once __DIR__.'/src/exceptions/SQSException.php';
require_once __DIR__.'/src/errors/VideoError.php';

function saveRenderedBlock($block){
	try{
		$mysql = new Mysql();
		$mysql->saveRenderedBlock($block->storyId, $block->assetId, $block->video, $block->blockId, $block->asset->getTags(), $block->asset->getTimestamp());
		return true;
	}
	catch (MysqlException $e){
		logme($e->getMessage());
		return false;
	}
}

// aws
$sqs = new SQS();
$s3 = new AWSS3();

// #################################################
//DEV
// $story = json_decode(file_get_contents("http://www.zeroslant.com/api/v0.2/story/nPzMZGazRieb"), true);
// $storyId = $story['storyId'];
// foreach ($story['body'] as $asset) {
// 	// echo $asset['type'] . "\n";
// 	if($asset['type'] != 'image' && $asset['type'] != 'video'){ continue; }
// 	$assetId = $asset['id'];
// 	$data = json_encode($asset);

// 	echo "$assetId\n";

// 	$attributes = [
// 		'data' => ['DataType' => 'String', 'StringValue' => $data],
// 		'storyId' => ['DataType' => 'String', 'StringValue' => $storyId],
// 	];
// 	$sqs->sendMessage(SQSQueue::VideoBlocks, $assetId, $attributes);
// }

// exit(0);

// $mysql = new Mysql();
// $storyId = 'QrMUEk8clAGJ';
// $as = ['1373537090952721434_2079237282', '1373536849503429903_31639893', '1373536606845603805_254505916', '1373536274169753979_1447724981', '1373536254860064329_31273743', '793240583238279169'];
// foreach ($as as $assetId) {
	// $assetId = '1369342022783214485_199625402';
	// $asset = $mysql->getAsset($assetId);
	
	// $attributes = [
	// 	'data' => ['DataType' => 'String', 'StringValue' => $asset['data']],
	// 	'storyId' => ['DataType' => 'String', 'StringValue' => $storyId],
	// 	'trendend' => ['DataType' => 'Number', 'StringValue' => time() + 3600]
	// ];
	// $sqs->sendMessage(SQSQueue::VideoBlocks, $assetId, $attributes);
// }
// $mysql->close();
// #################################################


while(true){
	// Fetch storyId from SQS
	try{
		$msgs = $sqs->receiveMessages(SQSQueue::VideoBlocks, 1);
	}catch (SQSException $e){
		logme($e->getMessage());
		// sleep 10 secs
		usleep(10000000);
		continue;
	}
	
	if(!$msgs['Messages']){
		usleep(5000000);
		continue;
	}

	$msg = $msgs['Messages'][0];
	$assetId = $msg['Body'];

	//read data, storyId attributes
	$data = json_decode($msg['MessageAttributes']['data']['StringValue'], true);
	$data['storyId'] = $msg['MessageAttributes']['storyId']['StringValue'];
	$trendend = intval($msg['MessageAttributes']['trendend']['StringValue']);

	// Remove from queue
	$sqs->deleteMessage(SQSQueue::VideoBlocks, $msg);

	// check if story is still live?
	if($trendend < time()){
		usleep(500000);
		continue;
	}


	logme("rendering asset $assetId");
	$time_start = microtime(true);

	// Render asset block
	$block = new Block($data);
	$res = $block->render();

	// print_r($res);

	// - handle errors
	if($res['status']=='error'){
		$e = $res['error'];
		if($e == VideoError::RENDER_TIMEOUT_ERROR){
			// page hung up -> try again
			$block->cleanup();
			//push back to queue
			$sqs->pushToVideoQueue(SQSQueue::VideoBlocks, $assetId, $msg['MessageAttributes']);
		}else{
			// whatever, moving on
			$block->cleanup();
		}

		usleep(5000000);
		continue;
	}
	

	// Upload vid + to AWS
	$uploadedUrl = $s3->upload(S3Bucket::Video, $block->video, $block->storyId."/".$block->assetId.".mp4", true);
	echo $uploadedUrl."\n";

	// Save info in mysql
	$block->video = $uploadedUrl;
	saveRenderedBlock($block);

	// Delete working dir
	$block->cleanup();

	// logme time
	$time_end = microtime(true);
	$time = ceil($time_end - $time_start);
	logme("$assetId t = $time");
	
// exit(0);
	// coffee break
	usleep(5000000);
}

?>