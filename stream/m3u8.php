<?php


if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) { exit(); }

//ob_start();

//error_reporting(0);
set_time_limit ( 0 );
ignore_user_abort(true);

//ob_implicit_flush(1);


$token = $_GET['token'];
$ACCESS_ID = $_GET['access'];

if(strlen($token) != 32 && strlen($token) != 64 ) die;
if(!preg_match('/^[a-zA-Z0-9-]+$/',$token)) die;
if(!is_numeric($ACCESS_ID)) die;

$ADPT='';
$file = explode('.',$_GET['file']);
if (strpos($file[0], '_') !== false) {
    list($CHANID,$RES) = explode('_',$file[0]);
    $ADPT = "_".str_replace('-','',$RES);

} else $CHANID = (int)$file[0];

$TYPE= $file[1];

if(!is_numeric($CHANID)) die;

$FOLDER_TMP = "/home/aces/stream_tmp/";

if(!empty($_GET['streamer'])) {

    $FOLDER_TMP = "/home/aces/stream_tmp_clients/";
    $CHANID = $ACCESS_ID;
}

clearstatcache();

if(!is_dir("/home/aces/run/conns/")) mkdir("/home/aces/run/conns/",0775);
if(!is_dir("/home/aces/run/smsg/")) mkdir("/home/aces/run/smsg/",0775);

//WAITING FOR PLAYLIST IF ITS ONDEMAND.
$wait_time = time() + 15;
while(!is_file("{$FOLDER_TMP}{$CHANID}{$ADPT}-.m3u8")) {
    usleep(100);
    if($wait_time < time() )
        die;
}

//if( (fileatime("{$FOLDER_TMP}{$CHANID}{$ADPT}-.m3u8") + 60 ) < time() ){ die; }

