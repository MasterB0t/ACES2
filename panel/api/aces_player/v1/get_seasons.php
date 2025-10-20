<?php

include 'main.php';

if(! isset($_GET['series_id']) || !is_numeric($_GET['series_id']) || $_GET['series_id'] < 1 ) die;

$a = [];

$r=$DB->query("SELECT id,number as season_number  FROM iptv_series_seasons WHERE series_id = '{$_GET['series_id']}' ORDER BY number ");


while($row=$r->fetch_assoc()) {

    $season = []; $episodes = [];

    $r2=$DB->query("SELECT id as episode_id, number as episode_number, title, about, logo, rate, release_date FROM iptv_series_season_episodes WHERE season_id =  {$row['id']} AND status = 1 ORDER BY number");
    while($row_episode=$r2->fetch_assoc()) {

        $row_episode['watched'] = 0;
        $r_watched = $DB->query("SELECT episode_id FROM iptv_app_watched WHERE episode_id = {$row_episode['episode_id']} AND profile_id = '{$ACCOUNT['profile_id']}'");
        if($r_watched->fetch_assoc()) $row_episode['watched'] = 1;

        $r4 = $DB->query("SELECT id FROM iptv_video_files WHERE episode_id = '{$row_episode['episode_id']}' ");
        $file_id = $r4->fetch_assoc()['id'];
        $row_episode['file_id'] = $file_id;

        $row_episode['resume_position'] = 0;
        $row_episode['resume_position_percent'] = 0;

        $r3 = $DB->query("SELECT resume_position,resume_position_percent FROM iptv_app_video_position 
                                               WHERE profile_id = '{$ACCOUNT['profile_id']}' AND file_id = '$file_id' ");
        if($row3=$r3->fetch_assoc()) {

            $row_episode['resume_position'] = $row3['resume_position'];
            $row_episode['resume_position_percent'] = $row3['resume_position_percent'];
        }


        if($row_episode['logo']) $row_episode['logo'] = "http://{$_SERVER['HTTP_HOST']}/logos/{$row_episode['logo']}";
        $episodes[] = $row_episode;


    }

    //ONLY ADD SEASON IF IT HAVE EPISODES...
    if($r2->num_rows>0)
        $a[] = array( 'season_id' => $row['id'], 'season_number' => $row['season_number'], 'episodes' => $episodes );



}

echo json_encode($a);