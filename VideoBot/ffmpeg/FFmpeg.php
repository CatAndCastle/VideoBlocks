<?php

class FFmpeg{

	public $max_vid_t = 15;

	function __construct()
	{
		$this->ffmpeg = FFMPEG_BIN . " -loglevel quiet";
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

		$t_fade_audio = min($v_duration, $a_duration)-3;

		// if($v_duration > $a_duration){
		// 	$this->silentAudio($v_duration-$a_duration, $dir."/silent_end.aac");
		// 	$this->concatAudio($dir."/silent_end.aac", $a, $dir."/padded_end.aac");
		// 	$a = $dir."/padded_end.aac";
		// }

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

	}

	// ffmpeg -y -v error -i audio.aac -i silent.ac3 -filter_complex [0:0] [1:0] concat=n=2:v=0:a=1 [a] -map [a] /usr/local/zeroslant/video/7WXBh7w7kdIY/padded.aac

	function concatAudio($a1, $a2, $f){
		$command = $this->ffmpeg . " -y -i $a1 -i $a2 -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map [a] $f";
		// echo $command."\n";
		shell_exec($command);


	}

	


	


	
}


?>