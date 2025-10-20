#!/usr/bin/env php
<?php

error_reporting(0);
date_default_timezone_set('UTF');

include "/home/aces/stream/config.php";

$ID = (int)$argv[1]; 
if(!$ID) die;


if(!is_dir('/home/aces/logs/vods/')) mkdir('/home/aces/logs/vods/',0774,true );

$PID_FILE = "/home/aces/run/aces_move_video_{$ID}.pid";
$LOGFILE = "/home/aces/logs/vods/moving-$ID.log";
$LOG = 1;

function logfile($text) { 

    global $LOGFILE,$LOG;

    //if(!$LOG) return false;	

    file_put_contents($LOGFILE, "\n[".date(' H:i:s Y:m:d')." ] $text \n\n", FILE_APPEND );

}

if(is_file($PID_FILE)) { 
    
    //THERE ARE A PID FILE LET TRY TO READ PID FROM THE FILE.
    if(! ($PID = file_get_contents($PID_FILE)) ) { logFile("IT SEEMS THERE IS A PROCESS ALREADY RUNNING FOR THIS VIDEO BUT COULDN'T GET PID FROM IT."); die; } 
     
    //WE HAVE PID # LET CHECK IF STILL RUNNING
    if(posix_getpgid($PID)) { logFile('THERE IS ALREADY PROCESS RUNNING FOR THIS VIDEO.'); die; } 
        
}

if(! (file_put_contents($PID_FILE,getmypid()) ) ) { logFile("COULD NOT WRITE PID TO FILE"); DIE; } 


$COMMAND = "ffmpeg  -loglevel error -progress /home/aces/logs/vods/$ID-move-prog.txt -i %{file} -map 0:a -map 0:v  -c:v copy -c:a copy -bsf:a aac_adtstoasc -strict -2  -threads 1 /home/aces/vods/$ID.mp4";
$COMMAND = "ffmpeg -y -loglevel error -progress /home/aces/logs/vods/{$ID}-move-prog.txt -i %{file} -map 0:a -map 0:v  -c:v copy -c:a copy  -strict -2  -threads 1 /home/aces/vods/$ID.mp4";


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;


$r=$DB->query("SELECT * FROM iptv_video_files WHERE id = $ID ");
if(!$row_video = mysqli_fetch_array($r)) { logFile("Video #$ID could not be found."); die; } 


if($row_video['episode_id'] != 0 ) { 
    $VIDEO_TYPE = 'e'; 
    $video_id = $row_video['episode_id']; 
    $table = 'iptv_series_season_episodes';
}else if($row_video['movie_id'] != 0 ) {
    $VIDEO_TYPE = 'm'; 
    $video_id = $row_video['movie_id']; 
    $table = 'iptv_ondemand';
}


