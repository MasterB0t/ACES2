<?php 

include "/home/aces/stream/config.php";

ini_set("error_log", "/home/aces/logs/record.log");
error_reporting( E_ERROR | E_WARNING );

$RECORD_ID = (int)$argv[1];
$RECORD_ID = str_replace("-",'',$RECORD_ID);
if(!$RECORD_ID) die;

//CREATE LOG FOLDER IF NOT EXIST.
if(!is_dir("/home/aces/logs/record/")) {
    if(!mkdir("/home/aces/logs/record/",0777 )) { error_log("RECORDING COULD NOT MAKE LOG DIRECTORY."); die; } 
}

if(!is_dir("/home/aces/recordings/")) { 
    if(!mkdir("/home/aces/recordings/",0777 )) { error_log("RECORDING COULD NOT MAKE DIRECTORY."); die; } 
}

$PID_FILE = "/home/aces/run/record-{$RECORD_ID}.pid";
if(is_file($PID_FILE)) { 
    $PID = file_get_contents("$PID_FILE");
    if(posix_getpgid($PID)) die("Another process is already running.");
} else {
    if(!file_put_contents($PID_FILE,getmypid())) die('Could not write pid file.');
}

exec("kill -9 $(ps -eAf  | grep /home/aces/recordings/ | grep ffmpeg | grep 'p26\-'  |  grep -v 'ps -eAf'  | awk '{print $2 }') "); 

$LOGFILE = "/home/aces/logs/record/record-$RECORD_ID.log";
$LOG = 3;
// 0 - NO LOGS
// 1 - ERROR ONLY
// 2 - ERRORS AND VERBOSE MESSAGE
// 3 - ERRORS, VERBOSE MESSAGE AND DEBUG 

function quit() { 
    global $PID_FILE;
    unlink($PID_FILE);
    exit;
}

function logfile($text) {

    global $LOGFILE,$LOG;

    if(!$LOG) return false;

    $text = "\n[ ".date('Y:m:d H:i:s')." ] $text \n\n";
    
    file_put_contents($LOGFILE, $text, FILE_APPEND );
    
    echo $text;

}

file_put_contents($LOGFILE,"");

$COMMAND = "ffmpeg -loglevel %{log} -fflags +nobuffer+genpts -start_at_zero -i %{input} -tune zerolatency -c:v copy -c:a copy -f mpegts %{out} ";

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) error_log("Could not connect to database");

$r=$DB->query("SELECT chan_id,UNIX_TIMESTAMP(start_time) as timestart, UNIX_TIMESTAMP(end_time) as timeend  FROM iptv_recording WHERE id = '$RECORD_ID' AND status in (0,1,2) ");
if(!$row=mysqli_fetch_array($r)) { if($LOG) logfile("NO RECORDING FOUND ON DATABASE WITH THIS ID"); quit(); }

$r=$DB->query("SELECT stream_server FROM iptv_channels WHERE id = '{$row['chan_id']}' ");
if(!$STREAM_SERVER=mysqli_fetch_array($r)['stream_server']) { logfile("COULD NOT GET CHANELL INFO."); quit(); } 

$r=$DB->query("SELECT address,port FROM iptv_servers WHERE id = '$STREAM_SERVER' ");
$SERVER = mysqli_fetch_array($r);

$r=$DB->query("SELECT address,port FROM iptv_servers WHERE id = '$SERVER_ID' ");
$THIS_ADDR = mysqli_fetch_array($r)['address'];


$DB->query("UPDATE iptv_recording SET status = '1' WHERE id = '$RECORD_ID'");

$end_time = $row['timeend'] + (60*2);
$start_time = $row['timestart'] - (60*2);

while($start_time > time() ) { 
    if($LOG>1) { logfile("WAITING FOR RECORDING TO START"); }
    sleep(10); 
} 
    
$DB->query("UPDATE iptv_recording SET status = '2' WHERE id = '$RECORD_ID'");

$i=count(glob("/home/aces/recordings/p{$RECORD_ID}*.ts"));

logfile("VERSION ". phpversion());
logfile("TIME ".time());
logfile("END TIME ".$end_time);


