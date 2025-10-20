<?php

include_once 'main.php';

$episode_id = (int)$_GET['episode_id'];
$movie_id = (int)$_GET['movie_id'];


if(!$episode_id && !$movie_id)
    set_error();

$column = 'movie_id'; $val = $movie_id;
if($episode_id) {
    $val = $episode_id;
    $column = 'episode_id';
}

if($movie_id)
    $DB->query("DELETE FROM iptv_app_watching WHERE vod_id = '$movie_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

else {

    $r = $DB->query("SELECT e.id as episode_id,e.number as episode_number,s.id as season_id,s.number as season_number,s.series_id FROM iptv_series_season_episodes e 
                        RIGHT JOIN iptv_series_seasons s ON s.id = e.season_id
                        WHERE e.id = '$episode_id' 
    ");
    $episode = $r->fetch_assoc();

    $DB->query("DELETE FROM iptv_app_watching WHERE vod_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");

}

echo json_encode(array('status'=> 1));