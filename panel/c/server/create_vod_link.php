<?php

header('Content-Type: application/json');
header('Connection: close');

$data = json_decode(base64_decode($_REQUEST['cmd']),1);
$cmd = '';

//error_log("CREATE LINK ");
//error_log(print_r($data,1));

if($data['type'] == 'series' ) {

    $season_id = (int)$data['season_id'];
    $episode_number = (int)$_GET['series'];
    $r=$DB->query("SELECT id FROM iptv_series_season_episodes WHERE season_id = '$season_id'  AND number = '$episode_number' ");
    if($media_id=$r->fetch_assoc()['id']) $cmd = "http://{$_SERVER['HTTP_HOST']}/series/{$MAG_USERNAME}/{$MAG_TOKEN}/$media_id.mp4";

} else  {

    $media_id = $data['movie_id'];
    $cmd = "http://{$_SERVER['HTTP_HOST']}/movie/{$MAG_USERNAME}/{$MAG_TOKEN}/$media_id.mp4";

}


//if(!empty($_GET['series'])) {
//    $r=$DB->query("SELECT id FROM iptv_video_files WHERE episode_id = $media_id ");
//    $cmd = "http://{$_SERVER['HTTP_HOST']}/series/{$MAG_USERNAME}/{$MAG_TOKEN}/$media_id.mp4";
//} else {
//    $r=$DB->query("SELECT id FROM iptv_ondemand WHERE id = $media_id");
//    $cmd = "http://{$_SERVER['HTTP_HOST']}/movie/{$MAG_USERNAME}/{$MAG_TOKEN}/$media_id.mp4";
//}

if(empty($cmd)) { echo '{"js":[]}'; die; }


$js = array (
  'js' => 
  array (
    'id' => $media_id,
    'cmd' => "ffmpeg $cmd",
    'storage_id' => '',
    'load' => '',
    'error' => '',
    'subtitles' => 
    array (
    ),
  ),
  'text' => '',
);

echo json_encode($js);
die;