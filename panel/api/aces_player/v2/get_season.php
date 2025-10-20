<?php

unset($_GET['action']);
require_once 'index.php';

$series_id = (int)$_GET['series_id'];
$season_number = (int)$_GET['season_number'];

if(!$series_id || !$season_number)
    set_error("Bad Request", \ACES\HttpStatusCode::BAD_REQUEST);

//CONFIRM SERIES IS IN BOUQUET
$r=$DB->query("SELECT s.id,s.number,s.logo,s.air_date,s.overview,s.air_date,s.tmdb_id FROM iptv_series_seasons s 
                RIGHT JOIN iptv_ondemand_in_bouquet b ON b.video_id = $series_id                                                       
                WHERE s.series_id = $series_id AND s.number = '$season_number' AND 
                      b.bouquet_id IN ('{$ACCOUNT['bouquets']}') ");

if(!$season=$r->fetch_assoc()) {
    set_error("Not Found", \ACES\HttpStatusCode::NOT_FOUND);
}


$episodes=[];
$r=$DB->query("SELECT id,number,title,about as plot,runtime_seconds,logo as image,rate,release_date,tmdb_id 
FROM iptv_series_season_episodes WHERE season_id = '{$season['id']}' AND status = 1 ");
while($episode = $r->fetch_assoc()) {

    if(!filter_var($episode['image'], FILTER_VALIDATE_URL))
        $episode['image'] = $episode['image'] ? $HOST."/logos/$episode[image]" : null;

    $r4=$DB->query("SELECT episode_id FROM iptv_app_watched WHERE episode_id = '{$episode['id']}' AND profile_id = '{$ACCOUNT['profile_id']}'  ");
    $episode['watched'] = (bool)$r4->fetch_assoc();

    $r_file = $DB->query("SELECT id,resolution,tracks,container,audio_codec,video_codec,bitrate 
                            FROM iptv_video_files WHERE episode_id = '{$episode['id']}' ");
    $file = $r_file->fetch_assoc();
    $file['container'] = $file['container'] ?: "mp4";
    $file['video_url'] = $HOST."/series/$USERNAME/$PASSWORD/{$episode['id']}.{$file['container']}";

    $r3 = $DB->query("SELECT resume_position,resume_position_percent as resume_percent FROM iptv_app_video_position 
                                               WHERE profile_id = '{$ACCOUNT['profile_id']}' AND file_id = '{$file['id']}' ");
    $resume = $r3->fetch_assoc();

    $file['resume_position'] = (int)$resume['resume_position'];
    $file['resume_percent'] = $episode['watched'] && $resume['resume_position'] == 0 ? 100 : (int)$resume['resume_percent'];
    //$file['resume_percent'] = (int)$resume['resume_percent'];

    $episode['video_file'] = $file;


    $season['episodes'][] = array_merge($episode);

}

echo json_encode($season);

