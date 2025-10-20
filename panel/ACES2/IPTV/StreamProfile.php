<?php

namespace ACES2\IPTV;

use ACES2\DB;

class StreamProfile {

    public $id=0;
    public $name = '';
    public $video_codec = 'copy';
    public $audio_codec = 'copy';
    public $video_bitrate = 0;
    public $audio_bitrate = 0;
    public $preset = '';
    public $threads = 0;
    public $screen_size = '';
    public $framerate = 0;
    public $pre_args = '';
    public $post_args = '';
    public $probe_size = 0;
    public $analyze_duration = 0;
    public $gen_pts = 0;
    public $native_frames = 0;
    public $stream_all = 0;
    public $skip_no_audio = 0;
    public $skip_no_video = 0;
    public $user_agent = '';
    public $timeout = 0;
    public $proxy = '';
    public $adaptive = 0;
    public $adaptive_opt = '';
    public $custom = '';
    public $headers = '';

    public $only_for_chan_id = 0;


    public function __construct(int $stream_profile_id = null ){

        if(!is_null($stream_profile_id)) {

            $db = new DB;
            $r = $db->query("SELECT * FROM iptv_stream_options WHERE id = $stream_profile_id ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to retrieve Stream Profile #$stream_profile_id");

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->video_codec = $row['video_codec'];
            $this->audio_codec = $row['audio_codec'];
            $this->audio_bitrate = (int)$row['audio_bitrate_kbps'] ? $row['audio_bitrate_kbps'] : '';
            $this->video_bitrate = (int)$row['video_bitrate_kbps'] ? $row['video_bitrate_kbps'] : '';
            $this->preset = $row['preset'];
            $this->threads = $row['threads'];
            $this->screen_size = $row['screen_size'];
            $this->framerate = $row['framerate'];
            $this->pre_args = $row['pre_args'];
            $this->post_args = $row['post_args'];
            $this->probe_size = $row['probesize'];
            $this->analyze_duration = $row['analyzeduration'];
            //$this->analyze_duration = $row['analyze_duration'];
            $this->gen_pts = $row['gen_pts'];
            $this->native_frames = $row['native_frames'];
            $this->stream_all = $row['stream_all'];
            $this->skip_no_audio = $row['skip_no_audio'];
            $this->skip_no_video = $row['skip_no_video'];
            $this->user_agent = $row['user_agent'];
            $this->timeout = (int)$row['timeout'] ? $row['timeout'] : '';
            $this->proxy = $row['proxy'];
            $this->adaptive = $row['adaptive'];
            $this->adaptive_opt = json_decode($row['adaptive_opt'],1);
            $this->custom = $row['custom'];
            $this->headers = $row['headers'];
            $this->only_for_chan_id = $row['only_chan_id'];
        }

    }

    private function setAll($POST) {
        $db = new DB;

        $this->name =$db->escString($POST['name']);

        $this->video_codec = ($POST['video_codec']) ? trim($POST['video_codec']) : 'copy';

        $this->audio_codec = ($POST['audio_codec']) ? trim($POST['audio_codec']) : 'copy';

        if(!$this->video_bitrate = (int)$POST['video_bitrate_kbps'])
            $this->video_bitrate = '';

        if(!$this->audio_bitrate = (int)$POST['audio_bitrate_kbps'])
            $this->audio_bitrate = '';

        $this->preset = $POST['preset'] ?: '';

        $this->threads = (int)$POST['threads'];

        if($POST['screen_size']){
            $exp = explode('x', trim($POST['screen_size']));
            if( !(int)$exp[0] || !(int)$exp[1] || count($exp) > 2 )
                throw new \Exception("Enter a valid screen size.");
            $this->screen_size = trim($POST['screen_size']);
        }

        $this->framerate = (int)$POST['framerate'];

        $this->pre_args = $POST['pre_args'] ?: '';

        $this->post_args = $POST['post_args'] ?: '';

        $this->probe_size = (int)$POST['probe_size'];

        $this->analyze_duration = (int)$POST['analyze_duration'];

        $this->gen_pts = $POST['gen_pts'] ? 1 : 0;

        $this->native_frames = $POST['native_frames'] ? 1 : 0;

        $this->stream_all = $POST['stream_all'] ? 1 : 0;

        $this->skip_no_video = $POST['skip_no_video']  ? 1 : 0;

        $this->skip_no_audio = $POST['skip_no_audio'] ? 1 : 0;

        $this->user_agent = $db->escString($POST['user_agent']);

        $this->timeout = (int)$POST['timeout'];

        $this->proxy = $POST['proxy'] ? $db->escString($POST['proxy']) : '';

        $this->adaptive_opt = $POST['adaptive_opt'];

        $adaptive_opt=[];$adaptive=0;
        if(is_array($POST['adaptive_video_codec'])) {
            foreach($POST['adaptive_video_codec'] as $i => $v) {

                $adaptive_opt[] = array(
                    'profile_name' => $db->escString($POST['adaptive_profile'][$i]),
                    'video_codec'=> $POST['adaptive_video_codec'][$i] ,
                    'audio_codec' => $POST['adaptive_audio_codec'][$i] ,
                    'video_bitrate' => $POST['adaptive_video_bitrate'][$i],
                    'audio_bitrate' => $POST['adaptive_audio_bitrate'][$i],
                    'screen_width' => $POST['adaptive_screen_width'][$i],
                    'screen_height' => $POST['adaptive_screen_height'][$i],
                );
                $adaptive++;

            }

            $this->adaptive_opt = json_encode($adaptive_opt);
            $this->adaptive = $adaptive;

        }
    }


