<?php
use Aws\Sqs\SqsClient;

abstract class SQSQueue
{
    const Video 		= "https://sqs.us-east-1.amazonaws.com/568183857234/zeroslant_video";
    const VideoBlocks 	= "https://sqs.us-east-1.amazonaws.com/568183857234/zeroslant_video_blocks";
}

class SQS{

	public $client;
	private $REGION = "us-east-1";

	function __construct(){
		
		try{
		    $this->client = SqsClient::factory(array(
			    'credentials' => array(
			        'key'    => AWS_ACCESS_KEY,
			        'secret' => AWS_SECRET_KEY,
			    ),
			    'region' => 'us-east-1'
			));
		} catch (Exception $e) {
			throw new SQSException('Error connecting to Amazon SQS', SQSException::CONNECTION_ERROR);
		}
	}

	public function receiveMessages($queueUrl, $n){
		try{
			return $this->client->receiveMessage(array(
			    // QueueUrl is required
			    'QueueUrl' => $queueUrl,
			    'MaxNumberOfMessages' => $n,
			    'WaitTimeSeconds' => 5,
			    'MessageAttributeNames' => array('All'),
			));
		} catch (Exception $e) {
			// lost connection to SQS?
			throw new SQSException('Error connecting to Amazon SQS', SQSException::CONNECTION_ERROR);
		}
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

	public function sendMessage($queue, $body, $attributes){
		
		$result = $this->client->sendMessage(array(
		    // QueueUrl is required
		    'QueueUrl' => $queue,
		    // MessageBody is required
		    'MessageBody' => $body,
		    'DelaySeconds' => 0,
		    'MessageAttributes' => $attributes,
		));
	}
}

?>