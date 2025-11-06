<?php

if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) {exit(); }


include 'config.php';

error_reporting(0);
set_time_limit ( 0 );
ini_set('memory_limit','100M');

//error_log("STREAM START");

//NOTE!!! DISABLING THIS!!!... WHILE USING PIDFILE INSTEAD OF DB
ignore_user_abort(true); // (optional) FOR SOME SOFTWARE MAY DETECT LEAVE WILL ITS IS CONNECTED.


function logfile($msg) {
    if(!defined('DEBUG'))
        return ;
    error_log($msg);
}

logfile("START ");

function close($r='') { //die; unlink("/home/aces/run/conns/$ACCESS_ID"); die;

    global $ACCESS_ID, $DEVICE_ID;

    logfile("CLOSING $r");
    //exit;

    unlink("/home/aces/run/conns/$ACCESS_ID");
    include 'config.php';
    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
    if($DB->connect_errno > 0) die();
    if($DEVICE_ID) //FOR RECORDING THERE ARE NO DEVICE ID.. DO NOTHING THEN.
        $DB->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = $DEVICE_ID ");
    $DB->query("DELETE FROM iptv_access WHERE id = '$ACCESS_ID' ");;
    die;

}

function send($chk_file) {

    global $STREAM_ID,$STREAM_FOLDER,$byterate;

    clearstatcache();

    $next = (int)explode("-",$chk_file)[1];
    $next++;
    $next_chuck = "{$STREAM_ID}-{$next}.ts";
    $sleep = 0;

    if( !$fp_chk = fopen("{$STREAM_FOLDER}$chk_file", 'r'))
        close('could not open ts');

    $chuk_size = filesize($STREAM_FOLDER.$chk_file);
    $chuk_size_remain = $chuk_size;

    $smart_sleep = false; $buffer = 1024;
    if(is_file("{$STREAM_FOLDER}{$next_chuck}")) {
        $smart_sleep = true;
        $buffer = $byterate ;
    }

    //logfile("SENDING $chk_file  $chuk_size_remain ");

    while (true) {

        $be_download = microtime(true) * 1000000;

        //$out = fread($fp_chk, 1024 * 10 );
        $out = stream_get_line($fp_chk, $buffer );

        if($out) {

            echo $out;
            $sleep = 0;

            $af_download = microtime(true) * 1000000 - $be_download ;
            $chuk_size_remain = $chuk_size_remain - $buffer;
            $to_sleep = 1100000 - ($af_download);
//            if( !$smart_sleep && $to_sleep && $chuk_size_remain > $byterate  ) {
//                logfile("SLEEPING.... $to_sleep");
//                usleep($to_sleep);
//            }
            //sleep(1);

        } else {

            sleep(1);
            $sleep++;
            clearstatcache();
            if( $chuk_size != filesize($STREAM_FOLDER.$chk_file ) ) {
                $fposs = ftell($fp_chk);
                fclose($fp_chk);
                if(!$fp_chk = fopen("{$STREAM_FOLDER}$chk_file", 'r')) close("COULD NOT OPEN TS $chk_file");
                fseek($fp_chk,$fposs, SEEK_CUR );
                //$chunkToSend = filesize($STREAM_FOLDER.$chk_file) - $chuk_size;
                $chuk_size_remain = $chuk_size_remain + ( filesize($STREAM_FOLDER.$chk_file) - $chuk_size); //ADD THE DIFFERENCES CHUNK SIZE CHANGED.
                $chuk_size = filesize($STREAM_FOLDER.$chk_file);
            }

        }


        if(feof($fp_chk) && is_file("{$STREAM_FOLDER}$next_chuck") ) break;
        if( $sleep> 30 ) close( 'NO NEXT CHUNK');

    }

    fclose($fp_chk);
    //error_log("FINISHED SENDING\n\n");
}

//flush();

$STREAM_FOLDER = '/home/aces/stream_tmp/';
$TOKEN = $_GET['token'];
$ACCESS_ID = (int)$_GET['access'];

$ffmpeg = is_file('/home/aces/bin/ffmpeg') ? true : false;

$ADPT='';
$file = explode('.',$_GET['file']);
if (strpos($file[0], '_') !== false) {
    list($STREAM_ID,$RES) = explode('_',$file[0]);
    $ADPT = "_".str_replace('-','',$RES);

} else $STREAM_ID = (int)$file[0];


if( !preg_match('/^[a-zA-Z0-9-]+$/',$TOKEN) || !$ACCESS_ID || !$STREAM_ID ) die;


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }


