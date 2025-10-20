<?php

//date_default_timezone_set('UTC');
set_time_limit(0);

include "/home/aces/stream/config.php";
include "/home/aces/panel/ACES2/DB.php";
include "/home/aces/panel/ACES2/Curl.php";
include "/home/aces/panel/ACES2/IPTV/StreamStats.php";
include "/home/aces/panel/ACES2/IPTV/StreamEvent.php";
include "/home/aces/panel/ACES2/IPTV/StreamProfile.php";
include "/home/aces/panel/ACES2/IPTV/Stream.php";
include "/home/aces/panel/ACES2/IPTV/Streaming.php";
include "/home/aces/panel/ACES2/IPTV/SMARKETS/Smarkets.php";
include "/home/aces/panel/ACES2/IPTV/SMARKETS/Event.php";
include "/home/aces/stream/YDLSites.php";

use ACES2\IPTV\StreamStats;
use ACES2\IPTV\StreamProfile;
use ACES2\IPTV\Streaming;
use ACES2\IPTV\SMARKETS\Event;

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$StreamID = (int)$argv[1];


if(is_file("/home/aces/run/aces_stream-$StreamID.pid")) die;
if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );
file_put_contents("/home/aces/run/aces_stream-$StreamID.pid",getmypid());

const L_ERROR = 1;
const L_VERBOSE = 2;
const L_DEBUG = 3;

// 0 - NO LOGS
// 1 - ERROR ONLY
// 2 - ERRORS AND VERBOSE MESSAGE
// 3 - ERRORS, VERBOSE MESSAGE AND DEBUG

$LOGFILE = "/home/aces/logs/streams/stream-$StreamID.log";
file_put_contents($LOGFILE, '');
ini_set("error_log",$LOGFILE);

function logfile($text,$log = 0) {

    global $LOGFILE,$LOG_LEVEL;

    if(!$LOG_LEVEL || $LOG_LEVEL < $log)
        return false;

    file_put_contents($LOGFILE, "\n[ ".date('Y:m:d H:i:s')." ] $text \n\n", FILE_APPEND );

}

function getTime($path){
    clearstatcache($path);
    $dateUnix = shell_exec('stat --format "%y" '.$path);
    $date = explode(".", $dateUnix);
    return (int)filemtime($path).".".substr($date[1], 0, 8);
}

function reconnect() {
    GLOBAL $DB, $DBHOST , $DBUSER, $DBPASS, $DATABASE;

    if(!$DB->ping()) {
        unset($DB);
        while(true) {
            $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
            if($DB->connect_errno > 0) { sleep(5); }
            else break;
        }
    }
}


$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.stream_logs' ");
$LOG_LEVEL=(int)$r->fetch_assoc()['value'];

$r=$DB->query("SELECT value FROM settings  WHERE name = 'iptv.EpgChannelBuilded'");
if(!$BUILD_EPG=$r->fetch_assoc()['value'])$BUILD_EPG=0;


