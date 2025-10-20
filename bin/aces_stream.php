#!/usr/bin/env php
<?php

//error_reporting(0);

date_default_timezone_set('UTC');

include "/home/aces/stream/config.php";

$CHANID = (int)$argv[1];
if(!$CHANID) die;


if(is_file("/home/aces/run/aces_stream-$CHANID.pid")) die;
if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );
file_put_contents("/home/aces/run/aces_stream-$CHANID.pid",getmypid());

//CREATE LOG FOLDER IF NOT EXIST.
if(!is_dir("/home/aces/logs/streams/"))
    mkdir("/home/aces/logs/streams/",0777 );

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


unlink($LOGFILE);

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.stream_logs' ");
if(!$LOG=mysqli_fetch_array($r)['value'])$LOG=0;

$r=$DB->query("SELECT value FROM settings  WHERE name = 'iptv.EpgChannelBuilded'");
if(!$BUILD_EPG=mysqli_fetch_array($r)['value'])$BUILD_EPG=0;

$FFPROBE_TEST_STREAMS = 0;


$r=$DB->query("SELECT id,source_server_id FROM iptv_streaming WHERE server_id = $SERVER_ID AND chan_id = $CHANID ");
if(!$row_streaming=$r->fetch_assoc()) die;

$STREAMING_ID = $row_streaming['id'];
//if($row_streaming['source_server_id'] != 0 ) {
//
//    $rs=$DB->query("SELECT id,address,port FROM iptv_servers WHERE id = {$row_streaming['source_server_id']} ");
//    $source_server = mysqli_fetch_array($rs);
//
//    $rs=$DB->query("SELECT address,name FROM iptv_servers WHERE id = $SERVER_ID");
//    $this_server=mysqli_fetch_array($rs);
//
//    $token = md5(rand(1,99999).time().time().rand(1,999999999));
//
//    $DB->query("INSERT INTO iptv_access (device_id,chan_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) VALUES('0','$CHANID','{$row_streaming['source_server_id']}','{$source_server['address']}','$token','{$this_server['address']}','','MPEGTS',NOW() + interval 1 YEAR,NOW(),NOW())");
//    $aid = $DB->insert_id;
//
//    $b = base64_encode("$aid:$token");
//
//    $u = "http://{$source_server['address']}:{$source_server['port']}/stream/$aid/$token/$CHANID-.mpegts";
//
//    $SOURCES[] = array('url' => $u );
//
//} else {
//
//    //GETTING SOURCES
//    $r = $DB->query("SELECT id,source,url,priority,stream,stream_options,is_backup,loader FROM iptv_channels_sources WHERE chan_id=$CHANID and enable = 1  ORDER BY is_backup,priority ASC ");
//    while($row=$r->fetch_assoc()) $SOURCES[] = $row;
//
//}

