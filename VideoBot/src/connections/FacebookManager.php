<?php

require_once dirname(__DIR__, 1).'/vendor/autoload.php';

class FacebookManager{

	const APP_ID = '869865473112380';
	const APP_SECRET = 'd2c57b5ed38ec050f8b4e241f86ca89b';
	const PAGE_ID = '1869554856603668';
	/** ZERO SLANT Page Token - never expires **/
	// @masha
	// const APP_TOKEN = 'EAAMXI1aGZBTwBAKd6DoVEOdxQ1An8cSyZAvSBYUZAuBxqRfqeiCpc8wZBZBiF3TaedoTIHQQnwptqkVuUQwvfnsXZCwAQnt88UmsgeopsCCiTWENRSqBpCYVoSqza9pxbN7t5Mf9oXe6k1Hgc55xtLY9z3xi1gzDmZAnoDTqiu0xQZDZD';
	// @eunice fake page
	//const APP_TOKEN = 'EAAMXI1aGZBTwBANbhH53fWjvxirXfPKtN5fnb5UV0zXOgwK2OqTYvMBquERgEkMuxVRDKnvB40YKdbyZBcFA9IXeNcU4dkDfRr1ZBQ6biEKZC8cXPuPkI1WOl7iVXmLs5sBHZAiww9ZBimL8Vl8zZALtj3mZCcZARq5pytZA9IMzWh3Gs3TEVTJuta';
	// @fake eaccount test live stream
	const APP_TOKEN = 'EAAMXI1aGZBTwBAJzyO9Lp7llvrPmkhGsoZC2UXLYkej3qnZCyZBnMdQDMbndpSFPdHKgQEWPSUNR4vapZCRxkaNcJzpiT4fL4VIu4MYYX2Mk5B3GrR5jWh6dEGRofLOerPT8TvDCovwhdkE974CN4kmgDqgavoR8rsc7mAZCkbdODwfg2I9rKY';
	private $db;

	private $userId;

	private $userToken;
	private $userName;
	private $userGender;

	function __construct($userId=null){
		
	}

	function init(){
		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.7',
		  ]);

		$fb->setDefaultAccessToken(self::APP_TOKEN);
		return $fb;

	}

	function live_video_start($title, $description, $content_tags=[]){
		$fb = $this->init();

		$data = array(
			'title' =>  $title,
			'description' => $description,
			'content_tags' => $content_tags,
			'hello' => 'asd'
			// 'status' => 'UNPUBLISHED',
			// 'privacy' => ['value'=>'SELF']
		);


		try {
		  $response = $fb->post('/me/live_videos', $data);
		  var_dump($response);
		  return $response->getDecodedBody();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned FacebookResponseException '.$e->getHttpStatusCode().': ' . $e->getMessage()."\n";
		  echo "Graph sub error code: ". $e->getSubErrorCode()."\n";
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK FacebookSDKException'.$e->getHttpStatusCode().': ' . $e->getMessage();
		  echo "Graph sub error code: ". $e->getSubErrorCode();
		  return false;

		}
	}

	function getPlace($placeId){
		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.7',
		  ]);

		$fb->setDefaultAccessToken(self::APP_TOKEN);

		$response = $fb->get('/'+$placeId);

		// $request = new FacebookRequest(
		//   $session,
		//   'GET',
		//   '/'.$placeId
		// );
		// $response = $request->execute();
		// $graphObject = $response->getGraphObject();

		print_r($response);
	}

	
	function search($params){
		
		$fb = $this->init();

		try {
		  $response = $fb->get('/search?'.http_build_query($params));
		  // var_dump($response);
		  return $response->getDecodedBody();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}
	}

	function node($nodeId, $params){
		$fb = $this->init();
		try {
		  $response = $fb->get('/'.$nodeId.'?'.http_build_query($params));
		  // var_dump($response);
		  return $response->getDecodedBody();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}
	}

	function delete($nodeId){
		$fb = $this->init();
		try {
			$response = $fb->delete('/'.$nodeId);
			// var_dump($response);
			return $response->getDecodedBody();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}
		
	}

	function getNodes($nodes){
		$fb = $this->init();

		try {
		  $response = $fb->get('/?ids='.implode(",", $nodes));
		  // var_dump($response);
		  return $response->getDecodedBody();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}
	}

	function updatePost($postId, $params){
		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.7',
		  ]);

		$fb->setDefaultAccessToken(self::APP_TOKEN);

		try {
		  $response = $fb->post('/'.$postId, $params);
		  return $response;
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}


	}

	function uploadVideoFromUrl($url, $description, $title, $content_tags = []){

		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.7',
		  ]);

		$fb->setDefaultAccessToken(self::APP_TOKEN);

		$data = array(
			'title' => $title,
			'description' => $description,
			'file_url' => $url,
			'secret' => false,
			// 'content_category'=> ??,
			'content_tags' => $content_tags
			);

		try {
		  $response = $fb->post('/me/videos', $data);
		  // $response = $fb->post('/672676540/videos', $data);
		  return $response;
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();

		  // send email to admins to notify
		  $send_to = "masha@zeroslant.com, ryan@zeroslant.com, eunice@zeroslant.com";
		  mail($send_to, "Facebook Error posting story \"$title\"", $e->getMessage());

		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();

		  $send_to = "masha@zeroslant.com, ryan@zeroslant.com, eunice@zeroslant.com";
		  mail($send_to, "Facebook Error posting story \"$title\"", $e->getMessage());

		  return false;

		}



	}

	function listVideo(){
		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.7',
		  ]);

		$fb->setDefaultAccessToken(self::APP_TOKEN);

		try {
		  $response = $fb->get('/'.self::PAGE_ID.'?fields=videos{content_tags,title,place,description,permalink_url},name', array());
		  // print_r($response);
		  return json_decode($response->getBody(),true);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  return false;

		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  return false;

		}
	}


	function postVideo($path, $momunt){
		if($this->userToken == null){
			echo "\n\n NO TOKEN \n\n";
			return false;
		}

		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.5',
		  ]);
		

		$momuntId = $momunt['momuntId'];
		$description = "I used @momunt to discover and share this visual collection. Download to discover what's happening around you momunt.com/$momuntId";
		
		$data = [
		  'title' 		=> $momunt['name'],
		  'description' => $description,
		  'source' 		=> $fb->videoToUpload($path),
		  'embeddable' 	=> true
		];
		print_r($data);
		try {
		  $response = $fb->post('/me/videos', $data, $this->userToken);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		// $graphNode = $response->getGraphNode();
		// var_dump($graphNode);

		// echo 'Video ID: ' . $graphNode['id'];

	}

	function postLink($link, $momunt){
		if($this->userToken == null){
			echo "\n\n NO TOKEN \n\n";
			return false;
		}

		$fb = new Facebook\Facebook([
		  'app_id' => self::APP_ID,
		  'app_secret' => self::APP_SECRET,
		  'default_graph_version' => 'v2.4',
		  ]);

		$linkData = [
		  'link' => $link,
		  'message' => 'Check out "'.$momunt['name'].'"',
		  ];

		try {
		  // Returns a `Facebook\FacebookResponse` object
		  $response = $fb->post('/me/feed', $linkData, $this->userToken);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		$graphNode = $response->getGraphNode();

	}
}

?>