$r=$DB->query("SELECT id,source_server_id FROM iptv_streaming 
                           WHERE server_id = $SERVER_ID AND chan_id = $StreamID ");
if(!$row_streaming=$r->fetch_assoc()) die;
$STREAMING_ID = $row_streaming['id'];
$Streaming = new Streaming($STREAMING_ID);

//GETTING SOURCES
$r = $DB->query("SELECT id,source,url,priority,stream,stream_options,is_backup,loader FROM iptv_channels_sources 
                 WHERE chan_id=$StreamID and enable = 1  ORDER BY is_backup,priority ASC ");
$SOURCES = $r->fetch_all(MYSQLI_ASSOC);


$r=$DB->query("SELECT stream_profile,source_type,ondemand,type,logo,event_id
    FROM iptv_channels WHERE id = $StreamID ");
$channel_info=$r->fetch_assoc();
$stream_profile = $channel_info['stream_profile'];
$isEventType = (bool)$channel_info['type'] == \ACES2\IPTV\Stream::CHANNEL_TYPE_EVENT && $channel_info['event_id'] > 0;
$eventStartTime = 0;        //TIME WHEN EVENT START
$eventEndTime = 0;          //TIME WHEN EVENT FINISH
$eventCheckApi = 0;         //INTERVAL TO CHECK API.
$isEventFinished = false;
$EventStream = new \ACES2\IPTV\StreamEvent(null);
if($isEventType) {
    $EventStream = new \ACES2\IPTV\StreamEvent($channel_info['event_id']);

    //THE PRE STREAM IS DISABLED WE WILL NOT STREAM PRE/POST MESSAGE EVENT.
    if(!$EventStream->getPreStream())
        $isEventType = false;
    else {
        $r_event = $DB->query("SELECT start_datetime,end_datetime,sk_id 
            FROM iptv_stream_events WHERE stream_id = '$StreamID' ");
        $row_event = $r_event->fetch_assoc();
        $eventStartTime = strtotime($row_event['start_datetime']);
        $eventEndTime = strtotime($row_event['end_datetime']);
        $eventSkID = $row_event['sk_id'];
    }

}


$r=$DB->query("SELECT * FROM iptv_stream_options WHERE id = $stream_profile " );
$stream_opt = $r->fetch_assoc();

$adaptive='';
$stream_opt['adaptive_opt'] = json_decode($stream_opt['adaptive_opt'],1);
foreach(@$stream_opt['adaptive_opt'] as $i => $v ) {
    $adaptive .= " -vf scale=w={$v['screen_width']}:h={$v['screen_height']}:force_original_aspect_ratio=decrease -c:a {$v['audio_codec']}  -c:v {$v['video_codec']}  -hls_time 4 -hls_playlist_type live  -b:v {$v['video_bitrate']}k -maxrate 856k -bufsize 1200k -b:a {$v['audio_bitrate']}k -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list {$FOLDER}{$StreamID}_$i-.m3u8 {$FOLDER}/{$StreamID}_$i-%01d.ts ";
}

$pre_args = '';$post_args = '';
if(!empty($stream_opt['pre_args'])) $pre_args = $stream_opt['pre_args'];
if(!empty($stream_opt['post_args'])) $post_args = $stream_opt['post_args'];

//ADDING LOOP IF USING VIDEO AS SOURCES.
//if($channel_info['source_type'] == 1 ) $pre_args .= " -loop -1 ";

$audio_bitrate = '';
if(!empty($stream_opt['audio_bitrate_kbps'])) $audio_bitrate = " -b:a {$stream_opt['audio_bitrate_kbps']}k";

$video_bitrate = '';
if(!empty($stream_opt['video_bitrate_kbps'])) $video_bitrate = " -b:v {$stream_opt['video_bitrate_kbps']}k";

$screen_size = '';
if(!empty($stream_opt['screen_size'])) {
    if($stream_opt['video_codec'] == 'h264_nvenc') { $stream_opt['screen_size']=str_replace("x",":",$stream_opt['screen_size']);  $screen_size = " -vf scale_cuda=".$stream_opt['screen_size']; }
    else $screen_size = "-s {$stream_opt['screen_size']}";
}

$framerate = '';
if(!empty($stream_opt['framerate'])) $frameate = "-r {$stream_opt['framerate']}";

$threads = '';
if(!empty($stream_opt['threads'])) $threads = "-threads {$stream_opt['threads']}";

$preset = '';
if(!empty($stream_opt['preset'])) $preset = "-preset {$stream_opt['preset']}";

$user_agent = "";
if(!empty($stream_opt['user_agent'])) $user_agent = " -user_agent \"{$stream_opt['user_agent']}\" ";


$stream_opt['video_codec'] = (!$stream_opt['video_codec']) ? 'copy' : $stream_opt['video_codec'];
$stream_opt['audio_codec'] = (!$stream_opt['audio_codec']) ? 'copy' : $stream_opt['audio_codec'];

$probesize = $stream_opt['probesize'] != 0 ? " -probesize {$stream_opt['probesize']} " : '' ;
$analyzeduration = $stream_opt['analyzeduration'] != 0 ? " -analyzeduration {$stream_opt['analyzeduration']} " : '';

$pre_opt = " -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 ";
if($stream_opt['gen_pts']) {
    $pre_opt = "  -fflags +genpts -async 1  ";
}

//GPU
if($stream_opt['video_codec'] == 'h264_nvenc')
    $pre_opt .= " -hwaccel cuda -hwaccel_output_format cuda ";



$maps = "";
if($stream_opt['stream_all']) $maps .= " -map 0 ";
if($stream_opt['skip_no_audio']) $maps .= " -map a ";
if($stream_opt['skip_no_video']) $maps .= " -map v ";

$timeout=(int)$stream_opt['timeout'];


$COMMAND = "ffmpeg -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration $pre_args %{headers} %{input} $post_args -sc_threshold 0 $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs} -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6 -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";


logfile("PROCESS START");
$StreamStats = new StreamStats($StreamID, $SERVER_ID);

function killFFMPEG() {
    global $StreamID;
    exec("ps -eAf  | grep /stream_tmp/{$StreamID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
    foreach($ff_pids as $fpid) exec("kill -9 $fpid");
}

function clearOldChunks() {

    global $StreamID;

    $segments=[];
    foreach(glob("/home/aces/stream_tmp/$StreamID-*.ts") as $seg ) {
        $seg = str_replace("/home/aces/stream_tmp/",'', $seg);
        $segments[] =(int)explode("-",$seg)[1];
    }
    asort($segments,SORT_NUMERIC);
    foreach($segments as $s ) {
        $segment_file = "{$StreamID}-$s.ts";
        if (!exec('grep ' . $segment_file . " /home/aces/stream_tmp/{$StreamID}-.m3u8")) {
            unlink("/home/aces/stream_tmp/" . $segment_file);
        } else
            break;
    }
}

function eventStream() {

    global $StreamID, $DB, $STREAMING_ID, $FOLDER, $eventStartTime, $eventEndTime, $EventStream, $LOGFILE, $LOG_LEVEL, $Streaming;

    if(!$EventStream->getPreStream())
        return;

    $text_file = "/home/aces/tmp/pre_stream_{$StreamID}_text";
    $image_file = "/home/aces/tmp/image_file_{$StreamID}.jpg";

    if($eventStartTime > time() && $eventEndTime < time()) {
        logfile("EVENT STREAMING EXIT...");
        return;
    }

    killFFMPEG();

    $text = $eventEndTime < time()
        ? "Event Finished"
        : "Game Start At ".date( "g:ia T", $eventStartTime );

    $font_size = $EventStream->stream_font_size;
    $font_color = $EventStream->stream_font_color;
    $font = $EventStream->stream_font;
    $image = $EventStream->stream_image;

    file_put_contents($text_file, $text);

    file_put_contents($image_file, file_get_contents($EventStream->stream_image));

    $command = "ffmpeg -re -loop 1 -stream_loop 1 ";
    $command .= " -i '{$image_file}' -r 10 -c:v libx264 -s 1080x720 -crf 12 -pix_fmt yuv420p -preset ultrafast -b 124000 -threads 1" ;
    $command .= " -vf 'drawtext=fontsize={$font_size}:fontcolor={$font_color}:";
    $command .= "fontfile={$font}:textfile={$text_file}:reload=1:x=(w-text_w)/2:y=(h-text_h)/1' ";
    $command .= "  -f segment -segment_format mpegts -segment_time 5 -segment_list_size 10 ";
    $command .= " -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live ";
    $command .= " -segment_list %{list} %{file} ";

    $cmd = str_replace("%{list}","$FOLDER$StreamID-.m3u8", $command  );
    $cmd = str_replace("%{file}","$FOLDER/$StreamID-%01d.ts",$cmd );
    $cmd = str_replace("%{log}",'verbose',$cmd );

    if($LOG_LEVEL > 3)
        $ffmpeg_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $cmd,$LOGFILE )));
    else $ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $cmd )));
    sleep(5);

    $DB->query("UPDATE iptv_streaming SET status = 1, source_id = '0', connection_speed = 1,
                  video_codec = 'h264', audio_codec = 'None',  streaming = 'MESSAGE', fps = '10',
                  bitrate = '0',  connected_datetime=NOW() 
              WHERE id=$STREAMING_ID ");


    while($eventStartTime > time() || $eventEndTime < time()) {

        //FAIL TO RUN COMMAND RE RUN THE FUNCTION.
        if(!posix_getpgid($ffmpeg_pid))
            eventStream();

        sleep(60);
        $diff = $eventStartTime - time();
        if($diff > 0 && $diff < 3600 ) {
            $text = "Game start in ". round(($eventStartTime - time()) / 60, 0) . " minutes";
            file_put_contents($text_file, $text);
        }
        clearOldChunks();
    }

    unlink($text_file);
    unlink($image_file);

    exec("kill -9 $ffmpeg_pid");

}

