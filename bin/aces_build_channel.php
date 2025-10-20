#!/usr/bin/env php
<?php

error_reporting(0);

include "/home/aces/stream/config.php";

$CHANID = (int)$argv[1];
if(!$CHANID) die('NO CHANNEL');

if(is_file("/home/aces/run/aces_build_channel-$CHANID.pid")) {
    
    $PID = file_get_contents("/home/aces/run/aces_build_channel-$CHANID.pid");
    if(posix_getpgid($PID)) { 
        die;
    }
    
}


if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );
if(!is_dir("/home/aces/channel_files/")) mkdir("/home/aces/channel_files/",0775 );
file_put_contents("/home/aces/run/aces_build_channel-$CHANID.pid",getmypid());

//CREATE LOG FOLDER IF NOT EXIST.
if(!is_dir("/home/aces/logs/streams/")) mkdir("/home/aces/logs/streams/",0777 );

$LOGFILE = "/home/aces/logs/streams/stream-$CHANID.log";


function logfile($text) {

    global $LOGFILE;

    file_put_contents($LOGFILE, "\n[ ".date('Y:m:d H:i:s')." ] $text \n\n", FILE_APPEND );

}

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$r=$DB->query("SELECT address FROM iptv_servers WHERE id = '$SERVER_ID'");
if(!$server_ip=mysqli_fetch_assoc($r)['address']) { logfile("COULD NOT GET SERVER IP ADDRESS "); exit; } 

$files=array();$playlist=array();
$r=$DB->query("SELECT cf.type as file_type,cf.val,cf.id,vf.id as file_id,vf.server_id, s.address as server_ip,s.port as server_port, vf.container, cf.status,vf.transcoding,source_file, c.build_gpu, c.build_codecs FROM iptv_channel_files cf"
        . " LEFT JOIN iptv_video_files vf ON vf.id = cf.file_id "
        . " LEFT JOIN iptv_servers s ON s.id = vf.server_id "
        . " LEFT JOIN iptv_channels c ON c.id = $CHANID "
        . " WHERE cf.channel_id = $CHANID ORDER BY cf.ordering ASC");


while($row=mysqli_fetch_assoc($r)) { 
    
    
    if($row['file_type'] == 1 || $row['file_type'] == 2 ) {

        $source = $row['val'];
    
    } else if($row['server_id'] == $SERVER_ID  || $row['transcoding'] == 'redirect' ) {
        
        if($row['transcoding'] == 'redirect') 
            $source = $row['source_file']; 
        else $source = "/home/aces/vods/{$row['file_id']}.{$row['container']}";

    } else {
        $token = md5(rand(1,99999).time().time().rand(1,999999999));
        $DB->query("INSERT INTO iptv_access (vod_id,server_id,server_ip,token,ip_address,limit_time,end_time,add_date) VALUES('{$row['file_id']}','{$row['server_id']}','{$row['server_ip']}','$token','$server_ip',NOW() + INTERVAL 5 MINUTE,NOW() + INTERVAL 1 HOUR, NOW())  ");
        $aid=$DB->insert_id;
        //$source = "http://{$row['server_ip']}:{$row['server_port']}/stream/vod/$aid:$token/{$row['file_id']}.{$row['container']}";
        $source = "http://{$row['server_ip']}:{$row['server_port']}/stream/vod/$aid:$token/{$row['file_id']}.mp4";

    }
    
    //if($row['status'] != 2 && $row['status'] != -1 ) { 
    if($row['status'] != 2 ) {
        
        logfile("BUILDING $source");
        $DB->query("UPDATE iptv_channel_files set status = 1 WHERE id = {$row['id']} ");


        //KILLING PROCESS 
        if($PID = (int)  exec("pgrep -f '/home/aces/channel_files/{$row['id']}.ts'",$pids) ) {
            foreach($pids as $pid) exec("kill -9 $pid ");
        }

        $codecs = explode(":",$row['build_codecs']);

        $v_codec = ($codecs[0]) ? $codecs[0] : 'libx264';
        $a_codec = ($codecs[1]) ? $codecs[1] : 'ac3';

        if($v_codec == 'h264_nvenc') {
            $pre_ffmpeg_cuda = $row['build_gpu'] ? ' -hwaccel cuda -hwaccel_output_format cuda ' : '';
            $ffmpeg_cuda = $row['build_gpu'] ? ' -vf scale_cuda=format=yuv420p ' : '';
        }
        
        if($row['file_type'] == 1 ) { logfile(":::$source");
            unlink("/home/aces/channel_files/youtubedl$CHANID-*");
            $zydl_file = "youtubedl$CHANID-".time();
            exec("youtube-dl -v -f best '$source' -o /home/aces/channel_files/$zydl_file >> $LOGFILE  ");

            exec("ffmpeg  -y  -nostdin -nostats -hide_banner -fflags +genpts -async 1 -i /home/aces/channel_files/$zydl_file  -c:v $v_codec -c:a ac3 -strict -2 -mpegts_flags +initial_discontinuity -sc_threshold 0 -preset medium -crf 24 -f mpegts /home/aces/channel_files/{$row['id']}.ts >> $LOGFILE 2>&1" ,$o,$status);
            unlink("/home/aces/channel_files/$zydl_file");
            //exec("youtube-dl -v -f best -o - '$source' | ffmpeg -y -nostdin -nostats -hide_banner -fflags +genpts -async 1 -i - -c:v libx264 -c:a ac3 -strict -2 -mpegts_flags +initial_discontinuity -sc_threshold 0 -preset medium -crf 24 -f mpegts /home/aces/channel_files/{$row['id']}.ts >> $LOGFILE 2>&1" ,$o,$status);

        } else {

            if($v_codec == 'symlink') {
                if(!is_file($source)) { logfile("Cannot use Symlink. File is not on server or could not be read."); die; }
                if(is_file("/home/aces/channel_files/{$row['id']}.ts")) unlink("/home/aces/channel_files/{$row['id']}.ts");
                exec("ln -sn \"$source\" /home/aces/channel_files/{$row['id']}.ts",$o,$status);
                //logfile(print_r($o,1));
                //$status = 0;
            } else {
                exec(" ffmpeg -y  $pre_ffmpeg_cuda -nostdin -nostats -hide_banner -fflags +genpts -async 1 -i '$source' $ffmpeg_cuda -c:v $v_codec -c:a ac3 -strict -2 -mpegts_flags +initial_discontinuity -sc_threshold 0  -preset medium -crf 24 -f mpegts /home/aces/channel_files/{$row['id']}.ts >> $LOGFILE 2>&1", $o, $status);
                //exec("ffmpeg -y -nostdin -nostats -hide_banner  -fflags +genpts -async 1  -i '$source' -c:v h264 -c:a aac -strict -2 -mpegts_flags +initial_discontinuity -f mpegts /home/aces/channel_files/{$row['id']}.ts",$o,$status);
            }
        }


        if($status != 0 ) { logfile("FAIL TO BUILD FILE"); $DB->query("UPDATE iptv_channel_files SET status = -1 WHERE id = {$row['id']} "); }
        else { logfile("COMPLETED."); $DB->query("UPDATE iptv_channel_files set status = 2 WHERE id = {$row['id']} "); }
        
    }
    
}

unlink("/home/aces/run/aces_build_channel-$CHANID.pid");


