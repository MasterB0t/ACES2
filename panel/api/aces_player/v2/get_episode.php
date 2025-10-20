<?php
unset($_GET['action']);
require_once 'index.php';


$EpisodeID = (int)$_GET['episode_id'];


$r=$DB->query("SELECT e.id,e.number,e.title,e.about as plot,e.runtime_seconds,e.logo as image, e.rate,e.release_date, 
       e.tmdb_id, s.id as next_season_id
 FROM iptv_series_season_episodes e 
 RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id 
 RIGHT JOIN iptv_ondemand o ON s.series_id = o.id 
 WHERE e.id = $EpisodeID  ");

if(!$episode=$r->fetch_assoc())
    set_error("Not Found", \ACES\HttpStatusCode::NOT_FOUND);

if(!filter_var($episode['image'], FILTER_VALIDATE_URL))
    $episode['image'] = $episode['image'] ? $HOST."/logos/{$episode['image']}" : null;

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
#$file['resume_percent'] = (int)$resume['resume_percent'];
$file['resume_percent'] = $episode['watched'] && $resume['resume_position'] == 0 ? 100 : (int)$resume['resume_percent'];

//$episode['next_episode'] = getEpisode($episode['next_season_id']);

$episode['video_file'] = $file;

echo json_encode($episode);