$build=0;$build_pid=0;$epg=[];$first_to_build=0;
if($channel_info['type'] == 1 && !$row_streaming['source_server_id'] ) {

    logfile("247");

    //24/7 CHANNEL

    //$r=$DB->query("SELECT id,status FROM iptv_channel_files WHERE channel_id = '$StreamID' AND status != -1 ORDER BY ordering ASC ");
    $r=$DB->query("SELECT id,status,file_id FROM iptv_channel_files WHERE channel_id = '$StreamID' ORDER BY ordering ASC ");
    while($row=mysqli_fetch_assoc($r)) {

        if($row['status'] != 2 || $row['status'] != 4 )$build=1;
        $playlist[] = "/home/aces/channel_files/{$row['id']}.ts";
        if(!$first_to_build) $first_to_build = $row['id'];

        if($BUILD_EPG) {

            $r2=$DB->query("SELECT o.name,o.about,o.runtime_seconds,e.title,e.about as episode_about,e.number as episode_number,s.number as season_number, f.duration,f.movie_id,f.episode_id FROM iptv_video_files f  "
                . " LEFT JOIN iptv_series_season_episodes e ON e.id = f.episode_id"
                . " LEFT JOIN iptv_series_seasons s ON s.id = e.season_id "
                . " LEFT JOIN iptv_ondemand o ON o.id = f.movie_id OR s.series_id = o.id "
                . " WHERE f.id = {$row['file_id']} AND f.duration > 0 ");

            while($row_epg=$r2->fetch_assoc()) {

                $name='';$about='';
                if($row_epg['movie_id']) { $name = $DB->escape_string($row_epg['name']); $about = $DB->escape_string($row_epg['about']); }
                else { $name = $DB->escape_string("{$row_epg['name']} S{$row_epg['season_number']} E{$row_epg['episode_number']} {$row_epg['title']}"); $about = $DB->escape_string($row_epg['episode_about']); }

                $name = str_replace('&','&amp;',$name);
                $about = str_replace('&','&amp;',$about);

                $epg[] = array('name'=>$name,'about'=>$about,'runtime'=>$row_epg['duration']);
                break;

            }
        }

    }


    if($build) {
        logfile("BUILDING FILES", L_VERBOSE);
        //$build_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', "php /home/aces/bin/aces_build_channel.php $StreamID" ,$LOGFILE )));
        exec( "php /home/aces/bin/aces_build_channel.php $StreamID- > /dev/null &");
    }

    //WAITING FOR FIRST FILE TO FINISHED ENCODE.
    while(true) {
        $r=$DB->query("SELECT status FROM iptv_channel_files WHERE id = $first_to_build ");
        if($r->fetch_assoc()['status'] == 2) break;
        sleep(10);
    }

    if(!file_put_contents("/home/aces/channel_files/$StreamID-.list","\nfile '".implode("'\nfile '",$playlist)."'")) {
        logfile("Unable to write list.", L_ERROR);

        die; }



    //$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 -re -probesize 5000000 -analyzeduration 5000000  -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$StreamID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

    $COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 -re -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$StreamID-.list' -c:v copy -c:a copy -c:s copy -map 0  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6  -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";
    //WITH GENPTS
    $COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -fflags +genpts -async 1 -re -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$StreamID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6  -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

    //$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +genpts -async 1 -re  -stream_loop -1 -safe 0 -f concat -i '/home/aces/channel_files/$StreamID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";


    $r3=$DB->query("SELECT UNIX_TIMESTAMP() as time ");
    $ctime = $r3->fetch_assoc()['time'];
    $endtime=$ctime+(86430*30);

    if(count($epg)>0) {
        $DB->query("DELETE FROM iptv_epg WHERE chan_id = '$StreamID' ");
        while($ctime < $endtime ) {

            foreach($epg as $e ) {

                $end = $ctime+$e['runtime'];

                if(!empty($e['name']))
                    $DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date,start_time,end_time) "
                        ." VALUES('$StreamID', 'aces-channel-$StreamID', '{$e['name']}','{$e['about']}', FROM_UNIXTIME( $ctime ),FROM_UNIXTIME( $end ),'$ctime', '$end' ) ");

                $ctime = $ctime + $e['runtime'];

            }

        }
    }

    unset($row,$row_epg);

} else if($channel_info['type'] == 2 ) {

    //EVENT STREAM
    logfile("STREAM EVENT", L_VERBOSE);
    eventStream();

}