$r=$DB->query("SELECT * FROM $table WHERE id = $video_id " );
if($row = mysqli_fetch_array($r)) { 
    
    $r2=$DB->query("SELECT id,address,port,api_token FROM iptv_servers WHERE id = {$row_video['server_id']} ");
    $SRV = mysqli_fetch_array($r2);
    
    //GETTING THIS SERVER IP FOR ACCESS.
    $r3=$DB->query("SELECT id,address FROM iptv_servers WHERE id = $SERVER_ID ");
    $server_ip = mysqli_fetch_array($r3)['address'];
    
    
    $token=md5(time().rand().time());
        
    $DB->query("INSERT INTO iptv_access (device_id,vod_id,server_id,server_ip,token,ip_address,limit_time,end_time,add_date) VALUES(0,'$ID','{$row_video['server_id']}','{$SRV['address']}','$token','$server_ip',NOW() + interval 1000  SECOND,NOW(),NOW())");
    $aid = $DB->insert_id;

    $link = "http://{$SRV['address']}:{$SRV['port']}/stream/vod/$aid:$token/$ID.mp4"; sleep(1);
    
    $total_vod_time = round(exec("ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1  \"$link\" "));
    
    $cmd = $COMMAND;
    $cmd = str_replace("%{file}", " '$link' ",$cmd );
    
    exec(" rm -rf /home/aces/vods/$ID.mp4 ");
    $ffmpeg_pid = exec(" nohup $cmd >> $LOGFILE 2>&1 & echo $!; ");
    
    $DB->query("UPDATE $table SET status = 2 WHERE id = $video_id ");
    
    //UPDATE STALKER IF ITS ENABLED.
    $r1=$DB->query("SELECT name,value FROM settings WHERE name = 'stalkersync.enable' or name = 'stalkersync.dbhost' or name = 'stalkersync.dbuser' or name = 'stalkersync.dbpass' or name = 'stalkersync.dbname' ");
    while($row=mysqli_fetch_array($r1)) {
        if($row['name'] == 'stalkersync.enable') $stalker_enabled = $row['value'];
        if($row['name'] == 'stalkersync.dbhost') $stalker_db_host = $row['value'];
        if($row['name'] == 'stalkersync.dbuser') $stalker_db_user = $row['value'];
        if($row['name'] == 'stalkersync.dbpass') $stalker_db_pass = $row['value']; 
        if($row['name'] == 'stalkersync.dbname') $stalker_db_name = $row['value']; 

    }
    if($stalker_enabled  == 1 ) { 

        $DB_ST = new mysqli($stalker_db_host,$stalker_db_user,$stalker_db_pass,$stalker_db_name);
        if($DB_ST->connect_errno > 0) logFile('Cannot connect to stalker db.');
        else { 
            if( $VIDEO_TYPE == 'm' ) $DB_ST->query("UPDATE video SET accessed = 0 WHERE id = $video_id ");
            else $DB_ST->query("UPDATE video_series_files SET accessed = 0 WHERE series_id = $video_id  ");
        }
    }
    
    sleep(1);

    while( exec("ps -p $ffmpeg_pid -o comm=")) { 


            if(!$DB->ping()) {
                    unset($DB);
                    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                    if($DB->connect_errno > 0) {
                        logfile("COULD NOT RECONNECT TO DATABASE (DB ERROR : $DB->connect_error) ");
                        exec("rm -rf /home/aces/vods/$ID.mp4 ");
                        die;
                    }
            }


            $cur_time = 0;			
            $content = @file_get_contents("/home/aces/logs/vods/$ID-move-prog.txt");
            if($content){

                    preg_match_all("/out_time=(.*)/", $content, $matches);
                    $c = count($matches[0]);
                    $rawTime = $matches[0][$c-1];

                    //rawDuration is in 00:00:00.00 format. This converts it to seconds.
                    $ar = array_reverse(explode(":", $rawTime));
                    $time = floatval($ar[0]);
                    if (!empty($ar[1])) $time  += intval($ar[1]) * 60;
                    if (!empty($ar[2])) $time  += intval($ar[2]) * 60 * 60;

                //calculate the progress
                $progress = round(($time/$total_vod_time) * 100);


            }
            
            $DB->query("UPDATE $table SET percent = '$progress' WHERE id = $video_id ");
            //$DB->query("UPDATE  iptv_ondemand SET percent = '$progress' WHERE id = $VODID ");
            sleep(10);

    }


    $content = @file_get_contents("/home/aces/logs/vods/$ID-move-prog.txt");
    preg_match("/progress=end/",$content,$matches);
    if($matches[0] && $total_vod_time == round(exec("ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1  /home/aces/vods/$ID.mp4 ")) ) {

            $DB->query("UPDATE iptv_video_files SET server_id = $SERVER_ID WHERE id = $ID ");
	    $DB->query("UPDATE $table SET status = 1 WHERE id = $video_id ");
            
            //REMOVING FROM OLDER SERVER 
            $p['api_token'] = $SRV['api_token'];
            $p['action'] = 'remove_vod';
            $p['vod_id'] = $ID;
            $j=urlencode(json_encode($p));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://{$SRV['address']}:{$SRV['port']}/stream/api.php?data=$j");
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $return = json_decode(curl_exec($ch),1);
            if(!$return) logfile('COULD NOT REMOVE FROM OLD SERVER.');
            
            logfile('COMPLETED');
            
            $r1=$DB->query("SELECT name,value FROM settings WHERE name = 'stalkersync.enable' or name = 'stalkersync.dbhost' or name = 'stalkersync.dbuser' or name = 'stalkersync.dbpass' or name = 'stalkersync.dbname' ");
            while($row=mysqli_fetch_array($r1)) {
                if($row['name'] == 'stalkersync.enable') $stalker_enabled = $row['value'];
                if($row['name'] == 'stalkersync.dbhost') $stalker_db_host = $row['value'];
                if($row['name'] == 'stalkersync.dbuser') $stalker_db_user = $row['value'];
                if($row['name'] == 'stalkersync.dbpass') $stalker_db_pass = $row['value']; 
                if($row['name'] == 'stalkersync.dbname') $stalker_db_name = $row['value']; 

            }
            if($stalker_enabled  == 1  ) { 

                $DB_ST = new mysqli($stalker_db_host,$stalker_db_user,$stalker_db_pass,$stalker_db_name);
                if($DB_ST->connect_errno > 0) logFile('Cannot connect to stalker db.');
                else { 
                    if($VIDEO_TYPE == 'm')
                        $DB_ST->query("UPDATE video SET accessed = 1 WHERE id = $video_id ");
                    else $DB_ST->query("UPDATE video_series_files SET accessed = 1 WHERE series_id = $video_id  ");
                }
            }

    } else { 
            $DB->query("UPDATE $table SET status = 1 WHERE id = $video_id ");
            exec("rm -rf /home/aces/vods/$ID* ");
            logfile("FAIL TO MOVE VOD.");
            
    }
    
}

logfile('FINISHED');