<?php

unset($_GET['action']);
require_once 'index.php';

$type = $_GET['type'] == 'series' ? 'series' : 'movies';
$vod_id = (int)$_GET['vod_id'];

if(!$vod_id)
    setAjaxError("No Video ID");

$r=$DB->query("SELECT tmdb_id,type FROM iptv_ondemand WHERE id = '$vod_id'");
if(!$vod=$r->fetch_assoc())
    setAjaxError("VOD #$vod_id not found");

$type = $vod['type'] == 'series' ? 'series' : 'movies';

$TMDB = new \Tmdb();
$info = $TMDB->getCredits($vod['tmdb_id'], $type);

$data = [];
$data['cast'] = [];
$data['director'] = [];
$data['writer'] = [];

foreach($info['cast'] as $c ) {
    $data['cast'][] = $c;
}

foreach ($info['crew'] as $c ) {
    if($c['job'] == 'Director' )
        $data['director'][] = $c;
    else if($c['job'] = 'Writer') {
        $data['writer'][] = $c;
    }
}

echo json_encode(
    array('status' => true, 'error_message' => '',
        'cast' => $data['cast'] , 'directors' => $data['director'] , 'writers' => $data['writer']));