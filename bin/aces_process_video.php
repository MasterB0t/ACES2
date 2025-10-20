#!/usr/bin/env php
<?php

//error_reporting(0);

include "/home/aces/stream/config.php";

$ID = (int)$argv[1]; 
if(!$ID) exit();


$PID_FILE = "/home/aces/run/aces_process_video_{$ID}.pid";
$LOGFILE = "/home/aces/logs/vods/{$ID}.log";
//$LOGFILE = "/home/aces/logs/php-aces.log";

if(!is_dir('/home/aces/logs/vods/')) mkdir('/home/aces/logs/vods/',0774,true );

function logfile($text) { 

    global $LOGFILE,$LOG;

    //if(!$LOG) return false;
    
    echo $text."\n";

    file_put_contents($LOGFILE, "\n[".date(' H:i:s Y:m:d')." ] $text \n\n", FILE_APPEND );

}

register_shutdown_function(function (){
    global $DB, $ID;
    $DB->query("UPDATE iptv_video_files SET is_processing = 0 WHERE id = $ID ");
});




echo "" > $LOGFILE;
logfile("PROCESS START");


if(is_file($PID_FILE)) { 
    
    //THERE ARE A PID FILE LET TRY TO READ PID FROM THE FILE.
    if (!($PID = file_get_contents($PID_FILE))) {
        logFile("IT SEEMS THERE IS A PROCESS ALREADY RUNNING FOR THIS VIDEO BUT COULDN'T GET PID FROM IT.");
        exit();
    }
     
    //WE HAVE PID # LET CHECK IF STILL RUNNING
    if(posix_getpgid($PID)) {
        logFile('THERE IS ALREADY PROCESS RUNNING FOR THIS VIDEO.'); exit(); }
        
}

if(! (file_put_contents($PID_FILE,getmypid()) ) ) {
    logFile("COULD NOT WRITE PID TO FILE"); exit(); }


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) { logfile('ERROR: COULD NOT CONNECT TO DATABASE.'); exit(); } 


$r=$DB->query("SELECT * FROM iptv_video_files WHERE id = $ID ");
if(!$row_video = mysqli_fetch_array($r)) { logFile("Video #$ID could not be found."); exit(); }
$DB->query("UPDATE iptv_video_files SET is_processing = 1 WHERE id = $ID ");

if($row_video['server_id'] != $SERVER_ID) { logFile("This video doesn't belong to this server."); exit(); }


if($row_video['episode_id'] != 0 ) {
    $VIDEO_TYPE = 'e'; 
    $video_id = $row_video['episode_id']; 
    $table = 'iptv_series_season_episodes';
} else if($row_video['movie_id'] != 0 ) {
    $VIDEO_TYPE  = 'm';
    $video_id = $row_video['movie_id']; 
    $table = 'iptv_ondemand';
}

$subs_files = '';  $subs_opts = '';


if(!empty($row_video['subtitles'])) { 

    if( $row_video['container'] == 'mkv') $subs_opts .= " -c:s srt ";
    else if( $row_video['container'] == 'avi') $subs_opts .= " -c:s ass ";
    else $subs_opts .= " -c:s mov_text ";
    
    $subtitle = unserialize($row_video['subtitles']);
    $m=0;
    
    foreach($subtitle as $s) { 
        $s['file'] = urldecode($s['file']);
        $s['file']=addslashes($s['file']);
        $s['file'] = str_replace(" ", "\\ ",$s['file']);
        $s['file'] = str_replace("(", "\(",$s['file']);
        $s['file'] = str_replace(")", "\)",$s['file']);
        $s['file'] = str_replace("&", "\&",$s['file']);
        
        $subs_files .= " -i {$s['file']} ";
        $subs_opts .= "  -map ".($m+1)." -metadata:s:s:$m language={$s['lang']} ";
        $m++;
    }
}

if(empty($subs_opts)) {
    if( $row_video['container'] == 'mkv') $subs_opts = " -c:s copy ";
    else if( $row_video['container'] == 'avi') $subs_opts .= " -c:s mov_text ";
    else $subs_opts = " -c:s mov_text ";
}

