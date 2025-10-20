#!/usr/bin/env php
<?php

error_reporting(0);
date_default_timezone_set('UTC');

include "/home/aces/stream/config.php";



$CHANID = (int)$argv[1];
if(!$CHANID) die;


if(is_file("/home/aces/run/aces_nstream-$CHANID.pid")) die;
file_put_contents("/home/aces/run/aces_nstream-$CHANID.pid",getmypid());
if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );
if(!is_dir("/home/aces/logs/streams/")) mkdir("/home/aces/logs/streams/",0777 );



$LOGFILE = "/home/aces/logs/streams/stream-$CHANID.log";
$LOG = 0;

function logfile($text) {

	global $LOGFILE,$LOG;

	if(!$LOG) return false;

	file_put_contents($LOGFILE, "\n[".date(' H:i:s Y:m:d')." ] $text \n\n", FILE_APPEND );

}



echo ""> $LOGFILE;


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$r=$DB->query("SELECT val_int FROM settings WHERE name = 'iptv.streamlogs' ");
if(!$LOG=mysqli_fetch_array($r)['val_int']) $LOG =0; 


$r=$DB->query("SELECT stream_profile FROM iptv_channels WHERE id = $CHANID ");
$stream_profile = mysqli_fetch_array($r)['stream_profile'];

$r=$DB->query("SELECT * FROM iptv_stream_options WHERE id = $stream_profile " );
$stream_opt = mysqli_fetch_array($r);

$r=$DB->query("SELECT id FROM iptv_streaming WHERE server_id = $SERVER_ID AND chan_id = $CHANID ");
$STREAMING_ID = (int)mysqli_fetch_array($r)['id'];

//BUIDLING URL
$r=$DB->query("SELECT s.address,s.port,s.vlan FROM iptv_servers_in_array a 
			INNER JOIN iptv_servers s ON a.main_server = s.id
			WHERE a.server_id = $SERVER_ID  ");
$row = mysqli_fetch_array($r);

if(!empty($row['vlan'])) $url = "http://{$row['vlan']}:9090/load/server/$CHANID.m3u8";
else $url = "http://{$row['address']}:9090/load/server/$CHANID.m3u8";



$opt=" ";
if(!empty($stream_opt['opt'])) $opt = " {$stream_opt['opt']} ";

$user_agent = "";
if(!empty($stream_opt['user_agent'])) $user_agent = " -user_agent \"{$stream_opt['user_agent']}\" ";


$start_number = 0;

$COMMAND = "ffmpeg -y -nostdin -nostats  -fflags +genpts  $user_agent  %{headers} -i %{url}  -c:v copy -c:a copy -strict -2  $opt  -hls_time {$stream_opt['segment_time']} -hls_list_size {$stream_opt['segment_list_files']} -hls_wrap {$stream_opt['segment_wrap']} -start_number %{i}  %{list} ";



logfile('PROCESS START');



//KILL OLD PROCCESS IF STILL RUNNING.
while($pid = exec(" ps -eAf  | grep /home/aces/stream_tmp/$CHANID-.m3u8 | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
	if($pid) exec("kill -9 $pid ");

	while(true) {

		$ffmpeg_pid=0;
		

		$cmd = $COMMAND;
	
		$cmd = str_replace("%{url}", " \"$url\" ",$cmd );
		$cmd = str_replace("%{list}","$FOLDER$CHANID-.m3u8",$cmd );
		$cmd = str_replace("%{file}","$FOLDER/$CHANID-%01d.ts",$cmd );
		$cmd = str_replace("%{i}",$start_number,$cmd );
		$cmd = str_replace("%{headers}",$headers,$cmd );
	
		$start = time();
	
		logfile("STARTING STREAM $url");
		if($LOG>2) logfile("COMMAND : $cmd ");
	
	
		$DB->query("UPDATE iptv_streaming SET status = 0,   streaming = '$url', start_time=NOW() WHERE id=$STREAMING_ID ");
	
	
		if($LOG>2) $ffmpeg_pid = trim(shell_exec(sprintf(' %s > %s 2>&1 & echo $!', $cmd,$LOGFILE )));
		else $ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $cmd )));
		
		
	
		if($LOG > 2 ) logfile("FFMPEG PID $ffmpeg_pid");
	
		$t=0;
		
		while($t<12) {
	
			//WAITING FOR LIST TO BE WRITTED
			clearstatcache();
			sleep(5);
			if(!file_exists("/proc/$ffmpeg_pid/comm")) {
				logfile('COULD NOT START STREAM, DISCONNECTED.');
				break ;

			} 
	
			if(is_file("/home/aces/stream_tmp/$CHANID-.m3u8")) {
				logfile("SOURCE CONNECTED " );
				$DB->query("UPDATE iptv_streaming SET status = 1  WHERE id=$STREAMING_ID ");
				break;
			}
			$t++;
			
	
		}
		
		if( file_exists("/proc/$ffmpeg_pid/comm"))
			while( true ) {
				
				sleep(10);
				clearstatcache();
	
				
				if( !file_exists("/proc/$ffmpeg_pid/comm")) {
	
					logfile('STREAM STOP, COMMAND STOP.');
					break ;
				}
				else if( (fileatime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ) {
	
					logfile('STREAM STOP, NOT RECIEVING DATA.');
					break ;
				}
	
				$DB->close();
				//empty($DB);
	
			} 
	
				
			if(!$DB->ping()) {
				unset($DB);
				$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
				if($DB->connect_errno > 0) {
	
					logfile("FAIL TO RECONNECT TO DATABASE.");
					
				}
			}
	
			//KILL IF PROCCESS STILL RUNNING
			$pid=null;
			while($pid = exec(" ps -eAf  | grep /$CHANID-.m3u8 | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
				if($pid) exec("kill -9 $pid ");
	

			$DB->query("UPDATE iptv_streaming SET source_id = 0, status = 0, streaming = '', start_time='00-00-00 00:00:00' WHERE id=$STREAMING_ID ");
	

	
	}
	
	die;
	
