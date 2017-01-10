<?php

Class Http{
	
	function __construct(){
	}

	function get($url, $tryagain=5){
		// return json_decode( file_get_contents($url), true );

		$ch = curl_init();
		$timeout = 15;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		
		$res = curl_exec($ch);
		// print_r($res);
		if(is_string($res)){
			$res = json_decode($res, true);
		}
		$res['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		
		switch ($res['http_code']) {
			case 200:  # OK
			  	break;
			case 503:
				if($tryagain>0){
					curl_close($ch);
					usleep(5000000);
					return $this->get($url, $tryagain-1);
				}
				break;
			default:
				echo "ERROR: $url\n";
		  		echo '--> Unexpected HTTP code: ', $res['http_code'], "\n";
		}

		curl_close($ch);
		return $res;

	}

	function post($url, $data){

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($ch);
		if(is_string($res)){
			$res = json_decode($res, true);
		}
		curl_close($ch);

		return $res;
	}
}

?>