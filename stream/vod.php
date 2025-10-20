<?php

error_reporting(0);
if(defined('DEBUG'))
    error_reporting(E_ALL);

set_time_limit(0); // Reset time limit for big files


list($ACCESS_ID,$token) = explode(':',$_GET['token']);
if(!preg_match('/^[a-zA-Z0-9-]{32,64}+$/',$token)) die;
if(!is_numeric($ACCESS_ID)) die;

$file = explode('.',$_GET['file']);
$CHANID = (int)$file[0];
$TYPE= $file[1];


include 'config.php';
$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }



$r=$DB->query("SELECT device_id FROM iptv_access 
          WHERE id=$ACCESS_ID AND token='$token' AND vod_id = $CHANID AND end_time > NOW() ");
if(!$row=$r->fetch_assoc()) {
    exit;
} else
    $DEVICE_ID=(int)$row['device_id'];



register_shutdown_function(function(){
    global $DB, $ACCESS_ID,  $DEVICE_ID, $fp;

    if($fp)
        fclose($fp);

    if($DEVICE_ID)
        $DB->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = '$DEVICE_ID'");


    $DB->query("UPDATE iptv_access SET limit_time = NOW() + interval 1 MINUTE, end_time = NOW() + interval 10 MINUTE
                   WHERE id=$ACCESS_ID ");

});


switch ($_GET['type']) {

    case 'record':

        $r=$DB->query("SELECT id,container FROM iptv_recording WHERE id = $CHANID ");
        if(!$row=$r->fetch_assoc())
            exit;

        $ext = $row['container'] ? $row['container'] : 'ts';

        $file_id = $row['id'];
        $file = "/home/aces/recordings/r$CHANID.$ext";


        break;

    default :

        $r=$DB->query("SELECT id,container FROM iptv_video_files WHERE id = $CHANID ");
        if(!$row=$r->fetch_assoc())
            exit;

        $ext = $row['container'] ? $row['container'] : 'mp4';
        $file_id = $row['id'];

        $file = "/home/aces/vods/$CHANID.$ext";


        break;

}


if($ext == 'ts')
    $mime = "application/octet-stream";
else if( $ext == 'mkv')
    $mime = 'video/x-matroska';
else
    $mime = "video/mp4";


//$videostream = new VideoStream($file);
//$videostream->start();
//exit;

if(!is_file($file)) {
    if($_GET['type'] != 'record')
        $DB->query("INSERT INTO iptv_video_reports (file_id, type , report_date, report_time) 
            VALUES('$file_id', 0, NOW(), UNIX_TIMESTAMP() ) ");
    exit;
}


if(!$fp = fopen($file,'rb')){
    error_log("File '$file' could not be open.");
    exit;
}


$size = filesize($file);
$start = 0;
$end = $size - 1;
$buffer = 102400;



ob_get_clean();
header("Content-Type: $mime");
header("Cache-Control: no-cache");
//header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
//header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
//header("Accept-Ranges: 0-".$size);
header("Accept-Ranges: bytes");


if (isset($_SERVER['HTTP_RANGE'])) {

    $c_start = $start;
    $c_end = $end;

    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    if ($range == '-') {
        $c_start = $size - substr($range, 1);
    } else{
        $range = explode('-', $range);
        $c_start = $range[0];

        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
    }
    $c_end = ($c_end > $end) ? $end : $c_end;
    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$size");
        exit;
    }
    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    fseek($fp, $start);
    header('HTTP/1.1 206 Partial Content');
    header("Content-Length: ".$length);
    header("Content-Range: bytes $start-$end/".$size);
}
else  {
    header("Content-Length: ".$size);
}



$i = $start;
$checkDB = time() + 10;
while(!feof($fp) && $i <= $end) {
    $bytesToRead = $buffer;
    if(($i+$bytesToRead) > $end) {
        $bytesToRead = $end - $i + 1;
    }
    $data = fread($fp, $bytesToRead);
    echo $data;
    flush();

    $i += $bytesToRead;
    if(time() > $checkDB ) {
        $DB->query("UPDATE iptv_access SET limit_time = NOW() + 
                                    INTERVAL 60 SECOND, end_time = NOW() + interval 1 HOUR WHERE id = $ACCESS_ID ");

        if($DB->affected_rows < 1) {
            exit;
        }
        $checkDB = time() + 10;
    }
}




//class VideoStream
//{
//    private $path = "";
//    private $stream = "";
//    private $buffer = 102400;
//    private $start  = -1;
//    private $end    = -1;
//    private $size   = 0;
//
//    function __construct($filePath)
//    {
//        $this->path = $filePath;
//    }
//
//    /**
//     * Open stream
//     */
//    private function open()
//    {
//        if (!($this->stream = fopen($this->path, 'rb'))) {
//            die('Could not open stream for reading');
//        }
//
//    }
//
//    /**
//     * Set proper header to serve the video content
//     */
//    private function setHeader()
//    {
//        ob_get_clean();
//        //header("Content-Type: video/mp4");
//        header("Content-Type: video/x-matroska");
//        header("Cache-Control: no-cache");
//        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
//        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
//        $this->start = 0;
//        $this->size  = filesize($this->path);
//        $this->end   = $this->size - 1;
//        //header("Accept-Ranges: 0-".$this->end);
//        header("Accept-Ranges: bytes");
//
//        if (isset($_SERVER['HTTP_RANGE'])) {
//
//            $c_start = $this->start;
//            $c_end = $this->end;
//
//            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
//            if (strpos($range, ',') !== false) {
//                header('HTTP/1.1 416 Requested Range Not Satisfiable');
//                header("Content-Range: bytes $this->start-$this->end/$this->size");
//                exit;
//            }
//            if ($range == '-') {
//                $c_start = $this->size - substr($range, 1);
//            }else{
//                $range = explode('-', $range);
//                $c_start = $range[0];
//
//                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
//            }
//            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
//            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
//                header('HTTP/1.1 416 Requested Range Not Satisfiable');
//                header("Content-Range: bytes $this->start-$this->end/$this->size");
//                exit;
//            }
//            $this->start = $c_start;
//            $this->end = $c_end;
//            $length = $this->end - $this->start + 1;
//            fseek($this->stream, $this->start);
//            header('HTTP/1.1 206 Partial Content');
//            header("Content-Length: ".$length);
//            header("Content-Range: bytes $this->start-$this->end/".$this->size);
//        }
//        else
//        {
//            header("Content-Length: ".$this->size);
//        }
//
//    }
//
//    /**
//     * close curretly opened stream
//     */
//    private function end()
//    {
//        fclose($this->stream);
//        exit;
//    }
//
//    /**
//     * perform the streaming of calculated range
//     */
//    private function stream()
//    {
//        $i = $this->start;
//        set_time_limit(0);
//        while(!feof($this->stream) && $i <= $this->end) {
//            $bytesToRead = $this->buffer;
//            if(($i+$bytesToRead) > $this->end) {
//                $bytesToRead = $this->end - $i + 1;
//            }
//            $data = fread($this->stream, $bytesToRead);
//            echo $data;
//            flush();
//            $i += $bytesToRead;
//        }
//    }
//
//    /**
//     * Start streaming video content
//     */
//    function start()
//    {
//        $this->open();
//        $this->setHeader();
//        $this->stream();
//        $this->end();
//    }
//}