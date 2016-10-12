<?php
use Aws\Sqs\SqsClient;

abstract class SQSQueue
{
    const Video = "https://sqs.us-east-1.amazonaws.com/568183857234/zeroslant_video";
}

class SQS{
	public $client;

	private $REGION = "us-east-1";
	private $VIDEO_QUEUE_URL = "https://sqs.us-east-1.amazonaws.com/568183857234/zeroslant_video";

	function __construct(){
		
	    $this->client = SqsClient::factory(array(
		    'credentials' => array(
		        'key'    => AWS_ACCESS_KEY,
		        'secret' => AWS_SECRET_KEY,
		    ),
		    'region' => 'us-east-1'
		));
	}

	public function receiveMessages($queueUrl, $n){
		return $this->client->receiveMessage(array(
		    // QueueUrl is required
		    'QueueUrl' => $queueUrl,
		    'MaxNumberOfMessages' => $n,
		    'WaitTimeSeconds' => 5,
		));
	}

	public function deleteMessage($queueUrl, $msg){
		$result = $this->client->deleteMessage(array(
		    // QueueUrl is required
		    'QueueUrl' => $queueUrl,
		    // ReceiptHandle is required
		    'ReceiptHandle' => $msg['ReceiptHandle'],
		));
	}

	public function pushToVideoQueue($storyId){
		
		$result = $this->client->sendMessage(array(
		    // QueueUrl is required
		    'QueueUrl' => SQSQueue::Video,
		    // MessageBody is required
		    'MessageBody' => $storyId,
		    'DelaySeconds' => 0,
		    'MessageAttributes' => array(),
		));
	}
}

?>