if($timeout) $COMMAND = "timeout {$timeout}h ".$COMMAND;

killFFMPEG();



$sTime = time();
$DB->query("UPDATE iptv_streaming SET total_down_seconds = 0, start_time = $sTime, disconnected_datetime = NOW(), 
              status = ".Streaming::STATUS_CONNECTING.", 
              reconnected = -1, connected_datetime = '00-00-00 00:00:00' WHERE id=$STREAMING_ID ");


$DOWN_TIME = time();
$i=0;$skipped=0;$source_back_on = null;
$last_backup_check = 0;


while(true) {

    //IS LB
    if($row_streaming['source_server_id'] != 0 ) {

        $rs = $DB->query("SELECT id,address,port FROM iptv_servers WHERE id = {$row_streaming['source_server_id']} ");
        $source_server = $rs->fetch_assoc();

        $rs = $DB->query("SELECT address,name FROM iptv_servers WHERE id = $SERVER_ID");
        $this_server = $rs->fetch_assoc();

        $token = md5(rand(1, 99999) . time() . time() . rand(1, 999999999));

        //CLEARING OLD CONNECTIONS FROM THIS SERVER.
        $DB->query("DELETE FROM iptv_access WHERE chan_id = '$StreamID' AND ip_address = '{$this_server['address']}' ");

        $DB->query("INSERT INTO iptv_access (device_id,chan_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
                            VALUES('0','$StreamID','{$row_streaming['source_server_id']}','{$source_server['address']}','$token','{$this_server['address']}','','MPEGTS',NOW() + interval 1 YEAR,NOW(),NOW())");

        $aid = $DB->insert_id;

        $b = base64_encode("$aid:$token");

        //TODO https for server with only ssl port
        $u = "http://{$source_server['address']}:{$source_server['port']}/stream/$aid/$token/$StreamID-.mpegts";

        //CLEARING SOURCES.
        $SOURCES = [];
        $SOURCES[] = array('url' => $u);

    }


    $s = $SOURCES[$i];
    $loader_post_argv='';
    $proxy='';$headers = '';
    if($stream_opt['proxy']) $proxy = " export http_proxy={$stream_opt['proxy']} ; ";


    //RTMP_PUSH
    if($channel_info['source_type'] == 1 ) {

        $url = "/home/aces/rtmp_push/$StreamID.m3u8";

    } else if(!empty($SOURCES[$i]['loader']) && is_file("/home/aces/loaders/{$SOURCES[$i]['loader']}/{$SOURCES[$i]['loader']}.php")) {

        $j=json_decode(exec("timeout 1m php /home/aces/loaders/{$SOURCES[$i]['loader']}/{$SOURCES[$i]['loader']}.php {$SOURCES[$i]['url']}  "),1);

        if(!empty($j['location'] ))$url = $j['location'];
        else $url = $j['url'];

        if($j['proxy'])  $proxy = " export http_proxy='{$j['proxy']}' ; ";
        if($j['headers']) $headers = " -headers '{$j['headers']}' ";
        if($j['post_argv']) $loader_post_argv = $j['post_argv'];


    } else {  $url = trim($SOURCES[$i]['url']); }


    $site_video=false;
    $sites[] = 'https://www.youtube.com';
    $sites[] = 'https://www.redtube.com';
    foreach($sites as $site ) {
        if(strpos($url,$site) !== false ) { $site_video = true; break; }
    }

    if( $site_video === false ) {
        $cmd = $COMMAND;
        //I DONT KNOW WHY THIS BUT I THINK IS TO FIX SOURCE OF A LOADER ????delete
        if(is_array($url)) { $input ="  -i \"".implode(" -i ",$url)."\""; }
        else $input = "-i \"$url\" ";

    } else {
        // ARGUMENT -4 TO FORCE IPTV4 MAY YOUTUBE LIVE STREAM.
        $cmd = "youtube-dlc -f best -o  - '$url' | $COMMAND ";
        $input = "-re -i - ";
        logfile("YDL SITE ", L_DEBUG);
    }


    $cmd = str_replace("%{input}", " $input ",$cmd );
    $cmd = str_replace("%{list}","$FOLDER$StreamID-.m3u8",$cmd );
    $cmd = str_replace("%{file}","$FOLDER/$StreamID-%01d.ts",$cmd );
    $cmd = str_replace("%{i}",@$start_number,$cmd );
    $cmd = str_replace("%{headers}",$headers,$cmd );
    $cmd = str_replace("%{loader_post_argvs}",$loader_post_argv,$cmd );

    //if($LOG_LEVEL > 2 ) $cmd = str_replace("%{log}",' debug ',$cmd );
    if($LOG_LEVEL > 2 ) $cmd = str_replace("%{log}",' verbose ',$cmd );
    else if($LOG_LEVEL > 0 ) $cmd = str_replace("%{log}",' error ',$cmd );
    else $cmd = str_replace("%{log}",' quiet ',$cmd );


    logfile("COMMAND : $cmd ", L_VERBOSE);

    $start = time();

    logfile("CONNECTING TO $url ", L_VERBOSE);

    $DB->query("UPDATE iptv_streaming SET status = 0, streaming = '$url' WHERE id=$STREAMING_ID ");
    exec(" rm -rf /home/aces/stream_tmp/$StreamID-*");
    exec(" rm -rf /home/aces/stream_tmp/{$StreamID}_*");


    if($row_streaming['source_server_id'] != 0 )  {
        //THIS SERVER IS ACTING AS LOAD BALANCE LET WAIT UNTIL SOURCE SERVER CONNECTED TO SOURCE.
        while(true) {
            $rms=$DB->query("SELECT id FROM iptv_streaming 
                WHERE status = 1 AND chan_id = '$StreamID' AND server_id = '{$row_streaming['source_server_id']}' ");
            if($rms->fetch_assoc()) break;
            sleep(2);
        }

    }

    //if( $SOURCES[$i]['is_backup'] == 1 || $channel_info['ondemand'] ) {
    if(true) {

        $StreamStats->setConnecting();

        if($LOG_LEVEL > 3) $ffmpeg_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $proxy.$cmd,$LOGFILE )));
        else $ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $proxy.$cmd )));


        sleep(1);

        //GETTING CODEC INFO
        $CONNECTED=0;$y=0;
        while($y<30) {

            if(!posix_getpgid($ffmpeg_pid)) break;
            clearstatcache();

            $chunk = exec("tail -1 /home/aces/stream_tmp/$StreamID-.m3u8 ");

            if(!empty($chunk)) {

                //$stream_v_info = json_decode(shell_exec(" ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk ",true));
                $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk " ),true);
                $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name /home/aces/stream_tmp/$chunk " ),true);
                $bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/stream_tmp/$chunk  " );

                $s_info = array();
                $s_info['video_codec'] = $stream_v_info['streams'][0]['codec_name'];
                $s_info['audio_codec'] = $stream_a_info['streams'][0]['codec_name'];
                $s_info['resolution'] = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
                try {
                    $s_info['frames'] = round( (int) explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[0] /
                        explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[1],0) ;
                } catch (DivisionByZeroError $exp ) {
                    $s_info['frames'] = 0;
                }

                $frames = (int)$s_info['frames'];
                $s_info = serialize($s_info);

                //RESET DOWNTIME;
                $DB->query("UPDATE iptv_channels set down_since = 0 WHERE id = $StreamID ");
                $DOWN_TIME=0;

                $DB->query("UPDATE iptv_streaming SET status = 1, source_id = '{$s['id']}', connection_speed = 1,
                      video_codec = '{$stream_v_info['streams'][0]['codec_name']}', audio_codec = '{$stream_a_info['streams'][0]['codec_name']}', 
                      `bitrate` = '$bitrate', fps='$frames', info = '$s_info', reconnected = reconnected + 1, connected_datetime=NOW() 
                  WHERE id=$STREAMING_ID ");

                logfile("CONNECTED $url", L_VERBOSE );
                $StreamStats->setStreaming($url);
                $CONNECTED=1;

                break;

            }

            sleep(1);
            $y++;

        }


        $startTime = 0;
        $prevChunk ='';
        $currentChunk = "$StreamID-0.ts";
        $totalStreamTime = 0.0000; //TIME OF ALL CHUNKS.
        $chunks = 0;               //COUNT OF CHUNKS.
        $speed = 0;

        $bufferingTime = 0;
        $bufferingRecover = 0;
        $bufferResetNext = false;

        while($CONNECTED) {

            if(!posix_getpgid($ffmpeg_pid))
                break;

            //EVENT HAVE FINISHED.
            if($isEventType) {

                if( time() > $eventEndTime )
                    eventStream();

                if(!$isEventFinished && $eventCheckApi < time()) {

                    if( Event::getEventState($eventSkID) == 'ended' ) {
                        logfile("EVENT ENDED");
                        $eventEndTime = time() + 60 * 20;       //ADDING 20 MINUTES AFTER THE EVENT FINISHED.
                        $isEventFinished = true;                // WHE DONT NEED TO CHECK IF HAD FINISHED ANYMORE.
                    }
                    $eventCheckApi = time() + 60;
                }

            }


            clearstatcache();
            sleep(10);

            $lines = null;
            exec("grep -B 1 $currentChunk '/home/aces/stream_tmp/$StreamID-.m3u8'", $lines, $status);
            if( $status == 0  ){

                //START COUNTING TIME AFTER FIRST CHUNK.
                if(!$startTime)
                    $startTime = microtime(true);

                $totalTime = microtime(true) - $startTime;
                $cacheTime = $totalStreamTime - $totalTime;
                $currentChunkTime = (float)explode(":",$lines[0])[1];
                $totalStreamTime += $currentChunkTime;


                if($prevChunk) {

                    $timeToDownload = getTime("/home/aces/stream_tmp/$currentChunk")
                        - getTime("/home/aces/stream_tmp/$prevChunk");

                    try {
                        $diff = $currentChunkTime / $timeToDownload;

                        if( $diff < .95  ) {
                            $bufferResetNext = false;
                            $bufferingTime += $timeToDownload - $currentChunkTime;
                            //logfile("\nBUFFER  $bufferingTime");

                        } else if( $bufferingTime > 0 ) {

                            $bufferingTime += $timeToDownload - $currentChunkTime;

                            if($bufferResetNext) {
                                $bufferingTime = 0;
                                logfile("RESET BUFFER");
                            }

                            $bufferResetNext = true;

                            //$bufferingTime = $timeToDownload - $currentChunkTime;
                            //logfile("\nRECOVERING BUFFERING $bufferingTime");
                            //A LITTLE HACK. IF THIS REACH 2 TIMES BUFFER WILL BE RESET.
                            //$bufferingTime = $bufferingTime < 1 ? 0 : 5;
                        }

                        if($cacheTime > 0 || $bufferingTime < 1 )
                            $speed = Streaming::SPEED_EXCELENT;
                        else if( $bufferingTime < 6 )
                            $speed = Streaming::SPEED_FAST;
                        else if($bufferingTime  < 12 )
                            $speed = Streaming::SPEED_SLOW;
                        else if($bufferingTime  < 16)
                            $speed = Streaming::SPEED_VERY_SLOW;
                        else
                            $speed = Streaming::SPEED_NOTHING;
//                        else
//                            $speed = match(true) {
//                                $diff > .94 => Streaming::SPEED_EXCELENT,
//                                $diff > .90 => Streaming::SPEED_FAST,
//                                $diff > .80 => Streaming::SPEED_SLOW,
//                                $diff > .70 => Streaming::SPEED_VERY_SLOW,
//                                default => Streaming::SPEED_NOTHING,
//                            };


                    } catch(DivisionByZeroError $e ){
                        $speed = 3;
                    }

                }

                //logfile("\nChunk:$currentChunk\nCacheTime: $cacheTime\nSpeed: $diff\nChunkTime: $currentChunkTime\nDownload Time: $timeToDownload\nBuffer: $bufferingTime", L_VERBOSE );


                $chunks++;
                $prevChunk = $currentChunk;
                $currentChunk = "$StreamID-{$chunks}.ts";

                //UPDATING BITRATE OF STREAM.
                $bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/stream_tmp/$currentChunk ");

                $DB->query("UPDATE iptv_streaming SET connection_speed = '$speed',  cache_time = '$cacheTime', 
                          buffer = '$bufferingTime', bitrate = '$bitrate'
                  WHERE id = $STREAMING_ID ");
            }


//            $PlaylistTime = filemtime("/home/aces/stream_tmp/$StreamID-.m3u8");
//            $RunningTime = 0;
//            $TotalTime = 0;
//            $BufferTime=0;
//            $Chunks = 0;

//            $CurrentPlaylistTime = (int)filemtime("/home/aces/stream_tmp/$StreamID-.m3u8");
//            if($CurrentPlaylistTime > $PlaylistTime) {
//
//                $Chunks++;
//
//                $diff_time = ($CurrentPlaylistTime - $PlaylistTime );
//                $RunningTime += $CurrentPlaylistTime - $PlaylistTime;
//                $TotalTime = $Chunks * 10;
//                $Buffer = ($TotalTime - $RunningTime) + 10 ;
//
//                //BUFFERING START.
//                //NOTE! 15 OR MORE CLIENT START BUFFERING.
//                if($BufferTime == 0 && $diff_time > 14 )
//                    $BufferTime=time();
//
//                //BUFFER END.
//                if($diff_time < 10 )
//                    $BufferTime = 0;
//
//                $speed = match(true) {
//                    $diff_time < 12 => 1,
//                    $diff_time < 14 => 2,
//                    $diff_time < 15 => 3,
//                    $diff_time < 17 => 4,
//                    default => 5,
//                };
//
//                $PlaylistTime = filemtime("/home/aces/stream_tmp/$StreamID-.m3u8");
//                $DB->query("UPDATE iptv_streaming SET connection_speed = '$speed', buffer = '$Buffer'
//                  WHERE id = $STREAMING_ID ");
//            }




            //PLAYLIST NOT UPDATED FOR A MINUTE.
            $CurrentPlaylistTime = (int)filemtime("/home/aces/stream_tmp/$StreamID-.m3u8");
            if( ($CurrentPlaylistTime + 60 )  < time() ) {
                logfile('STREAM STOP, NOT RECEIVING DATA.',L_VERBOSE);
                break;
            }


            //CHECKING IF STREAMING ROW IS ON DATABASE WE MOVING THE RECONNECTION TO HERE.
            if(!$DB->ping()) {
                unset($DB);
                while(true) {
                    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                    if($DB->connect_errno > 0) { sleep(5); }
                    else break;
                }
            }


            //REMOVING OLD SEGMENT WHICH ARE NOT IN PLAYLIST... NOT USING -segment_wrap ANYMORE...
            clearOldChunks();

            $r_streaming=$DB->query("SELECT id FROM iptv_streaming 
                WHERE id = $STREAMING_ID  ");
            if(!$r_streaming->fetch_assoc()) {
                exec("ps -eAf  | grep /stream_tmp/{$StreamID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
                $StreamStats->setShutoff();
                foreach($ff_pids as $fpid) exec("kill -9 $fpid"); die;
            }

            //IF IS ONDEMAND, LB CONNECTED TO SERVER AND NO CLIENTS KILL IT.
            if($channel_info['ondemand'] && $row_streaming['source_server_id'] == 0  ) {

                $r_clients = $DB->query("SELECT id FROM iptv_access 
                    WHERE chan_id = $StreamID AND limit_time > NOW() AND device_id != 0  ");
                if($r_clients->num_rows < 1) {

                    $DB->query("UPDATE iptv_streaming SET source_id = 0, status = 2, streaming = '', connected_datetime='00-00-00 00:00:00', 
                      disconnected_datetime = NOW() WHERE id=$STREAMING_ID ");

                    exec("kill -9 $ffmpeg_pid ");
                    exec("ps -eAf  | grep /stream_tmp/{$StreamID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
                    foreach($ff_pids as $fpid) exec("kill -9 $fpid");

                    exec(" rm -rf /home/aces/stream_tmp/$StreamID-*");
                    logfile("NO CLIENTS. GOING TO STAND BY", L_VERBOSE );
                    $StreamStats->setStandBy();

                    die;
                }


            }

            if($SOURCES[$i]['is_backup'] == 1 && time() > $last_backup_check ) {
                //IF IS A BACKUP SOURCE LET CHECK IF A NONE BACK SOURCE IS BACK ON.

                $last_backup_check = time() + (60 * 10);

                $x=null;
                for($x=0;isset($SOURCES[$x]); $x++ ) {
                    if($SOURCES[$x]['is_backup'] == 0 ) {
                        $uptime = (int)exec(" timeout 10 ffprobe -v quiet -show_entries format=start_time -of default=noprint_wrappers=1:nokey=1 {$SOURCES[$x]['url']} ");
                        if( $uptime > 121 ) {
                            $source_back_on = $x;
                            exec("kill -9 $ffmpeg_pid ");

                            $DB->query("UPDATE iptv_streaming SET source_id = 0, status = 2, streaming = '', connected_datetime='00-00-00 00:00:00',
                                disconnected_datetime = NOW() WHERE id=$STREAMING_ID ");
                            break;
                        }

                    }

                }
                if(!is_null($source_back_on)) break;

            }

            //$DB->close();


        }

    }


    logfile("Cache Time $cacheTime");
    $StreamStats->setConnecting();

    if(posix_getpgid($ffmpeg_pid)) exec("kill -9 $ffmpeg_pid ");
    exec("ps -eAf  | grep /stream_tmp/{$StreamID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
    foreach($ff_pids as $fpid) exec("kill -9 $fpid");

    if(!$DB->ping()) {
        unset($DB);
        while(true) {
            $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
            if($DB->connect_errno > 0) { sleep(5); }
            else break;
        }
    }

    //WHY THIS IS ALSO HERE????
    $r_streaming=$DB->query("SELECT id FROM iptv_streaming 
          WHERE id = $STREAMING_ID AND server_id = $SERVER_ID AND chan_id = $StreamID ");
    if(!$r_streaming->fetch_assoc()) {
        logfile("NO STREAMING ROW IN DB, EXIT", L_VERBOSE);
        $StreamStats->setShutoff();
        die;
    }

    //A NONE BACKUP SOURCE HAVE CAME ONLINE LET CHANGE IT.
    if(!is_null($source_back_on)) {
        $i = $source_back_on; $source_back_on = null; }

    else if( (time() - $start) < 30 || !$CONNECTED ) {
        $i++; $skipped++;

    } else {

        $skipped = 0;
        $uptime = round( ((time()-$start)/60) );
        if( (time() - $start) > 120 ) {  //THIS MEAN THE STREAM WAS CONNECTED.
            logfile("STREAM LAST $uptime MINUTES ", L_DEBUG);
        }

        $DOWN_TIME=time();

        $DB->query("UPDATE iptv_streaming SET disconnected_datetime = NOW()  
                      WHERE id=$STREAMING_ID ");

        //RESET SOURCE TO CHECK IF A NO BACKUP SOURCE IS BACK LIVE.
        if($SOURCES[$i+1]['is_backup'] == 1) $i=0;

    }

    if($skipped >= count($SOURCES) ) {

        if($channel_info['ondemand']) {
            $StreamStats->setStandBy();
            $DB->query("UPDATE iptv_streaming SET status = 2 WHERE id=$STREAMING_ID ");
            exec(" rm -rf /home/aces/stream_tmp/$StreamID-*");
            logfile("ONDEMAND, NO CLIENT, GOING TO STAND BY", L_DEBUG);
            $StreamStats->setStandBy();
            die;
        }

        sleep(10);
        $skipped = 0; $i=0;
    }

}