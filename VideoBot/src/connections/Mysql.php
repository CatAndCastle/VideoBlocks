<?php

require_once dirname(__DIR__, 1).'/utils/helpers.php';

class Mysql{

	public $conn;
    const MAX_TRIES = 5;

	function __construct(){
		// $this->connect();
	}

    function connect($i=0){
        // runs as soon as class is instantiated
        mysqli_report(MYSQLI_REPORT_STRICT);
        try{
            $this->conn = new mysqli(ZEROSLANT_DB_SERVER, ZEROSLANT_DB_USER, ZEROSLANT_DB_PASSWORD, ZEROSLANT_DB_NAME);
        } catch (Exception $e ) {
            if($i < self::MAX_TRIES){
                usleep(5000000);
                $this->connect($i++);
            }else{
                logme("ERROR connecting to mysql");
                logme(" -> message: " . $e->message); 
                throw new MysqlException('ERROR connecting to mysql', MysqlException::CONNECTION_ERROR);
                return;
            }
        }
    }

    function close(){
        $this->conn->close();
    }

    function setVideoStatus($storyId, $status, $url=null){
    	if(!is_null($url)){
    		$q = "INSERT INTO videos (storyId, url, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE url=?, status=?";
    		$this->query($q, [['s',$storyId], ['s',$url], ['i',$status], ['s',$url], ['i',$status]]);
    	}else{
    		$q = "INSERT INTO videos (storyId, status) VALUES (?,?) ON DUPLICATE KEY UPDATE status=?";	
    		$this->query($q, [['s',$storyId], ['i',$status], ['i',$status]]);
    	}
    }

    function saveRenderedBlock($storyId, $assetId, $videoUrl, $blockId, $tags, $timestamp){
        $tnow = time();
        $q = "INSERT INTO video_blocks_rendered (storyId, assetId, mp4_url, blockId, tags, timestamp, time_added) VALUES (?,?,?,?,?,?,?) 
                ON DUPLICATE KEY UPDATE mp4_url=?, blockId=?, tags=?";
        $this->query($q, [
            ['s',$storyId], ['s',$assetId], ['s',$videoUrl], ['s',$blockId], ['s',join(' ', $tags)],['i', $timestamp],['i', $tnow],
             ['s',$videoUrl], ['s',$blockId], ['s',join(' ', $tags)]
            ]);
    }

    function getAsset($assetId){
        $q = "SELECT data FROM assets_0816 WHERE assetId=?";
        $res = $this->select($q, [['s', $assetId]]);
        return $res[0];
    }

    function getStoryBlocks($storyId, $timestamp, $num=10){
        $q = "SELECT assetId, mp4_url, time_added, blockId FROM video_blocks_rendered WHERE storyId=? AND time_added > ? ORDER BY time_added ASC LIMIT ?";
        $res = $this->select($q, [['s', $storyId], ['i', $timestamp], ['i', $num]]);
        return $res;
    }

    function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
    *   Run Query
    */
    function query($query, $params)
    {
        $this->connect();

        $query = $query;
        $stmt = $this->conn->prepare($query);

        if(count($params) > 0)
        {
            // dynamically build bind_param args:
            $args = array();
            $param_type = '';
            foreach ($params as $param) {
                $param_type .= $param[0];
                $args[] = &$param[1];
            }
            $pt = &$param_type;
            array_unshift($args, $pt);

            $a = &$args;
            // echo json_encode($this->refValues($args));
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($args));
        }

        $stmt->execute();
        
        $error = $stmt->error;
        $affected_rows = $this->conn->affected_rows;
        
        $stmt->close();
        $this->close();

        return array(
                'error' => $error,
                'affected_rows' => $affected_rows
            );

        
    }

    /**
    *   Run SELECT Query
    */
    function select($query, $params)
    {
        $this->connect();

        $query = $query;
        $stmt = $this->conn->prepare($query);
        // echo($query);
        // print_r($params);

        if(count($params) > 0)
        {
            // dynamically build bind_param args:
            $args = array();
            $param_type = '';
            foreach ($params as $param) {
                $param_type .= $param[0];
                $args[] = &$param[1];
            }
            $pt = &$param_type;
            array_unshift($args, $pt);

            $a = &$args;
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($args));
        }

        $stmt->execute();
        // echo $stmt->error;
        
        /* Fetch result to array */
        $res = $stmt->get_result();
        $data = array();
        while($row = $res->fetch_array(MYSQLI_ASSOC)) {
          array_push($data, $row);
        }

        $stmt->close();
        $this->close();
        return $data;
    }

}
?>