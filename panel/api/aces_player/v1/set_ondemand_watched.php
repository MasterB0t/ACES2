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


echo json_encode(array('status' => 1 ));
fastcgi_finish_request();

if($movie_id) {

    $r=$DB->query("SELECT id FROM iptv_video_files WHERE movie_id = '$movie_id'");
    $file_id = $r->fetch_assoc()['id'];

    $r=$DB->query("SELECT movie_id FROM iptv_app_watched WHERE movie_id = '$movie_id' AND profile_id = '{$ACCOUNT['profile_id']}'");
    if(!$r->fetch_assoc())
        $DB->query("INSERT INTO iptv_app_watched (movie_id,profile_id,account_id,time) VALUES('$movie_id','{$ACCOUNT['profile_id']}','{$ACCOUNT['id']}',UNIX_TIMESTAMP())");

    $DB->query("DELETE FROM iptv_app_watching WHERE vod_id = '$movie_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

} else {

    $r=$DB->query("SELECT id FROM iptv_video_files WHERE episode_id = '$episode_id'");
    $file_id = $r->fetch_assoc()['id'];

    $r = $DB->query("SELECT episode_id FROM iptv_app_watched WHERE episode_id = '$episode_id' AND profile_id = '{$ACCOUNT['profile_id']}'");
    if (!$r->fetch_assoc())
        $DB->query("INSERT INTO iptv_app_watched (episode_id,profile_id,time) VALUES('$episode_id','{$ACCOUNT['profile_id']}',UNIX_TIMESTAMP())");

    $r = $DB->query("SELECT e.id as episode_id,e.number as episode_number,s.id as season_id,s.number as season_number,s.series_id FROM iptv_series_season_episodes e 
                        RIGHT JOIN iptv_series_seasons s ON s.id = e.season_id
                        WHERE e.id = '$episode_id' 
    ");
    $episode = $r->fetch_assoc();

    //ADDING TO WATCHING
    $r = $DB->query("SELECT vod_id FROM iptv_app_watching WHERE vod_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");
    if (!$r->fetch_assoc())
        $DB->query("INSERT INTO iptv_app_watching (vod_id,profile_id,add_time) VALUES('{$episode['series_id']}','{$ACCOUNT['profile_id']}',UNIX_TIMESTAMP() ) ");
    else
        $DB->query("UPDATE iptv_app_watching SET add_time = UNIX_TIMESTAMP() WHERE vod_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}'  ");


    //TRYING TO GET THE NEXT EPISODE FROM SAME SEASON...
    $r = $DB->query("SELECT id as episode_id ,number as episode_number , season_id FROM iptv_series_season_episodes WHERE season_id = '{$episode['season_id']}' AND number > {$episode['episode_number']} ORDER BY number ASC LIMIT 1 ");
    if ($next_episode = $r->fetch_assoc()) {

        $r = $DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");
        if (!$r->fetch_assoc())
            $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,season_id,episode_id,profile_id) VALUES('{$episode['series_id']}', '{$episode['season_number']}', '{$next_episode['episode_number']}','{$next_episode['season_id']}','{$next_episode['season_id']}', '{$ACCOUNT['profile_id']}'  )");
        else
            $DB->query("UPDATE iptv_app_last_episode SET season_number = '{$episode['season_number']}', episode_number = '{$next_episode['episode_number']}', season_id = '{$next_episode['season_id']}', episode_id = '{$next_episode['episode_id']}'  WHERE series_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");


    } else {

        $r = $DB->query("SELECT id,number as season_number FROM iptv_series_seasons WHERE series_id = '{$episode['series_id']}' AND number > '{$episode['season_number']}' ORDER BY number ASC LIMIT 1");
        if ($season = $r->fetch_assoc()) {
            $r = $DB->query("SELECT id as episode_id,number as episode_number,season_id FROM iptv_series_season_episodes WHERE season_id = '{$season['id']}' ORDER BY number ASC LIMIT 1 ");
            if ($next_episode = $r->fetch_assoc()) {

                $r = $DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");
                if (!$r->fetch_assoc())
                    //$DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,profile_id) VALUES('{$episode['series_id']}', '{$season['season_number']}', '{$next_episode['episode_number']}', '{$ACCOUNT['profile_id']}'  )");
                    $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,season_id,episode_id,profile_id) VALUES('{$episode['series_id']}', '{$episode['season_number']}', '{$next_episode['episode_number']}','{$next_episode['season_id']}','{$next_episode['season_id']}', '{$ACCOUNT['profile_id']}'  )");

                else
                    //$DB->query("UPDATE iptv_app_last_episode SET season_number = '{$season['season_number']}', episode_number = '{$next_episode['episode_number']}' WHERE series_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");
                    $DB->query("UPDATE iptv_app_last_episode SET season_number = '{$episode['season_number']}', episode_number = '{$next_episode['episode_number']}', season_id = '{$next_episode['season_id']}', episode_id = '{$next_episode['episode_id']}'  WHERE series_id = '{$episode['series_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' ");


            }
        }

    }
}


$DB->query("DELETE FROM iptv_app_video_position WHERE file_id = '$file_id'");