$r=$DB->query("SELECT id,device_id FROM iptv_access WHERE id=$ACCESS_ID
                                       AND token='$TOKEN' AND chan_id = $STREAM_ID AND limit_time > NOW()  ");
if(!$row=$r->fetch_assoc())
    die;
$DEVICE_ID=$row['id'];


//CONNECTION HAVE BEEN ESTABLISHED BEFORE
if(is_file("/home/aces/run/conns/$ACCESS_ID"))
    close("NO CONNECTION FILE");

$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 60 SECOND WHERE id = $ACCESS_ID ");
$DB->close();

if(!is_dir("/home/aces/run/conns/")) mkdir("/home/aces/run/conns/",0775);
if(!is_dir("/home/aces/run/smsg/")) mkdir("/home/aces/run/smsg/",0775);
touch("/home/aces/run/conns/$ACCESS_ID");

//header('Content-Type: application/octet-stream');
//header('Transfer-Encoding: chunked');
header('content-type: video/mp2t');
header('Connection: close');
header("X - Accel - Buffering:\n no");

//ob_start();

$chk_file = null;

$chk_time = 0;
$chk_number = 0;
$chk_filemtime = array();


//NO PLAYLIST WAIT FOR THE FIRST CHUNK.
if (!is_file("{$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-.m3u8"))
    for($i=0;$i<60000;$i++) {

        clearstatcache();
        if(@filesize("{$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-0.ts") > 1 ) {

            send("{$STREAM_ID}{$ADPT}-0.ts");

            //error_log("FIRST CHUNK END");
            $chk_number=1;
            $chk_filemtime[0] = filemtime("{$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-0.ts");

            //THIS IS NOT NEEDED IF CHUNK ARE READING FROM PLAYLIST.
            //send("{$STREAM_ID}{$ADPT}-1.ts");
            //$chk_number=2;

            break;

        }

        usleep(10000);
        if($i>5999) close("NEVER START...");

    } else {

    //THIS ELSE NOT NEEDED IF GETTING FROM PLAYLIST.
    exec("tail -3 {$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-.m3u8",$out);
    //error_log(" out ".$out[2]);

    if(strpos($out[0],".ts")) {
        $chk_file = $out[0];
        $chk_number = (int)explode("-",$out[0])[1];
    } else {
        $chk_file = $out[2];
        $chk_number = (int)explode("-",$out[2])[1];
    }


    readfile($STREAM_FOLDER.$chk_file);
    //send($chk_file);

    $byterate = (int)shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1 {$STREAM_FOLDER}{$chk_file}  " );
    $byterate = $byterate / 8;
    $chk_number++;

}



while (true) {

    $chk_msg = null;
    $chk_file = null;

    //if( $chk_number > 5 ) $chk_number = 0;
    for($w=0;$w<200;$w++) {

        clearstatcache();
        $n = $chk_number;
        //if( is_file("{$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-$n.ts" ) {
        if( filesize("{$STREAM_FOLDER}{$STREAM_ID}{$ADPT}-$n.ts" ) > 1024 ) {
            $chk_file = "{$STREAM_ID}{$ADPT}-$chk_number.ts";
            break;
        }
        if($w == 100) close("LOOP END"); //COULDNT FIND NEXT CHUNK WITHING 50 SECONDS..
        usleep(500000); //SLEEP HALF SECOND..
    }

    if($chk_file) {

        if( is_file("/home/aces/run/smsg/$ACCESS_ID") ) { //logfile("THERE IS A SMSG");

            $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
            if($DB->connect_errno > 0) close("NO DB FROM SMSG");

            $chk_msg = $chk_file;

            if(!$r1=$DB->query("SELECT id,message FROM iptv_access WHERE id = $ACCESS_ID "))  close();
            if( !$row_acc=$r1->fetch_assoc() ) {  close("NO CONNECTION FROM SMSG"); }
            $MESSAGE = $row_acc['message']; unset($row_acc);

            $r_c = $DB->query("SELECT video_codec,audio_codec FROM iptv_streaming WHERE chan_id = $STREAM_ID ");
            $row_c = $r_c->fetch_assoc();

            if(empty($row_c['video_codec'])) {
                $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=codec_name, {$STREAM_FOLDER}$chk_file " ),true);
                $row_c['viedo_codec'] = $stream_v_info['streams'][0]['codec_name'];
            }

            if(empty($row_c['audio_codec'])) {
                $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_streams {$STREAM_FOLDER}$chk_file " ),true);
                $row_c['audio_codec'] = $stream_a_info['streams'][0]['codec_name'];
            }

            $e_opt = " ";
            if($row_c['audio_codec'] == 'aac' ) $e_opt = " -bsf:a aac_adtstoasc ";
            if($row_c['video_codec'] == 'h264' ) $row_c['video_codec'] = "libx264";

            $found = 0;$nc = $chk_number+2;
            for($x=0;$x<200;$x++) {
                if (is_file("{$STREAM_FOLDER}{$STREAM_ID}-$nc.ts")) {
                    $found++;
                    break;
                }
                usleep(500000);
            }

            if($found) {
                exec("ffmpeg -y -copyts  -i {$STREAM_FOLDER}$chk_file $MESSAGE  -c:a {$row_c['audio_codec']} -c:v {$row_c['video_codec']} -crf 27 -map 0 $e_opt -preset ultrafast -threads 0 -copyts -muxdelay 0 {$STREAM_FOLDER}m{$ACCESS_ID}a-{$chk_number}.ts");
                if (is_file("{$STREAM_FOLDER}m{$ACCESS_ID}a-{$chk_number}.ts")) $chk_file = "m{$ACCESS_ID}a-{$chk_number}.ts";
            }

            $MESSAGE = null;$row_c=null;$e_opt=null;
            $DB->query("UPDATE iptv_access SET  message = NULL WHERE id = $ACCESS_ID ");
            unlink("/home/aces/run/smsg/$ACCESS_ID");

            $DB->close();

        }


        send($chk_file);
        $chk_number++;

    }


    if($chk_msg) {  exec("rm {$STREAM_FOLDER}$chk_file"); }


    if(connection_status() != CONNECTION_NORMAL || connection_aborted() ) {  close('not normal or aborted'); }

    if(!is_file("/home/aces/run/conns/$ACCESS_ID")) close('no file');

    if(is_file("/home/aces/run/conns/$ACCESS_ID")) {
        unlink("/home/aces/run/conns/$ACCESS_ID");
        touch("/home/aces/run/conns/$ACCESS_ID");
    } else close(" CONNECTION KILLED ");

}