//GETTING SOURCES
$r = $DB->query("SELECT id,source,url,priority,stream,stream_options,is_backup,loader FROM iptv_channels_sources 
                     WHERE chan_id=$CHANID and enable = 1  ORDER BY is_backup,priority ASC ");
while($row=$r->fetch_assoc()) $SOURCES[] = $row;

$r=$DB->query("SELECT stream_profile,source_type,ondemand,type FROM iptv_channels WHERE id = $CHANID ");
$channel_info=mysqli_fetch_array($r);
$stream_profile = $channel_info['stream_profile'];


$r=$DB->query("SELECT * FROM iptv_stream_options WHERE id = $stream_profile " );
$stream_opt = mysqli_fetch_array($r);

$adaptive='';
$stream_opt['adaptive_opt'] = json_decode($stream_opt['adaptive_opt'],1);
foreach($stream_opt['adaptive_opt'] as $i => $v ) {
    $adaptive .= " -vf scale=w={$v['screen_width']}:h={$v['screen_height']}:force_original_aspect_ratio=decrease -c:a {$v['audio_codec']}  -c:v {$v['video_codec']}  -hls_time 4 -hls_playlist_type live  -b:v {$v['video_bitrate']}k -maxrate 856k -bufsize 1200k -b:a {$v['audio_bitrate']}k -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list {$FOLDER}{$CHANID}_$i-.m3u8 {$FOLDER}/{$CHANID}_$i-%01d.ts ";
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


$pre_opt = " -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 ";
if($stream_opt['gen_pts']) {
    $pre_opt = "  -fflags +genpts -async 1  ";
}

//GPU
if($stream_opt['video_codec'] == 'h264_nvenc')
    $pre_opt .= " -hwaccel cuda -hwaccel_output_format cuda ";

$probesize = " "; $analyzeduration = " "; //-probesize 512000
if($stream_opt['probesize']) {
    $probesize = " -probesize {$stream_opt['probesize']} ";
    $analyzeduration = " -analyzeduration {$stream_opt['probesize']} ";
}

$maps = "";
if($stream_opt['stream_all']) $maps .= " -map 0 ";
if($stream_opt['skip_no_audio']) $maps .= " -map a ";
if($stream_opt['skip_no_video']) $maps .= " -map v ";

$timeout=$stream_opt['timeout'];

$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -fflags +igndts+ignidx+nobuffer+genpts -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0  $user_agent $pre_args %{input} -probesize 100000 -analyzeduration 100000  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -strict -2  -tune zerolatency  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 3 -segment_wrap 4 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";



//NOTES
//-fflags +igndts+ignidx+nobuffer+genpts
//+igndts FLAG CAN MAKE SOME STREAM DONT PLAY WELL, SLOW OR FASTFORWARD ALSO MAKE CLIENT PLAYER DROP.
// -sc_threshold 0 with genpts seems to fix the hls play on VLC linux.
// -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 

//LOWER LATENCY 
//PRE   -analyzeduration 0 -flags low_delay -fflags nobuffer
//POST -tune zerolatency

//TEST
//$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -start_at_zero -copyts  -avoid_negative_ts disabled  $user_agent $pre_args %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args    -tune zerolatency  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 3 -segment_wrap 4 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +igndts+ignidx+nobuffer+genpts -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0  $user_agent $pre_args  %{input} -probesize 100000 -analyzeduration 100000  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -strict -2  -tune zerolatency  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +igndts+ignidx+nobuffer+genpts -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0  $user_agent  -probesize 512000 -analyzeduration 0 $pre_args  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -strict -2  -tune zerolatency  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +nobuffer+genpts -copyts -start_at_zero   $user_agent  -probesize 512000  $pre_args  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -strict -2  -tune zerolatency  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

//FIX ERROR WITH TIMESTAMP
$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +genpts -async 1 -copyts -start_at_zero  $user_agent  -probesize 512000 -analyzeduration 5000000  $pre_args  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";


//NO GENPTS
$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0  $user_agent  -probesize 512000 -analyzeduration 5000000  $pre_args  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']}  $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

//DVR
//$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize -analyzeduration 5000000 $pre_args %{headers}  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -f hls -hls_allow_cache 1 -hls_playlist_type event -hls_time 10 -hls_list_size 60 -hls_wrap 61  %{list} %{file} ";

//DVR SEGMENT
$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize -analyzeduration 5000000 $pre_args %{headers}  %{input}  -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -f segment  -segment_format mpegts  -segment_time 10 -segment_list_size 30 -segment_wrap 61 -segment_list_type m3u8 -segment_list_flags +live -segment_list %{list} %{file} ";


//SEGMENT TEST
//$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize -analyzeduration 0  $pre_args %{headers} -fflags nobuffer -flags low_delay %{input} $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  $post_args  -f segment -segment_format mpegts -segment_time 5 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

//HLS TEST
//$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize -analyzeduration 0  $pre_args %{headers}  %{input} $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}   $post_args -f hls -hls_allow_cache 1 -hls_playlist_type event -hls_init_time 5 -hls_time 10 -hls_list_size 10 -hls_wrap 5 %{list} %{file} ";




//CURRENT
$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration  $pre_args %{headers}  %{input} $post_args  $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}   -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";
$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration  $pre_args %{headers}  %{input} $post_args  $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}   -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6 -segment_wrap 7 -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

//XCFFMPG
//$COMMAND = "/home/aces/bin/ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration  $pre_args %{headers} %{input} $post_args  $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs}  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 8 -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live+delete  -segment_list %{list} %{file} ";

$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration $pre_args %{headers} %{input} $post_args  $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs} -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6 -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";