//$COMMAND = "ffmpeg -y -loglevel error -progress /home/aces/logs/vods/{$ID}-prog.txt -i %{file}  -map 0:s? -map 0:a -map 0:v -c:v %{vcodec} -c:a %{acodec} $subs_opts /home/aces/vods/$ID.%{cont}";
$COMMAND = "ffmpeg -y -loglevel error -progress /home/aces/logs/vods/{$ID}-prog.txt -i %{file} $subs_files  -map 0:s? -map 0:a -map -v -map V -c:v %{vcodec} -c:a %{acodec} $subs_opts /home/aces/vods/$ID.%{cont}";


$DOWNLOAD_SPEED = '';
$r=$DB->query("SELECT name,value FROM settings WHERE  name = 'iptv.videos.max_download_speed' ");
while ($row = mysqli_fetch_array($r)) {
    if($row['name'] == 'iptv.videos.max_download_speed' && $row['value'] > 0 ) $DOWNLOAD_SPEED = "--limit-rate=".$row['value']."m";
}

exec(" rm -rf /home/aces/vods/$ID-* ");
exec(" rm -rf /home/aces/vods/$ID.* ");

$r=$DB->query("SELECT * FROM $table WHERE id = $video_id " );
if(!mysqli_fetch_array($r)) { 
    
    logFile('Video Could not be found.');
    $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id ");   
    exit();
    
} else { 
    
    exec("ls /home/aces/vods/$ID.* 2>&1 ",$o,$s);
    
    if(!$s) {
        logfile("Old file could not be removed.");
        $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id ");   
        exit();
    }
    
    $DB->query("UPDATE $table SET status = 0, percent = 0  WHERE id = $video_id ");
    $row_video['source_file'] =urldecode($row_video['source_file']);

    if($row_video['transcoding'] == 'symlink' || $row_video['transcoding'] == 'redirect' ||  $row_video['transcoding'] == 'stream' ) {
        if (filter_var( $row_video['source_file'] , FILTER_VALIDATE_URL)) {
            //REDIRECT TO WEB SOURCE.
            
//            if(empty($NO_PROCCESS_SYMLINK)) {
//                $DB->query("UPDATE iptv_video_files SET transcoding = 'redirect', duration = '0', is_processing = 0
//                        WHERE id = $ID  ");
//            }


            $DB->query("UPDATE  $table SET status = 1  WHERE id = $video_id ");

            exec("php /home/aces/bin/aces_get_video_codecs.php $ID &");

            logfile("FINISHED");
            unlink($PID_FILE);
            exit();
            
            
        } else { 
            //is local file.
            if(!is_readable($row_video['source_file'])) {
                logfile("Local file not found or could not be read. {$row_video['source_file']}");
                $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id ");
                unlink($PID_FILE);
                exit();
                
            } else {

                $row_video['source_file']=addslashes($row_video['source_file']);
                $row_video['source_file'] = str_replace(" ", "\\ ",$row_video['source_file']);
                $row_video['source_file'] = str_replace("(", "\(",$row_video['source_file']);
                $row_video['source_file'] = str_replace(")", "\)",$row_video['source_file']);
                $row_video['source_file'] = str_replace("&", "\&",$row_video['source_file']);

                $duration = 0;
                if($a = exec("ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$row_video['source_file']} "))
                    $duration = round($a);

                exec("ln -sn {$row_video['source_file']} /home/aces/vods/$ID.{$row_video['container']}");

                if(empty($NO_PROCCESS_SYMLINK)) {

//                    $stream_v_info = json_decode(shell_exec(" ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate {$row_video['source_file']} "), true);
//                    $stream_a_info = json_decode(shell_exec(" ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name {$row_video['source_file']} "), true);
//
//                    if (!$bitrate = (int)shell_exec("ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1 {$row_video['source_file']}  ")) $bitrate = 0;
//
//                    $video_codec = $stream_v_info['streams'][0]['codec_name'];
//                    $audio_codec = $stream_a_info['streams'][0]['codec_name'];
//                    $resolution = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
//
//
//                    $frames = 0;
//                    if ($stream_v_info['streams'][0]['r_frame_rate'] && $stream_v_info['streams'][0]['r_frame_rate'])
//                        $frames = round((int)explode('/', $stream_v_info['streams'][0]['r_frame_rate'])[0] / explode('/', $stream_v_info['streams'][0]['r_frame_rate'])[1]);
//
//                    $DB->query("UPDATE iptv_video_files SET resolution = '$resolution', frames = '$frames', bitrate = '$bitrate', audio_codec = '$audio_codec', video_codec = '$video_codec', duration = '$duration' WHERE id = $ID  ");

                    exec("php /home/aces/bin/aces_get_video_codecs.php $ID &");

                }

                $DB->query("UPDATE  $table SET status = 1  WHERE id = '$video_id' ");

                logfile("FINISHED");
            }
            
        }
        
    } else { 
        
//        if(exec(" ffprobe  -v quiet -show_entries format=format_name -of default=noprint_wrappers=1:nokey=1 {$row_video['source_file']}") != 'hls,applehttp' )        {  
//
//            logfile("DOWNLOADING : {$row_video['source_file']} ");
//            exec("wget \"{$row_video['source_file']}\" $DOWNLOAD_SPEED -t 5 -a $LOGFILE -O /home/aces/tmp/vod_downloads/{$ID}", $o, $status);
//            if($status > 0 ) { $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id "); logfile("Error Downloading movie."); exit(); } 
//            $down_file = "/home/aces/tmp/vod_downloads/{$ID}";
//
//        } else  $down_file = $row_video['source_file'];
        
        if (filter_var( $row_video['source_file'] , FILTER_VALIDATE_URL)) {

            if(!is_dir('/home/aces/tmp/vod_downloads/'))
                if(!exec("mkdir -m 777 /home/aces/tmp/vod_downloads/ ")) {
                    logfile("Unable to create download directory");
                    exit();
                }


            logfile("DOWNLOADING : {$row_video['source_file']} ");
            logfile("wget --no-check-certificate \"{$row_video['source_file']}\" $DOWNLOAD_SPEED -t 5 -a $LOGFILE -O /home/aces/tmp/vod_downloads/{$ID}");
            exec("wget --no-check-certificate \"{$row_video['source_file']}\" $DOWNLOAD_SPEED -t  5 -O /home/aces/tmp/vod_downloads/{$ID}", $o, $status);
            if($status > 0 ) { $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id "); unlink($PID_FILE); logfile("Error Downloading movie."); exit(); }
            
//            exec("ffmpeg  -i /home/aces/tmp/vod_downloads/{$ID} -c copy -map 0:s -v 0 -hide_banner -f null -  && echo $? || echo $?", $o, $status);
//            if(!$status) { 
//                
//                if(empty($subs_files)) { //SUB CODEC HAVENT ADD YET....
//                    if( $row_video['container'] == 'mkv') $subs_opts .= " -c:s srt ";
//                    else if( $row_video['container'] == 'avi') $subs_opts .= " -c:s ass ";
//                    else $subs_opts .= " -c:s mov_text ";
//                }
//                
//                $subs_files .= " -map 0:s?  ";
//            }
            
            $down_file = "/home/aces/tmp/vod_downloads/{$ID}";
        } else { logfile("LOCAL FILE.");
            //LOCAL FILE ADD \ BEFORE SPACES.
            $down_file = addslashes($row_video['source_file']);
            $down_file = str_replace(" ", "\\ ",$down_file);
            $down_file = str_replace("(", "\(",$down_file);
            $down_file = str_replace(")", "\)",$down_file);
            $down_file = str_replace("&", "\&",$down_file);
        }
            
        
        logfile("FILE ".$down_file);
        
        if(!$row_video['container'])$row_video['container'] = "mp4";

        $COMMAND = str_replace("%{sub}", " $subs_files ",$COMMAND );
        $COMMAND = str_replace("%{file}", " $down_file ",$COMMAND );
        $COMMAND = str_replace("%{cont}", $row_video['container'], $COMMAND );
        
        if($row_video['transcoding'] == 'copy' ) { 
            $COMMAND = str_replace("%{vcodec}", " copy ",$COMMAND );
            $COMMAND = str_replace("%{acodec}", " copy ",$COMMAND );
        } else { 
            $codecs = explode(":",$row_video['transcoding']);
            $COMMAND = str_replace("%{vcodec}", " {$codecs[0]} ",$COMMAND );
            $COMMAND = str_replace("%{acodec}", " {$codecs[1]} ",$COMMAND );
        }
        
        //getting codecs from source video
        $total_vod_time = round(exec("ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $down_file "));
        if($total_vod_time  < 1 ) { logfile('COULD NOT GET VIDEO TOTAL TIME.'); unlink($PID_FILE); $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id ");  exit(); }
        else { logfile("TOTAL VIDEO TIME : $total_vod_time"); }

        $DB->query("UPDATE $table SET percent = '1' WHERE id = $video_id ");

        logfile("$COMMAND");

        $ffmpeg_pid = exec(" $COMMAND >> $LOGFILE 2>&1 & echo $!; ");

        sleep(5);

        while( exec("ps -p $ffmpeg_pid -o comm=")) { 

            if(!$DB->ping()) {
                unset($DB);
                $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                if($DB->connect_errno > 0) {
                        logfile("COULD NOT RECONNECT TO DATABASE (DB ERROR : $DB->connect_error) ");
                        exec("rm -rf /home/aces/tmp/vod_downloads/{$ID}");
                        unlink($PID_FILE);
                        exit();
                }
            }


            $cur_time = 0;

            $content = @file_get_contents("/home/aces/logs/vods/{$ID}-prog.txt");
            if($content){

                    preg_match_all("/out_time=(.*)/", $content, $matches);
                    $c = count($matches[0]);
                    $rawTime = $matches[0][$c-1];

                    //rawDuration is in 00:00:00.00 format. This converts it to seconds.
                    $ar = array_reverse(explode(":", $rawTime));
                    $time = floatval($ar[0]);
                    if (!empty($ar[1])) $time += intval($ar[1]) * 60;
                    if (!empty($ar[2])) $time += intval($ar[2]) * 60 * 60;

                //calculate the progress
                $progress = round(($time/$total_vod_time) * 100);
            }


            $DB->query("UPDATE $table SET percent = '$progress' WHERE id = $video_id ");
            sleep(10);

        }
        
        $content = @file_get_contents("/home/aces/logs/vods/{$ID}-prog.txt");
        preg_match("/progress=end/",$content,$matches);

        if(!$matches[0]) {
            
            logfile("FAIL TO PROCESS VIDEO.");
            $DB->query("UPDATE $table SET status = -2 WHERE id = $video_id ");
            
        } else {

//            $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/vods/$ID.{$row_video['container']} " ),true);
//            $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name /home/aces/vods/$ID.{$row_video['container']} " ),true);
//            if( !$bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/vods/$ID.{$row_video['container']}  " )) $bitrate = 0;
//
//
//            $video_codec = $stream_v_info['streams'][0]['codec_name'];
//            $audio_codec = $stream_a_info['streams'][0]['codec_name'];
//            $resolution = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
//            $frames = round( (int) explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[0] / explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[1],0)  ;
//
//
//            //$DB->query("UPDATE  $table SET status = 1, runtime_seconds = $total_vod_time  WHERE id = $video_id ");
//
//            $DB->query("UPDATE iptv_video_files SET resolution = '$resolution', frames = '$frames', bitrate = '$bitrate', audio_codec = '$audio_codec', video_codec = '$video_codec', duration='$total_vod_time' WHERE id = $ID  ");


            $DB->query("UPDATE $table SET status = 1 WHERE id = $video_id ");

            exec("php /home/aces/bin/aces_get_video_codecs.php $ID &");

            logFile("COMPLETED");

        } 
        
    }

    
    
}


unlink($PID_FILE);