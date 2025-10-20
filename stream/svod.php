<?php

error_reporting(0);
if(defined('DEBUG'))
    error_reporting(-1);


$CURL = null;

require_once( $_SERVER['DOCUMENT_ROOT']."/panel/functions/logs.php" );
require_once ( $_SERVER['DOCUMENT_ROOT']."/panel/ACES2/Curl.php" );

class VideoStream {
    private $url = "";
    private $sizeReq = 0;
    private $curlStream = NULL;
    private $followRedirects = true; //maybe disable if not trusted
    private $start = -1;
    private $end = -1;
    private $size = -1;

    function __construct($URL, $FileSize = null)
    {
        $this->sizeReq = 10 * 1000000; //get 10MB per range request
        $this->url = $URL;
        //if(!$FileSize)
            $this->setSize(); //set the size and check if the video is available at the same time

        $this->size = $FileSize;

        //A LITTLE HACK TO KEEP LOOP IN THE SCRIPT
        $this->sizeReq = $this->size;
    }


    /**
     * Set proper header to serve the video content
     */
    private function setHeader()
    {

        header("Content-Type: video/mp4");
        //header("Cache-Control: no-cache");
        //header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $this->start = 0;

        $this->end = $this->size;
        header("Accept-Ranges: 0-" . ($this->size + 1));
        //header("Accept-Ranges: bytes");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $ranges = explode("-", explode("=", $_SERVER['HTTP_RANGE'])[1]); //explode twice to get the ranges in an array
            $this->start = $ranges[0]; //starting range

            if ($ranges[1] === "") { //no end specified, set it ourselves
                $this->end = $ranges[0] + $this->sizeReq;
                if ($this->end > $this->size) $this->end = $this->size; //set it to the end if the request would be too big
            } else { //set it to the requested length
                $this->end = $ranges[1];
            }

            $length = $this->end - $this->start + 1;

            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: " . $length);
            header("Content-Range: bytes $this->start-$this->end/" . ($this->size + 1));

        } else {
            header("Content-Length: " . ($this->end - $this->start + 1));
        }


    }

    /**
     * close curretly opened stream
     */
    private function end()
    {
        logD("END....");
        curl_close($this->curlStream);
        exit;
    }

    /**
     * perform the streaming of calculated range
     */
    private function stream()
    {
        global $CURL;
        $splitter = new SplitCurlByLines();

        ob_end_flush();

        $this->curlStream = curl_init();
        $CURL = $this->curlStream;
        curl_setopt($this->curlStream, CURLOPT_URL, $this->url);
        curl_setopt($this->curlStream, CURLOPT_WRITEFUNCTION, array($splitter, 'curlCallback')); //we need this so we "live"stream our results
        curl_setopt($this->curlStream, CURLOPT_ENCODING, 'gzip, deflate');

        //curl_setopt($this->curlStream, CURLOPT_BUFFERSIZE, 104857640);
        //curl_setopt($this->curlStream, CURLOPT_FOLLOWLOCATION);

        $headers = array();
        $headers[] = "Pragma: no-cache";
        $headers[] = "Dnt: 1";
        $headers[] = "Accept-Encoding: identity;q=1, *;q=0";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36";
        $headers[] = "Accept: */*";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Connection: keep-alive";
        $headers[] = "Range: bytes={$this->start}-{$this->end}";
        logD("Range: bytes={$this->start}-{$this->end}");
        curl_setopt($this->curlStream, CURLOPT_HTTPHEADER, $headers);
        logD("EXEC CURL" );
        curl_exec($this->curlStream);

        $splitter->processLine($splitter->currentLine);
    }

    /**
     * get the size of the external video
     */
    private function setSize()
    {

        global $DB, $ACCESS_ID;

        $curl = new \ACES2\Curl();
        $curl->setUserAgent("VLC/3.0.21 LibVLC/3.0.21");
        $curl->setNoBody();
        $curl->setRequestType('GET');
        $tries = 0;
        while(true) {
            $curl->setUrl($this->url);
            $curl->get();
            $headers = $curl->getResponseHeadersInArray();
            if(isset($headers['Location']) && $this->url != $headers['Location']) {
                $this->url = $headers['Location'];
                logD("New Location : $this->url");
            } else {
                logD("Content Length ".$headers["Content-Length"]);
                $this->size = $headers["Content-Length"];
                break;
            }

            $tries++;
            if($tries>5)
                die;

        };

        $DB->query("UPDATE iptv_access SET svod_url = '$this->url' WHERE id = '$ACCESS_ID'");
    }

    /**
     * Start streaming video content
     */
    function start()
    {
        $this->setHeader();
        logD("HEADER SET");
        $this->stream();
        $this->end();
    }
}

class SplitCurlByLines {

    public function __construct() {
        $this->checkDb  = time() + 10;
    }

    public function curlCallback($curl, $data)
    {

        $this->currentLine .= $data;
        $lines = explode("\n", $this->currentLine);
        // The last line could be unfinished. We should not
        // proccess it yet.
        $numLines = count($lines) - 1;
        $this->currentLine = $lines[$numLines]; // Save for the next callback.

        for ($i = 0; $i < $numLines; ++$i) {
            $this->processLine($lines[$i]); // Do whatever you want
            ++$this->totalLineCount; // Statistics.
            $this->totalLength += strlen($lines[$i]) + 1;
        }
        return strlen($data); // Ask curl for more data (!= value will stop).

    }