//$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err $pre_opt $user_agent $probesize $analyzeduration  $pre_args %{headers}  %{input} $post_args  $adaptive -c:v {$stream_opt['video_codec']} -c:a {$stream_opt['audio_codec']} $maps $video_bitrate $audio_bitrate $screen_size $frameate $threads $preset %{loader_post_argvs} -ldash 1 -tune zerolatency  -f hls -hls_flags delete_segments  -hls_time 4 -hls_list_size 8   %{list} %{file} ";


logfile('PROCESS START...');

$build=0;$build_pid=0;$epg=[];$first_to_build=0;
if($channel_info['type'] == 1 && !$row_streaming['source_server_id'] ) {

    //24/7 CHANNEL

    //$r=$DB->query("SELECT id,status FROM iptv_channel_files WHERE channel_id = '$CHANID' AND status != -1 ORDER BY ordering ASC ");
    $r=$DB->query("SELECT id,status,file_id FROM iptv_channel_files WHERE channel_id = '$CHANID' ORDER BY ordering ASC ");
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


    if($build) {  logfile("BUILDING FILES ");
        //$build_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', "php /home/aces/bin/aces_build_channel.php $CHANID" ,$LOGFILE )));
        exec( "php /home/aces/bin/aces_build_channel.php $CHANID- > /dev/null &");
    }

    //WAITING FOR FIRST FILE TO FINISHED ENCODE.
    while(true) {
        $r=$DB->query("SELECT status FROM iptv_channel_files WHERE id = $first_to_build ");
        if($r->fetch_assoc()['status'] == 2) break;
        sleep(10);
    }

    if(!file_put_contents("/home/aces/channel_files/$CHANID-.list","\nfile '".implode("'\nfile '",$playlist)."'")) {
        logfile("Unable to write list."); die; }



    //$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 -re -probesize 5000000 -analyzeduration 5000000  -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$CHANID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

    $COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 -re -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$CHANID-.list' -c:v copy -c:a copy -c:s copy -map 0  -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6  -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";
    //WITH GENPTS
    $COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err -fflags +genpts -async 1 -re -stream_loop -1 -safe 0  -f concat -i '/home/aces/channel_files/$CHANID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 6  -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";

    //$COMMAND = "ffmpeg  -y -loglevel %{log} -nostdin -nostats -hide_banner -err_detect ignore_err  -fflags +genpts -async 1 -re  -stream_loop -1 -safe 0 -f concat -i '/home/aces/channel_files/$CHANID-.list' -c:v copy -c:a copy -c:s copy -map 0 -f segment -segment_format mpegts -segment_time 10 -segment_list_size 4 -segment_wrap 5 -segment_list_type m3u8 -segment_list_flags +live  -segment_list %{list} %{file} ";


    $r3=$DB->query("SELECT UNIX_TIMESTAMP() as time ");
    $ctime = $r3->fetch_assoc()['time'];
    $endtime=$ctime+(86430*30);

    if(count($epg)>0) {
        $DB->query("DELETE FROM iptv_epg WHERE chan_id = '$CHANID' ");
        while($ctime < $endtime ) {

            foreach($epg as $e ) {

                $end = $ctime+$e['runtime'];

                if(!empty($e['name']))
                    $DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date,start_time,end_time) "
                        ." VALUES('$CHANID', 'aces-channel-$CHANID', '{$e['name']}','{$e['about']}', FROM_UNIXTIME( $ctime ),FROM_UNIXTIME( $end ),'$ctime', '$end' ) ");

                $ctime = $ctime + $e['runtime'];

            }

        }
    }

    unset($row,$row_epg);

}



if($timeout>0) $COMMAND = "timeout {$timeout}h ".$COMMAND;



