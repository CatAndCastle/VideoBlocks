<?php

function checkRemoteFile($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    // don't download content
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if(curl_exec($ch)!==FALSE)
    {
        return true;
    }
    else
    {
        return false;
    }
}

function writeToFile($f, $array){
	$fp = fopen($f, 'w');
	fwrite($fp, json_encode($array));
	fclose($fp);
}

function makedir($dir){
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

function rrmdir($dir) { 
    if (is_dir($dir)) { 
        $objects = scandir($dir); 
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (is_dir($dir."/".$object)){
                    rrmdir($dir."/".$object);
                }
            }else{
               unlink($dir."/".$object); 
            } 
        }
    rmdir($dir); 
    } 
 }

?>