    public static function create($POST):self  {

        $Stream = new StreamProfile();
        if(empty($POST['user_agent']))
            $POST['user_agent'] = 'AcesIPTV PANEL';
        $Stream->setAll($POST);

        $db = new DB;

        $db->query("INSERT INTO iptv_stream_options ( only_chan_id, name, video_codec, video_bitrate_kbps,
                                 audio_bitrate_kbps, audio_codec, screen_size, framerate, preset, threads, segment_time, 
                                 segment_list_files, segment_wrap, pre_args, post_args, probesize, analyzeduration, gen_pts, native_frames, 
                                 stream_all, skip_no_audio, skip_no_video, opt, proxy, timeout, user_agent,  adaptive, 
                                 adaptive_opt)
            VALUES( '0', '$Stream->name', '$Stream->video_codec', '$Stream->video_bitrate', '$Stream->audio_bitrate', '$Stream->audio_codec', 
                   '$Stream->screen_size', '$Stream->framerate', '$Stream->preset', '$Stream->threads',  10, 4, 5, '$Stream->pre_args', 
                   '$Stream->post_args', '$Stream->probe_size', '$Stream->analyze_duration', '$Stream->gen_pts', '$Stream->native_frames', $Stream->stream_all, '$Stream->skip_no_audio', 
                   '$Stream->skip_no_video', '', '$Stream->proxy', $Stream->timeout, '$Stream->user_agent', 
                     '', '' )");


        $Stream = $db->insert_id;

        return new self($Stream);
        
    }

    public function update($POST):bool {
        $db = new DB;
        $this->setAll($POST);
        $db->query("UPDATE iptv_stream_options SET name = '$this->name', video_codec = '$this->video_codec', audio_codec = '$this->audio_codec',
                               video_bitrate_kbps = '$this->video_bitrate', audio_bitrate_kbps = '$this->video_bitrate', screen_size = '$this->screen_size', 
                               framerate = '$this->framerate', preset = '$this->preset', threads = '$this->threads', pre_args = '$this->pre_args', post_args = '$this->post_args', 
                               probesize = '$this->probe_size', analyzeduration = '$this->analyze_duration', gen_pts = '$this->gen_pts', native_frames = '$this->native_frames', stream_all = '$this->stream_all', 
                               skip_no_audio = '$this->skip_no_audio', skip_no_video = '$this->skip_no_video', proxy = '$this->proxy', timeout = '$this->timeout', 
                               user_agent = '$this->user_agent', adaptive = '$this->adaptive', adaptive_opt = '$this->adaptive_opt' WHERE id = '$this->id';
                            ");

        return true;
    }

    public function remove():bool {
        $db = new DB();
        $r=$db->query("SELECT id FROM iptv_channels WHERE stream_profile = '$this->id';");
        if($r->fetch_assoc())
            throw new \Exception("Unable to remove this stream profile. Stream are using this profile.");

        $db->query("DELETE FROM iptv_stream_options WHERE id = '$this->id';");
        return true;
    }

}