####THIS........
#exec("kill -9  $(ps -eAf  | grep /$STREAMID-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");
#exec("kill -9  $(ps aux | grep '/$CHANID-.m3u8' | grep ffmpeg |  awk '{print $2}') 2>/dev/null ");
exec("ps -eAf  | grep /stream_tmp/{$CHANID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
foreach($ff_pids as $fpid) exec("kill -9 $fpid");

//UPDATING DOWN SINCE
//$DB->query("UPDATE iptv_channels set down_since = NOW() WHERE id = $CHANID ");

$sTime = time();
$DB->query("UPDATE iptv_streaming SET total_down_seconds = 0, start_time = $sTime, disconnected_datetime = NOW(), 
                  reconnected = -1, connected_datetime = '00-00-00 00:00:00' WHERE id=$STREAMING_ID ");

$DOWN_TIME = time();
$i=0;$skipped=0;$source_back_on = null;
$last_backup_check = 0;

while(true) {

    if($row_streaming['source_server_id'] != 0 ) {

        $rs = $DB->query("SELECT id,address,port FROM iptv_servers WHERE id = {$row_streaming['source_server_id']} ");
        $source_server = $rs->fetch_assoc();

        $rs = $DB->query("SELECT address,name FROM iptv_servers WHERE id = $SERVER_ID");
        $this_server = $rs->fetch_assoc();

        $token = md5(rand(1, 99999) . time() . time() . rand(1, 999999999));

        //CLEARING OLD CONNECTIONS FROM THIS SERVER.
        $DB->query("DELETE FROM iptv_access WHERE chan_id = '$CHANID' AND ip_address = '{$this_server['address']}' ");

        $DB->query("INSERT INTO iptv_access (device_id,chan_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
                            VALUES('0','$CHANID','{$row_streaming['source_server_id']}','{$source_server['address']}','$token','{$this_server['address']}','','MPEGTS',NOW() + interval 1 YEAR,NOW(),NOW())");

        $aid = $DB->insert_id;

        $b = base64_encode("$aid:$token");

        $u = "http://{$source_server['address']}:{$source_server['port']}/stream/$aid/$token/$CHANID-.mpegts";

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

        $url = "/home/aces/rtmp_push/$CHANID.m3u8";

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
    logfile("SITE ???");
    if( $site_video === false ) {
        $cmd = $COMMAND;
        //I DONT KNOW WHY THIS BUT I THINK IS TO FIX SOURCE OF A LOADER ????delete
        if(is_array($url)) { $input ="  -i \"".implode(" -i ",$url)."\""; }
        else $input = "-i \"$url\" ";

    } else {
        // ARGUMENT -4 TO FORCE IPTV4 MAY YOUTUBE LIVE STREAM.
        $cmd = "youtube-dlc -f best -o  - '$url' | $COMMAND ";
        $input = "-re -i - ";
        logfile("IS WEB");
    }


    $cmd = str_replace("%{input}", " $input ",$cmd );
    $cmd = str_replace("%{list}","$FOLDER$CHANID-.m3u8",$cmd );
    $cmd = str_replace("%{file}","$FOLDER/$CHANID-%01d.ts",$cmd );
    $cmd = str_replace("%{i}",@$start_number,$cmd );
    $cmd = str_replace("%{headers}",$headers,$cmd );
    $cmd = str_replace("%{loader_post_argvs}",$loader_post_argv,$cmd );

    //if($LOG > 2 ) $cmd = str_replace("%{log}",' debug ',$cmd );
    if($LOG > 2 ) $cmd = str_replace("%{log}",' verbose ',$cmd );
    else if($LOG > 0 ) $cmd = str_replace("%{log}",' error ',$cmd );
    else $cmd = str_replace("%{log}",' quiet ',$cmd );


    if($LOG > 4) logfile("COMMAND : $cmd ");
    logfile("COMMAND : $cmd ");

    $start = time();

    if($LOG > 1 ) logfile("CONNECTING TO $url");

    $DB->query("UPDATE iptv_streaming SET status = 0, streaming = '$url' WHERE id=$STREAMING_ID ");
    exec(" rm -rf /home/aces/stream_tmp/$CHANID-*");
    exec(" rm -rf /home/aces/stream_tmp/{$CHANID}_*");

    //TESTING SOURCE IF IS LIVE.
    if($FFPROBE_TEST_STREAMS == 0 || trim(shell_exec("timeout 10 ffprobe  -v quiet -headers \"Connection: close\" -probesize 100000 -analyzeduration 100000 '$url' > /dev/null 2>&1;  echo $?")) == 0 ){

        if($row_streaming['source_server_id'] != 0 )  {
            //THIS SERVER IS ACTING AS LOAD BALANCE LET WAIT UNTIL SOURCE SERVER CONNECTED TO SOURCE.
            while(true) {
                $rms=$DB->query("SELECT id FROM iptv_streaming WHERE status = 1 AND chan_id = '$CHANID' AND server_id = '{$row_streaming['source_server_id']}' ");
                if($rms->fetch_assoc()) break;
                sleep(2);
            }

        }

        //if( $SOURCES[$i]['is_backup'] == 1 || $channel_info['ondemand'] ) {
        if(true) {

            if($LOG > 0) $ffmpeg_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $proxy.$cmd,$LOGFILE )));
            else $ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $proxy.$cmd )));

            //logfile("FFMPEG PID ".$ffmpeg_pid);

            sleep(1);

            //GETTING CODEC INFO
            $CONNECTED=0;$y=0;
            while($y<30) {

                if(!posix_getpgid($ffmpeg_pid)) break;
                clearstatcache();

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

                    $DB->query("UPDATE iptv_streaming SET status = 1, source_id = '{$s['id']}', video_codec = '{$stream_v_info['streams'][0]['codec_name']}', audio_codec = '{$stream_a_info['streams'][0]['codec_name']}', `bitrate` = '$bitrate', info = '$s_info', reconnected = reconnected + 1, connected_datetime=NOW() WHERE id=$STREAMING_ID ");

                    //if($LOG > 1 )
                    logfile("CONNECTED $url" );
                    $CONNECTED=1;

                    break;

                }

                sleep(1);
                $y++;

            }

            //$DB->close();


            $INFO_SET = 0;$BUFF_COUNT=0; $fmtime=0;
            while($CONNECTED) {

                if(!posix_getpgid($ffmpeg_pid)) break;

                clearstatcache();
                sleep(10);

//                    if( $fmtime != filemtime("/home/aces/stream_tmp/$CHANID-.m3u8") ) {
//                        //PLAYLIST HAVE BEEN WRITED.
//
//                        $fmtime_diff = time()-$fmtime;
//                        if($fmtime_diff < 13 ) logfile("FAST $fmtime_diff ");
//                        else if($fmtime_diff < 15 ) logfile("GOOD $fmtime_diff ");
//                        else if($fmtime_diff < 19 ) logfile("SLOW $fmtime_diff ");
//                        else logfile("BUFFERING $fmtime_diff ");
//
//                        $fmtime = filemtime("/home/aces/stream_tmp/$CHANID-.m3u8");
//
//                    }


                //if( (fileatime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ) {
                if( (filemtime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ) {
                    //logfile('STREAM STOP, NOT RECIEVING DATA.');
                    //$DISC_REASON = 'STREAM STOP, NOT RECIEVING DATA.';
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


                //REMOVING OLD SEGMENT WHICH ARE NOT IN PLAYLIST.. ARE NOT USING -segment_wrap ANY MORE...
                $segments=[];
                foreach(glob("/home/aces/stream_tmp/$CHANID-*.ts") as $seg ) {
                    $seg = str_replace("/home/aces/stream_tmp/",'', $seg);
                    $segments[] =(int)explode("-",$seg)[1];
                }
                asort($segments,SORT_NUMERIC);
                foreach($segments as $s ) {
                    $segment_file = "{$CHANID}-$s.ts";
                    if (!exec('grep ' . $segment_file . " /home/aces/stream_tmp/{$CHANID}-.m3u8")) {
                        unlink("/home/aces/stream_tmp/" . $segment_file);
                    } else
                        break;
                }


                $r_streaming=$DB->query("SELECT id FROM iptv_streaming WHERE id = $STREAMING_ID AND server_id = $SERVER_ID AND chan_id = $CHANID ");
                if(!$r_streaming->fetch_assoc()) {  exec("ps -eAf  | grep /stream_tmp/{$CHANID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids); foreach($ff_pids as $fpid) exec("kill -9 $fpid"); die;  }

                //IF IS ONDEMAND, LB CONNECTED TO SERVER AND NO CLIENTS KILL IT.
                if($channel_info['ondemand'] && $row_streaming['source_server_id'] == 0  ) {

                    $r_clients = $DB->query("SELECT id FROM iptv_access WHERE chan_id = $CHANID and limit_time > NOW() and device_id != 0  ");
                    if($r_clients->num_rows < 1) {

                        $DB->query("UPDATE iptv_streaming SET source_id = 0, status = 2, streaming = '', connected_datetime='00-00-00 00:00:00', disconnected_datetime = NOW() WHERE id=$STREAMING_ID ");

                        exec("kill -9 $ffmpeg_pid ");
                        exec("ps -eAf  | grep /stream_tmp/{$CHANID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
                        foreach($ff_pids as $fpid) exec("kill -9 $fpid");

                        exec(" rm -rf /home/aces/stream_tmp/$CHANID-*");
                        //logfile("NO CLIENTS");
                        die;
                    }


                }

                if($SOURCES[$i]['is_backup'] == 1 && time() > $last_backup_check ) {
                    //IF IS A BACKUP SOURCE LET CHECK IF A NONE BACK SOURCE IS BACK ON.

                    $last_backup_check = time() + (60 * 10);

                    $x=null;
                    for($x=0;isset($SOURCES[$x]); $x++ ) {
                        if($SOURCES[$x]['is_backup'] == 0 ) {

                            if( round(exec(" timeout 10 ffprobe -v quiet -show_entries format=start_time -of default=noprint_wrappers=1:nokey=1 {$SOURCES[$x]['url']} ")) > 121 ) {
                                $source_back_on = $x;
                                exec("kill -9 $ffmpeg_pid ");

//                                    if(!$DB->ping()) {
//                                        $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
//                                        if($DB->connect_errno > 0) { logfile("FAIL TO RECONNECT TO DATABASE."); }
//                                    }

                                $DB->query("UPDATE iptv_streaming SET source_id = 0, status = 2, streaming = '', connected_datetime='00-00-00 00:00:00', disconnected_datetime = NOW() WHERE id=$STREAMING_ID ");
                                break;
                            }

                        }

                    }
                    if(!is_null($source_back_on)) break;

                }

                //$DB->close();

            }

        } else {
            $aces_sinfo_pid=null;
            $aces_sinfo_pid=shell_exec("php /home/aces/bin/aces_stream_info.php $CHANID- > /dev/null 2>&1 & echo $!" );
            $DB->close();
            exec ($proxy.$cmd);
            $DB->query("UPDATE iptv_streaming SET source_id = 0, status = 0, streaming = '', connected_datetime='00-00-00 00:00:00' WHERE id=$STREAMING_ID ");
            exec("kill 9 $aces_sinfo_pid");
        }
    }

    if(posix_getpgid($ffmpeg_pid)) exec("kill -9 $ffmpeg_pid ");
    exec("ps -eAf  | grep /stream_tmp/{$CHANID}- | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' ",$ff_pids);
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
          WHERE id = $STREAMING_ID AND server_id = $SERVER_ID AND chan_id = $CHANID ");
    if(!$r_streaming->fetch_assoc()) die;

    //A NONE BACKUP SOURCE HAVE CAME ONLINE LET CHANGE IT.
    if(!is_null($source_back_on)) {
        $i = $source_back_on; $source_back_on = null; }

    else if( (time() - $start) < 30 || !$CONNECTED ) {
        $i++; $skipped++;

    } else {

        $skipped = 0;
        $uptime = round( ((time()-$start)/60) );
        if( (time() - $start) > 120 ) {  //THIS MEAN THE STREAM WAS CONNECTED.
            if($LOG > 1 ) logfile(" STREAM LAST $uptime MINUTES ");
        }

        $DOWN_TIME=time();

        $DB->query("UPDATE iptv_streaming SET disconnected_datetime = NOW()  WHERE id=$STREAMING_ID ");

        //RESET SOURCE TO CHECK IF A NO BACKUP SOURCE IS BACK LIVE.
        if($SOURCES[$i+1]['is_backup'] == 1) $i=0;

    }

    if($skipped >= count($SOURCES) ) {

        if($channel_info['ondemand']) {
            $DB->query("UPDATE iptv_streaming SET status = 2 WHERE id=$STREAMING_ID ");
            exec(" rm -rf /home/aces/stream_tmp/$CHANID-*");
            die;
        }


        sleep(10);
        $skipped = 0; $i=0;
    }

}              
