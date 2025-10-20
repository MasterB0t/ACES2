<?php
error_reporting(0);
//ini_set("error_log", '/home/aces/logs/php-aces.log');
date_default_timezone_set('UTC');

include "/home/aces/stream/config.php";

$CHANID = (int)$argv[1];
if(!$CHANID) die;

if(!$TOKEN = $argv[2]) die;



//$ffmpeg = " ffmpeg -loglevel error  -nostdin -nostats -hide_banner  -i /home/aces/stream_tmp/$CHANID-.m3u8 -listen 1 -c:v copy -c:a copy  -strict -2 -listen_timeout 5000 -f mpegts -reconnect_at_eof 1 -reconnect_streamed 1 -reconnect_delay_max 60 http://0.0.0.0:7070/$TOKEN ";

$ffmpeg = " ffmpeg -fflags +genpts  -i /home/aces/stream_tmp/$CHANID-.m3u8 -listen 1 -c:v copy -c:a copy  -listen_timeout 5000  -f mpegts http://0.0.0.0:7070/$CHANID/$TOKEN ";

$ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $ffmpeg )));
//$ffmpeg_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $ffmpeg,'/home/aces/logs/stream_client.txt' )));

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0)  { exec("kill -9 $ffmpeg_pid"); die; } 

$r = $DB->query("SELECT id FROM iptv_access WHERE token = '$TOKEN' ");
$ACCESS_ID = $r->fetch_assoc()['id'];
$r->free();


while(true) { 
    
    if(!posix_getpgid($ffmpeg_pid)) break;
    else { 
	if(!@$DB->ping()) { 
		$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
		if($DB->connect_errno > 0) { exec("kill -9 $ffmpeg_pid"); die; } 
	}
        $r=$DB->query("SELECT id FROM iptv_access WHERE id = $ACCESS_ID ");
        if($r->num_rows < 1 ) { 
            exec("kill -9 $ffmpeg_pid");
            $r->free();
            break;
        } 
	$r->free();
	$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 1 MINUTE WHERE id = $ACCESS_ID  ");
	$DB->close();
    }
    
    
    sleep(10);
    
}
if(!@$DB->ping()) { 
	$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
	if($DB->connect_errno > 0) die;
}


$DB->query("DELETE FROM iptv_access WHERE token = '$TOKEN' ");

die;