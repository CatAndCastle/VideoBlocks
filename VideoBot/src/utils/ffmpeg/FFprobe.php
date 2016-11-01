<?php
/*
 * ffprobe class helper for ffmpeg 0.9+ (JSON support)
 * Written by Paulo Freitas <me@paulofreitas.me> under CC BY-SA 3.0 license
 * https://github.com/paulofreitas/php-ffprobe/blob/master/ffprobe.php
 */
require_once dirname(__DIR__, 3).'/.config.php';

class FFprobe
{
    public function __construct($filename, $prettify = false)
    {
        // if (!file_exists($filename)) {
        //     throw new Exception(sprintf('File not exists: %s', $filename));
        // }
        $this->__metadata = $this->__probe($filename, $prettify);
    }
    private function __probe($filename, $prettify)
    {
        // Start time
        $init = microtime(true);
        // Default options
        $options = '-loglevel quiet -show_format -show_streams -print_format json';
        if ($prettify) {
            $options .= ' -pretty';
        }
        // Avoid escapeshellarg() issues with UTF-8 filenames
        setlocale(LC_CTYPE, 'en_US.UTF-8');
        // Run the ffprobe, save the JSON output then decode
        $json = json_decode(shell_exec(sprintf('ffprobe %s %s', $options,
            escapeshellarg($filename))));
        if (!isset($json->format)) {
            throw new Exception('Unsupported file type');
        }
        // Save parse time (milliseconds)
        $this->parse_time = round((microtime(true) - $init) * 1000);
        return $json;
    }
    public function __get($key)
    {
        if (isset($this->__metadata->$key)) {
            return $this->__metadata->$key;
        }
        throw new Exception(sprintf('Undefined property: %s', $key));
    }

    public function getVideoStream()
    {
        foreach ($this->streams as $stream) {
            if ($stream->codec_type == 'video') {
                return $stream;
            }
        }
        return null;
    }

    public function getVideoInfo()
    {
        $stream = $this->getVideoStream();
        if($stream == null){return null;}
        $info   = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $info->duration     = (float) $stream->duration;
        $info->frame_height = (int) $stream->height;
        $info->frame_width  = (int) $stream->width;
        eval("\$frame_rate = {$stream->r_frame_rate};");
        $info->frame_rate   = (float) $frame_rate;
        $info->n_frames   = (float) $stream->nb_frames;

        return $info;
    }

    // TODO: check if audio stream is silent - with echoprint-codegen
    public function getAudioStream()
    {
        foreach ($this->streams as $stream) {
            if ($stream->codec_type == 'audio') {
                return $stream;
            }
        }
        return null;
    }

    public function getAudioInfo()
    {
        $stream = $this->getAudioStream();
        if($stream == null){return null;}
        $info   = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $info->duration     = (float) $stream->duration;
        $info->bit_rate = (int) $stream->bit_rate;
        $info->codec_name  = $stream->codec_name;

        return $info;
    }

    public function getInfo(){
        $info   = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $info->video = $this->getVideoInfo();
        $info->audio = $this->getAudioInfo();

        return $info;
    }
}