    public function processLine($str) {

        global $ACCESS_ID, $DB;

        clearstatcache();
        if(time() > $this->checkDb) {

            //logD("Checking DB ");

            $DB->query("UPDATE iptv_access SET limit_time = NOW() + 
                                    INTERVAL 60 SECOND, end_time = NOW() + interval 1 HOUR WHERE id = $ACCESS_ID ");

            if($DB->affected_rows < 1) {
                exit;
            }

            $this->checkDb = time() + 2;

        }

        echo $str . "\n";
        //echo $str ;
    }

    public $currentLine = '';
    public $totalLineCount = 0;
    public $totalLength = 0;
    public $checkDb = 0;
}


set_time_limit(-1);
ini_set('memory_limit', '4064M');

list($ACCESS_ID,$token) = explode(':',$_GET['token']);
if(!preg_match('/^[a-zA-Z0-9-]{32,64}+$/',$token)) die;
if(!is_numeric($ACCESS_ID)) die;

$file = explode('.',$_GET['file']);
if(!$CHANID = (int)$file[0])
    die;


include_once 'config.php';
$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die();


$r=$DB->query("SELECT device_id,svod_url FROM iptv_access 
          WHERE id=$ACCESS_ID AND token='$token' AND vod_id = $CHANID AND end_time > NOW() ");
if(!$row=$r->fetch_assoc())
    die;


$DEVICE_ID = $row['device_id'];
$svod_url = $row['svod_url'];

$r=$DB->query("SELECT source_file,filesize FROM iptv_video_files WHERE id = '$CHANID' ");
if(!$row=$r->fetch_assoc())
    die;

register_shutdown_function(function(){
    global $DB, $ACCESS_ID, $ACCESS_KEY, $DEVICE_ID, $CURL;

    if($DEVICE_ID)
        $DB->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = '$DEVICE_ID'");

    //ACCESS CANNOT BE REMOVED FOR STREAM VODS HERE
    $DB->query("UPDATE iptv_access SET limit_time = NOW() - interval 1 MINUTE, end_time = NOW() + interval 1 HOUR
                   WHERE id=$ACCESS_ID ");

});




$UrlStream = $row['source_file'];
$buffer = 10 * 100000;
$FileSize = (int)$row['filesize'];





//$curl = new \ACES2\Curl();
//$curl->setUserAgent("VLC/3.0.21 LibVLC/3.0.21");
//$curl->setNoBody();
//$curl->setRequestType('GET');
//while(true) {
//    $curl->setUrl($UrlStream);
//    $curl->get();
//    $headers = $curl->getResponseHeadersInArray();
//    if(isset($headers['Location']) && $UrlStream != $headers['Location']) {
//        //$curl->setReferred($UrlStream);
//        $UrlStream = $headers['Location'];
//        logD("New Location : $UrlStream");
//    } else {
//        break;
//    }
//
//
//} die;







//function getFileSize()
//{
//    global $UrlStream, $FileSize;
//
//    $headers = get_headers($UrlStream, 1);
//    if (isset($headers["Location"]) ) { //following the rabit hole, might be risky
//        $UrlStream = $headers["Location"];
//        getFileSize();
//        return;
//    }
//    if (strpos($headers[0], '200 OK') === false) { //URL is not OK
//        throw new Exception("URL not valid, not a 200 response code");
//    }
//    if (!isset($headers["Content-Length"])) {
//        throw new Exception("URL not valid, could not find the video size");
//    }
//    $FileSize = (int)$headers["Content-Length"];
//
//}

//getFileSize();

//header("Content-Type: video/mp4");
////header("Cache-Control: no-cache");
////header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
//
//header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
//header("Cache-Control: post-check=0, pre-check=0", false);
//header("Pragma: no-cache");
//header("Accept-Ranges: 0-" . ($FileSize + 1));
//
//$start =0;$end = 0;
//if (isset($_SERVER['HTTP_RANGE'])) {
//    $ranges = explode("-", explode("=", $_SERVER['HTTP_RANGE'])[1]); //explode twice to get the ranges in an array
//    $start = $ranges[0]; //starting range
//
//    if ($ranges[1] === "") { //no end specified, set it ourselves
//        $end = $ranges[0] + $buffer;
//        if ($end> $FileSize) $end = $FileSize; //set it to the end if the request would be too big
//    } else { //set it to the requested length
//        $end = $ranges[1];
//    }
//
//    $length = $end - $start + 1;
//
//    header('HTTP/1.1 206 Partial Content');
//    //header("Content-Length: " . $length);
//    header("Content-Length: " . $length );
//    header("Content-Range: bytes $start-$end/" . ($FileSize + 1));
//} else {
//    header("Content-Length: " . ($end - $start + 1));
//}
//
//$start = +1;
//while (@ ob_end_flush()); // end all output buffers if any
//$range_bytes = $start> 0 ? "-c --header=\"Range: bytes=$start-$end\"": '';
//$proc = popen( "wget -O - -o /dev/null '{$row['source_file']}' ", 'r');
//
//while (!feof($proc))
//{
//    echo fread($proc, 80960);
//    @flush();
//}
//
//
//exit;


$url = $svod_url ? $svod_url : $row['source_file'] ;
$stream = new VideoStream($url, $FileSize);
$stream->start();
