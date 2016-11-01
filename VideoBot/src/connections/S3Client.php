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

	public function upload($bucket, $file, $key, $wait){

		$this->client->putObject(array(
		    'Bucket'     => $bucket,
		    'Key'        => $key,
		    'SourceFile' => $file,
		    'ACL'        => 'public-read',
		));

		if($wait){
			// We can poll the object until it is accessible
			$this->client->waitUntil('ObjectExists', array(
			    'Bucket' => $bucket,
			    'Key'    => $key
			));
		}

		return "https://s3.amazonaws.com/video.zeroslant.com/".$key;
	}

}

?>