if($TYPE == 'm3u8') {

    //ob_start();

    #header('Content-type: application/vnd.apple.mpegurl' );
    #header('Access-Control-Allow-Origin: *');
    #header('Content-type: application/force-download');
    header('Content-Type: application/vnd.apple.mpegurl');

    #header("Content-Disposition: attachment; filename={$CHANID}.m3u8");
    header('Content-Length: ' . filesize("{$FOLDER_TMP}{$CHANID}{$ADPT}-.m3u8"));
    header('Connection: keep-alive');
    #header('Accept-Ranges: bytes');
    //header('Access-Control-Allow-Origin: *');

    readfile("{$FOLDER_TMP}{$CHANID}{$ADPT}-.m3u8");

    die;

} else {

    include 'config.php';

    if(!is_file("/home/aces/run/conns/$ACCESS_ID")) {

        $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
        if($DB->connect_errno > 0){ die(); }

        //$r=$DB->query("SELECT id,message FROM iptv_access WHERE id=$ACCESS_ID AND token='$token' AND chan_id = $CHANID AND limit_time > NOW() AND  ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
        $r=$DB->query("SELECT id,message FROM iptv_access WHERE id=$ACCESS_ID AND token='$token' AND chan_id = $CHANID AND limit_time > NOW() ");

        if(!($row=$r->fetch_assoc())) die;

        if(!file_put_contents("/home/aces/run/conns/$ACCESS_ID","$ACCESS_ID:$token")) {
          $DB->query("DELETE FROM iptv_access WHERE id=$ACCESS_ID ");
          die;
        }

    } else {

      $content = file_get_contents("/home/aces/run/conns/$ACCESS_ID");
      if($content != "$ACCESS_ID:$token") {
        $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
        if($DB->connect_errno > 0) die();
        $DB->query("DELETE FROM iptv_access WHERE id=$ACCESS_ID ");
        die;
      }
      unlink("/home/aces/run/conns/$ACCESS_ID");
      file_put_contents("/home/aces/run/conns/$ACCESS_ID","$ACCESS_ID:$token");

    }

    if( is_file("/home/aces/run/smsg/$ACCESS_ID") ) {

        $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
        if($DB->connect_errno > 0){ die(); }

        $r=$DB->query("SELECT id,message FROM iptv_access WHERE id=$ACCESS_ID  ");
        $MESSAGE = $r->fetch_assoc()['message'];

        $r_c = $DB->query("SELECT video_codec,audio_codec FROM iptv_streaming WHERE chan_id = $CHANID ");
        $row_c = $r_c->fetch_assoc();

        if(empty($row_c['video_codec'])) {
            $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=codec_name, {$FOLDER_TMP}{$_GET['file']} " ),true);
            $row_c['viedo_codec'] = $stream_v_info['streams'][0]['codec_name'];
        }

        if(empty($row_c['audio_codec'])) {
            $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_streams {$FOLDER_TMP}{$_GET['file']} " ),true);
            $row_c['audio_codec'] = $stream_a_info['streams'][0]['codec_name'];
        }

        $e_opt = " ";
        if($row_c['audio_codec'] == 'aac' ) $e_opt = " -bsf:a aac_adtstoasc ";
        if($row_c['video_codec'] == 'h264' ) $row_c['video_codec'] = "libx264";

        //$a = time();
        //error_log("ENCODING...");

        //exec("ffmpeg -y  -i {$FOLDER_TMP}{$_GET['file']} -vf drawtext=\"fontfile=/usr/share/fonts/cantarell/Cantarell-Regular.otf: text='ACCOUND ID {$ACCESS_ID}!!!': fontcolor=white: fontsize=32: box=1: boxcolor=black@0.7: boxborderw=7: x=(w-text_w)/2: y=(h-text_h)/2\" -c:a {$row_c['audio_codec']} -c:v {$row_c['video_codec']} -map 0 $e_opt -threads 1 -preset ultrafast  -copyts -muxdelay 0  {$FOLDER_TMP}m{$ACCESS_ID}.ts ");
        //exec("ffmpeg -y  -i {$FOLDER_TMP}{$_GET['file']} {$MESSAGE}  -c:a {$row_c['audio_codec']} -c:v {$row_c['video_codec']} -map 0 $e_opt -threads 1 -preset ultrafast  -copyts -muxdelay 0  {$FOLDER_TMP}m{$ACCESS_ID}.ts 2>  /home/aces/logs/msgEncoder.txt  ");

        exec("ffmpeg -y  -i {$FOLDER_TMP}{$_GET['file']} {$MESSAGE}  -c:a {$row_c['audio_codec']} -c:v {$row_c['video_codec']} -crf 29 -map 0 $e_opt  -preset ultrafast -threads 0  -copyts -muxdelay 0  {$FOLDER_TMP}m{$ACCESS_ID}.ts ");

        if(is_file($FOLDER_TMP."m{$ACCESS_ID}.ts")) $_GET['file'] = "m{$ACCESS_ID}.ts";

        $DB->query("UPDATE iptv_access SET  message = NULL WHERE id = $ACCESS_ID ");
        unlink("/home/aces/run/smsg/$ACCESS_ID");

        //$t = time() - $a ;
        //error_log("DONE ECODING $t");

    }

    $size = filesize("{$FOLDER_TMP}{$_GET['file']}");

    #header('Content-Type: application/octet-stream');
    header('Content-Type: video/mp2t');

    #header('Transfer-Encoding: chunked');
    header('Content-Length: ' . $size );
    //header('Access-Control-Allow-Origin: *');
    #header('Accept-Ranges: bytes');
    header('Accept-Ranges: '. 0 .'-'. $size );

    ob_start();
    if(!$handle = fopen("{$FOLDER_TMP}{$_GET['file']}", 'rb')) { die; }
//    while (!feof($handle))
//    {
//        if(!$buffer = fread($handle, 1024 * 1024 )) die;
//        //echo $buffer;
//        //@ob_flush();
//        ////@flush();
//        //@ob_clean();
//
//    }

    fpassthru($handle);
    #readfile("{$FOLDER_TMP}{$_GET['file']}");
    @ob_flush();@ob_clean();
    fastcgi_finish_request();
    fclose($handle);

    $e_sql = '';
    if($row['message']) $e_sql = " , message = null " ;

    //$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 60 SECOND $e_sql WHERE id = $ACCESS_ID ");

    if($row['message']) exec("rm {$FOLDER_TMP}m{$ACCESS_ID}.ts");

}
