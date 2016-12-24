<?php

require_once dirname(__DIR__, 1).'/.config.php';
require_once dirname(__DIR__, 1).'/src/vendor/autoload.php';
require_once dirname(__DIR__, 1).'/src/connections/SQSClient.php';
require_once dirname(__DIR__, 1).'/src/exceptions/SQSException.php';
require_once dirname(__DIR__, 1).'/src/utils/helpers.php';

date_default_timezone_set('UTC');
$sqs = new SQS();

// $storyId = 'NgW0jucEWlM7';
// $trendstart = 1478522100;
// $trendend = time() + 10*60;
// $attributes = [
// 	'trendstart' => ['DataType' => 'Number', 'StringValue' => $trendstart],
// 	'trendend' => ['DataType' => 'Number', 'StringValue' => $trendend],
// ];
// $sqs->sendMessage(SQSQueue::VideoLivestream, $storyId, $attributes);

// exit(0);

while(true){
	try{
		$msgs = $sqs->receiveMessages(SQSQueue::VideoLivestream, 1);
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
	$storyId = $msg['Body'];
	// start live stream 15 mins after story starts trending
	$trendstart = intval($msg['MessageAttributes']['trendstart']['StringValue']) + (15*60);
	$trendend = intval($msg['MessageAttributes']['trendend']['StringValue']);
	logme("processing story ".$storyId);

	if($trendstart < time()-60){
		$trendstart = time() + (5*60);
	}

	// Remove from queue
	$sqs->deleteMessage(SQSQueue::VideoLivestream, $msg);

	$dir = __DIR__."/_data/".$storyId."/live/";
	makedir($dir);
	$log_file = $dir."stream.log";
	$stream_file = __DIR__."/stream.php";
	shell_exec("touch $log_file");

	$cron_str = date("i G j n N", $trendstart) . " root php $stream_file $storyId $trendend 1>$log_file 2>&1 &\n";
	logme("adding $cron_str");
	shell_exec("echo '$cron_str' >> /etc/cron.d/livestream");

	// delete story folder 10 minutes after finished streaming
	$t_delete = $trendend + 600;
	$dir_delete = __DIR__."/_data/".$storyId;
	$cron_str = date("i G j n N", $t_delete) . " root rm -R $dir_delete\n";
	shell_exec("echo '$cron_str' >> /etc/cron.d/livestream");

	usleep(5000000);

}

?>