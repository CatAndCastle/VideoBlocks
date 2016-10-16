<?php

class FFmpeg{

	public $max_vid_t = 15;
	// public $default_audio = dirname(__DIR__, 1).'/resources/default_audio.aac';

	function __construct()
	{
		$this->ffmpeg = FFMPEG_BIN . " -loglevel quiet";
		$this->default_audio = dirname(__DIR__, 1).'/resources/default_audio.aac';
	}

	/**
		Split Video into frames
			fps = 29.97
			max segment length = 15 seconds
	**/
	function splitIntoFrames($url, $dir){
		makedir($dir, 0777);
		$command = $this->ffmpeg . " -i $url -r 29.97 -ss 0 -t ".$this->max_vid_t." ".$dir."/frame%d.png";
		// echo $command."\n";
		shell_exec($command);
	}

	/**
		Extract audio track from video file
	**/
	function extractAudio($url, $out){
		$command = $this->ffmpeg . " -y -i $url -map 0:1 $out";
		// echo $command."\n";
		shell_exec($command);
	}


	function framesToVideoCMD($out){
		return $this->ffmpeg . " -y -f image2pipe -vcodec mjpeg -r 29.97 -i - -vcodec libx264 -b:v 5000k -s 1920x1080 -pix_fmt yuv420p " . $out;
	}

	// ffmpeg -y -v error -i .data/bGwcqDbkZ3l9/video.mp4 -ss 0 -t 38 -i .data/bGwcqDbkZ3l9/audio.aac -vf fade=t=in:s=0:n=30 -af "afade=t=in:st=0:d=3,afade=t=out:st=35.00:d=3" -map 0:0 -map 1:0 .data/bGwcqDbkZ3l9/final1.mp4
	function combineAV($v, $a, $f, $dir){
		if(is_null($a)){
			rename($v, $f);
			return;
		}
		$ffprobe = new FFprobe($v);
		$v_info = $ffprobe->getInfo();
		$v_duration = $v_info->video->duration;

		$ffprobe = new FFprobe($a);
		$a_info = $ffprobe->getInfo();
		$a_duration = $a_info->audio->duration;

		// pad with 5 secs of silence at the beginnig - for title segment
		// $this->silentAudio(5, $dir."/silent5.aac");
		// $this->concatAudio($dir."/silent5.aac", $a, $dir."/padded_front.aac");
		// $a = $dir."/padded_front.aac";

		

		if($v_duration > $a_duration){
			$this->silentAudio($v_duration-$a_duration, $dir."/silent_end.aac");
			$padded = $this->concatAudio($a, $dir."/silent_end.aac", $dir."/padded_end.aac");

			// cross fade with default
			$faded = $this->fadeIntoDefaultAudio($padded, $a_duration-3, $v_duration, $dir."/faded.aac");

			$a = $faded;
		}

		$t_fade_audio = $v_duration-3; // min($v_duration, $a_duration)-3;

		$command = $this->ffmpeg . " -y -i $v "
								. " -ss 0 -t $v_duration -i $a"
								. " -c:a aac -c:v copy"
								. " -af 'afade=t=in:st=0:d=3,afade=t=out:st=$t_fade_audio:d=3'"
								. " -map 0:0 -map 1:0 $f";

		// echo $command."\n";
		shell_exec($command);


	}

	function silentAudio($t, $f){
		$command = $this->ffmpeg . " -y -f lavfi -i aevalsrc=0:d=$t $f";
		// echo $command."\n";
		shell_exec($command);
		return $f;

	}
	
	function concatAudio($a1, $a2, $f){
		$command = $this->ffmpeg . " -y -i $a1 -i $a2 -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map [a] $f";
		// echo $command."\n";
		shell_exec($command);
		return $f;
	}

	function fadeIntoDefaultAudio($a, $tfade, $duration, $f){
		$command = $this->ffmpeg . " -y -ss 0 -t $duration -i $a"
								." -ss 0 -t $duration -i ".$this->default_audio
								." -filter_complex '[0:a]afade=t=out:st=$tfade:d=2[a0]; [1:a]afade=t=in:st=$tfade:d=2[a1]; [a0][a1]amerge=inputs=2[out]'"
								." -map [out] -ac 2 $f";

		// echo $command."\n";
		shell_exec($command);
		return $f;
	}

	


	


	
}


?>