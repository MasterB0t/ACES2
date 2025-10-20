#!/usr/bin/env php
<?php

error_reporting(0);
date_default_timezone_set('UTC');

include "/home/aces/stream/config.php";

$CHANID = (int)$argv[1];
if(!$CHANID) die;


$LOGFILE = "/home/aces/logs/streams/stream-$CHANID.log";
$LOG = 0;

// 0 - NO LOGS
// 1 - ERROR ONLY
// 2 - ERRORS AND VERBOSE MESSAGE
// 3 - ERRORS, VERBOSE MESSAGE AND DEBUG 

function logfile($text) {

    global $LOGFILE,$LOG;

    if(!$LOG) return false;

    file_put_contents($LOGFILE, "\n[ ".date('Y:m:d H:i:s')." ] $text \n\n", FILE_APPEND );

}

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.streamlogs' ");
if(!$LOG=mysqli_fetch_array($r)['value'])$LOG=0;

$r=$DB->query("SELECT id FROM iptv_streaming WHERE server_id = $SERVER_ID AND chan_id = $CHANID ");
$STREAMING_ID = mysqli_fetch_array($r)['id'];


while($x<120) { 
    
    clearstatcache();
    sleep(1);
    
    //IF ACES_STREAM IS NOT RUNNING LET DIE;
//    $ffmpeg_pid=file_get_contents("/home/aces/run/ffmpeg_stream-$CHANID.pid");
//    if(empty($ffmpeg_pid)) { 
//        logfile("COULD NOT GET ACES STREAM PID");
//        die;
//    } else if(!posix_getpgid($ffmpeg_pid)) { 
//        die;
//    }
    
    //GETTING CODEC INFO
    $chunk = exec("tail -1 /home/aces/stream_tmp/$CHANID-.m3u8 ");

    if(!empty($chunk)) { 
    
        //$stream_v_info = json_decode(shell_exec(" ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk ",true));
        $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk " ),true);
        $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name /home/aces/stream_tmp/$chunk " ),true);
        if( !$bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/stream_tmp/$chunk  " )) $bitrate = 0;


        $s_info = array();
        $s_info['video_codec'] = $stream_v_info['streams'][0]['codec_name'];
        $s_info['audio_codec'] = $stream_a_info['streams'][0]['codec_name'];
        $s_info['resolution'] = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
        $s_info['frames'] = round( (int) explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[0] / explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[1],0)  ;
        $s_info = serialize($s_info);

        //RESET DOWNTIME;
        $DB->query("UPDATE iptv_channels set down_since = 0 WHERE id = $CHANID ");
        $DOWN_TIME=0;

        $DB->query("UPDATE iptv_streaming SET status = 1, video_codec = '{$stream_v_info['streams'][0]['codec_name']}', audio_codec = '{$stream_a_info['streams'][0]['codec_name']}', `bitrate` = '$bitrate', info = '$s_info', reconnected = reconnected + 1, connected_datetime=NOW() WHERE id=$STREAMING_ID ");
        //$DB->query("UPDATE iptv_streaming SET status = 1  WHERE id=$STREAMING_ID ");
        
        if($LOG > 1 ) logfile("CONNECTED $url" );
        break;

    }
        
    $x++;
        
}