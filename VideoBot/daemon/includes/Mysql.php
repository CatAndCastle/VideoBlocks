<?php

class Mysql{

	public $conn;
	function __construct(){
		// runs as soon as class is instantiated
        $this->supportPhoneNumbers = true;
		$this->conn = new mysqli(ZEROSLANT_DB_SERVER, ZEROSLANT_DB_USER, ZEROSLANT_DB_PASSWORD, ZEROSLANT_DB_NAME) 
						or die('Error connecting to the database');
		mysqli_report(MYSQLI_REPORT_STRICT);
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

        return array(
                'error' => $error,
                'affected_rows' => $affected_rows
            );
        // /* Fetch result to array */
        // $res = $stmt->get_result();
        // while($row = $res->fetch_array(MYSQLI_ASSOC)) {
        //   array_push($a_data, $row);
        // }
    }

    /**
    *   Run SELECT Query
    */
    function select($query, $params)
    {
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
        return $data;
    }

}
?>