if($end_time > time() ) { 
    
    logfile('STARTING RECORDING');
    while(time() < $end_time) { 

        while(true) {

            if(!$DB->ping()) {
                unset($DB);
                while(true) { 
                    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                    if($DB->connect_errno > 0) { sleep(5); }
                    else break;
                }
            }

            //WATTING FOR MAIN SERVER CONNECT TO SOURCE
            while(true) {
                
                $r=$DB->query("SELECT stream_server FROM iptv_channels WHERE id = '{$row['chan_id']}' ");
                $STREAM_SERVER=mysqli_fetch_array($r)['stream_server']; 
                
                $r=$DB->query("SELECT address,port FROM iptv_servers WHERE id = '$STREAM_SERVER' ");
                $SERVER = mysqli_fetch_array($r);
                
                $ra=$DB->query("SELECT id FROM iptv_streaming WHERE chan_id = '{$row['chan_id']}' AND server_id = '$STREAM_SERVER' AND status = 1  ");
                if(mysqli_fetch_array($ra)) {
                    $acc_token=md5(rand().time().rand());
                    $DB->query("INSERT INTO iptv_access (chan_id,server_id,server_ip,token,ip_address,limit_time,add_date) VALUES('{$row['chan_id']}','$SERVER_ID','{$SERVER['address']}','$acc_token','$THIS_ADDR',NOW()+INTERVAL 1 DAY,NOW() )  ");
                    $aid = $DB->insert_id;
                    $URL = "http://{$SERVER['address']}:{$SERVER['port']}/stream/$aid/$acc_token/{$row['chan_id']}-.mpegts";
                    break;
                }
                if(time() > $end_time) { 
                    if($LOG) logfile(" PANEL NEVER CONNECT TO CHANNEL SOURCE. TIME ENDED");
                    $DB->query("DELETE FROM iptv_access WHERE id = '$aid' ");
                    quit(); 

                } //NEVER CONNECT TO SOURCE AND PROGRAMME EXPIRED.
                if($LOG>2) logfile("WATTING CHANNEL TO CONNNECT $STREAM_SERVER");
                sleep(10);
            }

            if(time() < $end_time) { break; } 
            sleep(10);
        }

        $i++;
        $cmd = $COMMAND;
        
        $cmd = str_replace("%{out}", " /home/aces/recordings/p{$RECORD_ID}-$i.ts ",$cmd );
        $cmd = str_replace("%{input}",$URL,$cmd );
        
        logfile("OUT FILE /home/aces/recordings/p{$RECORD_ID}-$i.ts ");

        if($LOG > 2 ) $cmd = str_replace("%{log}",' verbose ',$cmd );
        else if($LOG > 0 ) $cmd = str_replace("%{log}",' error ',$cmd );
        else $cmd = str_replace("%{log}",' quiet ',$cmd );

        if($LOG > 0) $ffmpeg_pid = trim(shell_exec(sprintf(' %s >> %s 2>&1 & echo $!', $cmd,$LOGFILE )));    
        else $ffmpeg_pid = trim(shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $cmd )));


        sleep(1);
        while(time() < $end_time && posix_getpgid($ffmpeg_pid)) {
            sleep(10);
        }
        if(posix_getpgid($ffmpeg_pid)) exec("kill -9 $ffmpeg_pid");
    }
    
} else logfile("NOTHING HAVE BEEN RECORD"); 


//CONTACT
file_put_contents("/home/aces/recordings/f{$RECORD_ID}.txt", "");
$files = glob("/home/aces/recordings/p{$RECORD_ID}*.ts");

logfile("FILES ".print_r($files,1));

foreach($files as $f )
    file_put_contents ("/home/aces/recordings/f{$RECORD_ID}.txt", "file $f\n", FILE_APPEND);

if($LOG) logfile("RUNNING FFMPEG CONCAT ");
//$CONCAT_FFMPEG = "ffmpeg -y -loglevel %{log}  -fflags +genpts -copyts -start_at_zero -f concat -safe 0 -i /home/aces/recordings/f{$RECORD_ID}.txt -codec copy -f mpegts /home/aces/recordings/r{$RECORD_ID}.ts";
$container = 'mkv';

//$CONCAT_FFMPEG = "ffmpeg -y -loglevel %{log}  -fflags +genpts -async 1 -copyts -err_detect ignore_err -start_at_zero -f concat -safe 0 -i /home/aces/recordings/f{$RECORD_ID}.txt -max_muxing_queue_size 1024 -c:v h264 -c:a aac -preset ultrafast /home/aces/recordings/r{$RECORD_ID}.$container";

$CONCAT_FFMPEG = "ffmpeg -y -loglevel %{log}  -fflags +genpts -async 1 -copyts -err_detect ignore_err -start_at_zero -f concat -safe 0 -i /home/aces/recordings/f{$RECORD_ID}.txt -max_muxing_queue_size 1024 -c:v copy -c:a copy  /home/aces/recordings/r{$RECORD_ID}.$container";

if($LOG > 2 ) $CONCAT_FFMPEG = str_replace("%{log}",' verbose ',$CONCAT_FFMPEG );
else if($LOG > 0 ) $CONCAT_FFMPEG = str_replace("%{log}",' error ',$CONCAT_FFMPEG );
else $CONCAT_FFMPEG = str_replace("%{log}",' error ',$CONCAT_FFMPEG );

if($LOG > 0) shell_exec(sprintf(' %s >> %s 2>&1 ', $CONCAT_FFMPEG,$LOGFILE ));    
else shell_exec(sprintf('%s > /dev/null 2>&1 ', $CONCAT_FFMPEG ));

unlink("/home/aces/recordings/f{$RECORD_ID}.txt");
foreach($files as $f ) unlink($f);

if(!$DB->ping()) {
    unset($DB);
    while(true) { 
        $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
        if($DB->connect_errno > 0) { sleep(5); }
        else break;
    }
}

$filesize = (int)filesize("/home/aces/recordings/r{$RECORD_ID}.$container");
logfile('FILESIZE : '.$filesize);



//if($filesize == 0 ) $DB->query("UPDATE iptv_recording SET status = 4, filesize='$filesize', container='$container' WHERE id = '$RECORD_ID'");
//else $DB->query("UPDATE iptv_recording SET status = 3, filesize='$filesize', container='$container' WHERE id = '$RECORD_ID'");


if($filesize < 1048576) $filesize = 1;
else $filesize=number_format($filesize / 1048576, 2); //CONVERT TO MEGABYTES

$DB->query("UPDATE iptv_recording SET status = 3, filesize='$filesize', container='$container' WHERE id = '$RECORD_ID'");
$DB->query("DELETE FROM iptv_access WHERE id = '$aid' ");