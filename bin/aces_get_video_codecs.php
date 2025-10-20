<?php

include_once "/home/aces/stream/config.php";

error_reporting(0);
if(defined('DEBUG'))
    error_reporting(-1 );

$ID = (int)$argv[1];
if(!$ID) die;

$LOGFILE = "/home/aces/logs/vods/{$ID}.log";

function logfile($text) {

    global $LOGFILE;

    echo $text."\n";

    file_put_contents($LOGFILE, "\n[".date(' H:i:s Y:m:d')." ] $text \n\n", FILE_APPEND );

}

logfile("GETTING VIDEO INFO");

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) { logfile('ERROR: COULD NOT CONNECT TO DATABASE.'); DIE; }


$r=$DB->query("SELECT * FROM iptv_video_files WHERE id = $ID ");
if(!$video_file = $r->fetch_assoc() ) { logfile("Video #$ID could not be found in database."); die; }

//escapeshellarg() ESTA FUNCION FALLA EN EL PRIMER INTENTO SI EL FILENAME TIENE ACENTOS Y OTROS CARACTERES
//MUY RARO ES QUE SI SE CORRE POR COMMAND POR SEGUNDA VEZ FUNCIONA..
//$path = escapeshellarg(urldecode($video_file['source_file']));


//$path = escapeshellarg(addslashes(urldecode($video_file['source_file'])));
$path = urldecode($video_file['source_file']);
$path = str_replace("'","'\''", $path);

$duration = 0;
$bitrate = 0;
$filesize = 0;
$video_codec = '';
$resolution = '';


error_log("GETTING CODECS FROM $path");

$stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate:format=duration,bit_rate,size '$path'"),true);
if(count($stream_v_info) > 0 ) {
    $duration = round($stream_v_info['format']['duration']);
    $bitrate = (int)$stream_v_info['format']['bit_rate'];
    $filesize = (int)$stream_v_info['format']['size'];
    $video_codec = $stream_v_info['streams'][0]['codec_name'];
    $resolution = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";

    $frames_array = explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] );

    $frames = round( (int) $frames_array[0] / $frames_array[1],0)  ;

    //error_log("Frames {$frames_array[0]}/{$frames_array[1]}");

}

$stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name:stream_tags=language '$path'" ),true);
if(count($stream_a_info) > 0 ) {
    $audio_codec = $stream_a_info['streams'][0]['codec_name'];
}

$audio_tracks = [];
if(is_array($stream_a_info['streams']))
foreach($stream_a_info['streams'] as $stream ) {
    $audio_tracks[] = array('codec'=> $stream['codec_name'], 'lang' => strtolower($stream['tags']['language']));
}

$tracks = json_encode(array('audio' => $audio_tracks));

$DB->query("UPDATE iptv_video_files SET resolution = '$resolution', frames = '$frames', bitrate = '$bitrate',
                            audio_codec = '$audio_codec', video_codec = '$video_codec', duration = '$duration', 
                            tracks = '$tracks', filesize = '$filesize'  
                    
                        WHERE id = $ID  ");