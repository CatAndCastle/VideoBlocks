<?php
use Aws\S3\S3Client;

abstract class S3Bucket
{
    const Video = "video.zeroslant.com";
}

class AWSS3{
	public $client;

	private $REGION = "us-east-1";

	function __construct(){
		
	    $this->client = S3Client::factory(array(
		    'credentials' => array(
		        'key'    => AWS_ACCESS_KEY,
		        'secret' => AWS_SECRET_KEY,
		    ),
		    'region' => $this->REGION
		));
	}

	public function upload($bucket, $file, $wait){
		$name = pathinfo($file)['basename'];

		$this->client->putObject(array(
		    'Bucket'     => $bucket,
		    'Key'        => $name,
		    'SourceFile' => $file
		));

		if($wait){
			// We can poll the object until it is accessible
			$this->client->waitUntil('ObjectExists', array(
			    'Bucket' => $bucket,
			    'Key'    => $name
			));
		}
	}

}

?>