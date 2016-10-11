<?php
	
	// echo dirname(__DIR__, 1)."\n";

	// echo __DIR__."\n";

	$str = "Girl&/  Boss?>*&%";
	$str = preg_replace("/[\s&-+.^:,#*?>%\\/]/","",$str);
	echo $str."\n";
?>