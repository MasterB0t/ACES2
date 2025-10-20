<?php

include "/home/aces/stream/config.php";
//include_once '/home/aces/panel/class/EpgParser.php';

set_time_limit ( 0 );
ini_set("error_log", "/home/aces/logs/catchup.log");

error_reporting( E_ERROR | E_WARNING );

$CHAND_ID = (int)$argv[1];
$CHAND_ID = str_replace("-",'',$CHAND_ID);
if(!$CHAND_ID) die;

//CREATE LOG FOLDER IF NOT EXIST.
if(!is_dir("/home/aces/logs/catchup/")) { 
    if(!mkdir("/home/aces/logs/catchup/",0777 )) { error_log("RECORDING COULD NOT MAKE LOGS DIRECTORY."); die; } 
}

$PID_FILE = "/home/aces/run/catchup-{$CHAND_ID}.pid";
if(is_file($PID_FILE)) { 
    $PID = file_get_contents("/home/aces/run/catchup-{$CHAND_ID}.pid");
    if(posix_getpgid($PID)) { error_log("Another process is already running."); die("Another process is already running."); }
} else {
    if(!file_put_contents($PID_FILE,getmypid())) { error_log("Another process is already running."); die('Could not write pid file.'); }
}

$LOGFILE = "/home/aces/logs/catchup/catchup-$CHAND_ID.log";
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

logfile(date_default_timezone_get());


$DB = new \mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) { logfile("UNABLE TO CONNECT TO DATABASE."); exit;};

$rc=$DB->query("SELECT id,tvg_id,stream_server,catchup_server,catchup_expire_days FROM iptv_channels WHERE id = $CHAND_ID ");
if(!$row_chan=mysqli_fetch_array($rc)) { error_log("Catchup can't find channel #$CHAND_ID"); die; }


$e=null;

function update_epg() { 
//    
    global $e,$row_chan,$DB;
//    
//    try {
//
//        $epg = new EgpParser('/home/aces/guide/guide.xml');
//
//    } catch (Exception $ex) {
//        logfile("Could not initalize EpgParser probably guide haven't been build yet.");
//    } 
//        
//    $e=$epg->getProgramme($row_chan['tvg_id']);

    $re=$DB->query("SELECT title,description,start_date,end_date,start_time,end_time 
            FROM iptv_epg WHERE tvg_id = '{$row_chan['tvg_id']}' AND end_date > NOW() GROUP BY start_date ");
    $e=array();
    while($row=mysqli_fetch_array($re)) $e[] = $row;
    return $e;
    
}



while(true) {

    
    while(empty($e)) { 
        if(empty($e)) update_epg();
        if(!empty($e)) break;
        if($LOG>1) logfile("Waiting For programme");
        
        sleep(60);
    }

    foreach($e as $i) { 

        if(!$DB->ping()) {
            unset($DB);
            while(true) { 
                $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                if($DB->connect_errno > 0) { sleep(5); }
                else break;
            }
        }

        $server_offset = date('Z');
        
//        $start_time = strtotime($i['start_date']);
//        $end_time = strtotime($i['end_date']);
        
        $start_time = $i['start_time'];
        $end_time = $i['end_time'];
        
        $next_programme = $end_time - (60*5);

        //$start = date('Y-m-d H:i:s',$start_time);
        //$end = date('Y-m-d H:i:s',$end_time);
        
        $start = $i['start_date'];
        $end= $i['end_date'];

        $title=null;$desc=null;
        $title = $DB->real_escape_string($i['title']);
        $desc = $DB->real_escape_string($i['description']);

        $r=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '$CHAND_ID' AND start_time = '$start' AND end_time = '$end' ");
        if(!$r->fetch_assoc()) {
            
            $rc=$DB->query("SELECT id,tvg_id,stream_server,catchup_server,catchup_expire_days FROM iptv_channels WHERE id = $CHAND_ID ");
            if($row=mysqli_fetch_array($rc)) $row_chan = $row;

            $DB->query("INSERT INTO iptv_recording (chan_id,server_id,title,description,start_time,end_time,expire_date) 
VALUES($CHAND_ID,'{$row_chan['catchup_server']}','$title','$desc', '$start', '$end', NOW() + INTERVAL {$row_chan['catchup_expire_days']} DAY ) ");
            
            if($LOG>1) { 
                logfile("TIME ".date('Y-m-d H:i:s'));
                logfile("RECORD $title  $r_id\n");
                logfile("Start : $start\n");
                logfile("End : $end\n\n");
            }
        }


        while(true) { 

            if(time() > $next_programme) break; 
            sleep(60);
            
        }

    }
    
    $e = null;
    
}
