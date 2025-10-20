<?php

die; 
//error_reporting(0);

set_time_limit ( 0 );


//ob_implicit_flush(1);
//ob_start();

$CHANID = '182';

if(empty($argv[1]) || !is_numeric($argv[1])) { die; };
$ACCESS_ID = $argv[1];
$U_PORT = 1000000 + $ACCESS_ID;


$ffm_source = "ffmpeg -fflags +genpts -copyts -i /home/aces/stream_tmp/$CHANID-.m3u8 -c:v copy -c:a copy -f mpegts udp://127.0.0.1:{$U_PORT}/1 ";
$ffm_source_pid = NULL;

//$ffm_client = "ffmpeg  -i  udp://127.0.0.1:10000/1 -c:v copy -c:a copy -strict -2  -listen 1 -f mpegts  http://127.0.0.1:9092/Stream1 ";
//$ffm_client = "ffmpeg  -i  udp://127.0.0.1:10000/1 -c:v copy -c:a copy -strict -2  -listen 1 -f mpegts  /home/aces/stream_tmp_clients/1.mp4 ";
$ffm_client = "ffmpeg -i udp://127.0.0.1:{$U_PORT}/1 -c:v copy -c:a copy -strict -2   -f segment -segment_format mpegts -segment_time 10 -segment_list_size 3 -segment_wrap 4 -segment_list_type m3u8 -segment_list_flags +live  -segment_list /home/aces/stream_tmp_clients/$ACCESS_ID-.m3u8 /home/aces/stream_tmp_clients/$ACCESS_ID-%01d.ts ";
$ffm_client_pid = NULL;

$chunk = exec("tail -n 1 /home/aces/stream_tmp/$CHANID-.m3u8 ");

$stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk " ),true);
$stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_streams /home/aces/stream_tmp/$chunk " ),true);
//if( !$bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/stream_tmp/$chunk  " )) $bitrate = 0;

$VIDEO_CODEC = $stream_v_info['streams'][0]['codec_name'];
$AUDIO_CODEC = $stream_a_info['streams'][0]['codec_name'];
$RESOLUTION = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
$FRAMES =  round( (int) explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[0] / explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[1],0)  ;
$SAMPLE_RATE = $stream_a_info['streams'][0]['sample_rate'];       

function l($m) { 
    error_log($m."\n", 3, "/home/aces/logs/t.log");
}

function backup() { 
    
    global $ffmpeg_pid,$source, $LAST_CHUNK;
    if($ffmpeg_pid) return false;  
    
    //$ffmpeg = "ffmpeg  -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -loop 1  -i /home/aces/index.png -threads 1 -s 1280x720 -preset ultrafast -r 60 -c:a aac -c:v h264 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 6 -segment_list_type m3u8 -segment_list_flags +live  -segment_list /home/aces/stream_tmp/t1-.m3u8  /home/aces/stream_tmp/t1-%01d.ts ";
    
    //$ffmpeg = "ffmpeg -y -fflags +genpts -i 'http://192.168.0.21:9090/load/123456/182.m3u8'  -threads 1 -s 1280x720 -preset ultrafast -r 60 -c:a aac -c:v h264 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 6 -segment_list_type m3u8 -segment_list_flags +live  -segment_list /home/aces/stream_tmp/t1-.m3u8  /home/aces/stream_tmp/t1-%01d.ts ";
    //$ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $ffmpeg )));

    
    
}

function close() {  
    
    global $ffm_source_pid, $ffm_client_pid, $ACCESS_ID;
    
    l("CLOSING...");
    exec("kill -9 $ffm_source_pid ");
    exec("kill -9 $ffm_client_pid ");
    exec("rm -rf /home/aces/stream_tmp_clients/$ACCESS_ID-*");
    
    die; 
    
} 


require '/home/aces/stream/config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ l("NO DB"); die(); }

$r=$DB->query("SELECT id FROM iptv_access WHERE id = $ACCESS_ID ");
//if(!mysqli_fetch_array($r)) { l('NO ACCESS');  die; } 


//$ffm_source_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $ffm_source,'/home/aces/logs/source.txt' )));
$ffm_source_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $ffm_source )));
if(!posix_getpgid($ffm_source_pid)) {  close(); }
//$ffm_client_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $ffm_client,'/home/aces/logs/client.txt' )));
$ffm_client_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $ffm_client )));
if(!posix_getpgid($ffm_client_pid)) {  close(); }

$DB->close();
while(true) { 
    sleep(30); 
    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
    $r=$DB->query("SELECT id FROM iptv_access WHERE id = $ACCESS_ID ");
    if(!mysqli_fetch_array($r)) { l('NO ACCESS');  close(); } 
    $DB->close();
//    sleep(10);
//    if(!$source) { 
//        exec("kill -9 $ffm_source_pid ");
//        $ffm_backup  = " ffmpeg -fflags +genpts -re -i /home/aces/stream_tmp/182-.m3u8 -vf drawtext=\"fontfile=/usr/share/fonts/cantarell/Cantarell-Regular.otf: text='TRYING TO DO THIS!!!': fontcolor=white: fontsize=32: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text_w)/2: y=(h-text_h)/2\" -c:a aac  -c:v libx264 -threads 1 -preset ultrafast -r $FRAMES -s $RESOLUTION -ar $SAMPLE_RATE -f mpegts udp://127.0.0.1:{$U_PORT}/1  ";
//        $ffm_source_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $ffm_backup )));
//        $source =1;
//    }
        
}
    
    
sleep(35);
exec("kill -9 $ffm_source_pid ");

//$ffm_backup  = " ffmpeg  -fflags +genpts -re -i http://piesustv.net:8000/live/Christo003ujelUtRs/I4xfOwtv8W/769.ts -c:a aac  -c:v libx264 -threads 1 -preset ultrafast -r $FRAMES -s $RESOLUTION -ar $SAMPLE_RATE -f mpegts udp://127.0.0.1:10000/1  ";

$ffm_backup  = " ffmpeg -fflags +genpts -re -i /home/aces/stream_tmp/182-.m3u8 -vf drawtext=\"fontfile=/usr/share/fonts/cantarell/Cantarell-Regular.otf: text='TRYING TO DO THIS!!!': fontcolor=white: fontsize=32: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text_w)/2: y=(h-text_h)/2\" -c:a aac  -c:v libx264 -threads 1 -preset ultrafast -r $FRAMES -s $RESOLUTION -ar $SAMPLE_RATE -f mpegts udp://127.0.0.1:10000/1  ";

//$ffm_backup = "ffmpeg  -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=$SAMPLE_RATE -loop 1  -i /home/aces/index.png  -c:a aac  -c:v libx264 -threads 1 -preset ultrafast -r $FRAMES -s $RESOLUTION -ar $SAMPLE_RATE -f mpegts udp://127.0.0.1:10000/1 ";

$ffm_backup_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $ffm_backup,'/home/aces/logs/backup.txt' )));
sleep(30);

exec("kill -9 $ffm_backup_pid ");

$ffm_source_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $ffm_source,'/home/aces/logs/source.txt' )));
while(true)sleep(1);


//$ffm_backup = "ffmpeg  -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44800 -loop 1  -i /home/aces/index.png -threads 1 -s 1280x720 -preset ultrafast -r $FRAMES -c:a aac -c:v libx264 -f mpegts  udp://127.0.0.1:10000/1";