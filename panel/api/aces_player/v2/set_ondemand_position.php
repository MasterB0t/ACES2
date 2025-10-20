<?php

include_once 'index.php';

$file_id = (int)$_GET['file_id'];
$position = (int)$_GET['position'];
$position_percent  = (float) $_GET['position_percent'];
$clear = (int)$_GET['clear'];
$series_row = null;

if($clear)
    AcesLogD("Clearing IT");


if(!$file_id )
    set_error("NO VALID VOD ID OR MISSING");

if(!$position || !$position_percent) {
    set_completed();
    exit;
}

if($clear) {
    $DB->query("DELETE FROM iptv_app_video_position WHERE file_id = $file_id AND profile_id = '{$ACCOUNT['profile_id']}'");
    set_completed();
}

$r=$DB->query("SELECT movie_id,episode_id FROM iptv_video_files WHERE id = '$file_id' ");
$file_row = $r->fetch_assoc();
if($file_row['movie_id']) {
   $vod_id = $file_row['movie_id'];
} else {
    $r2=$DB->query("SELECT s.series_id,s.id as season_id, s.number as season_number, e.id as episode_id, e.number as episode_number FROM iptv_series_season_episodes e 
                RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id 
                WHERE e.id = {$file_row['episode_id']}
        
    ");
    $series_row = $r2->fetch_assoc();
    $vod_id = $series_row['series_id'];
    $series_id = $vod_id;
    $season_id = $series_row['season_id'];
    $season_number = $series_row['season_number'];
    $episode_number = $series_row['episode_number'];
    $season_id = $series_row['season_id'];
    $episode_id = $series_row['episode_id'];
}

$r=$DB->query("SELECT resume_position FROM iptv_app_video_position WHERE file_id = $file_id AND profile_id = '{$ACCOUNT['profile_id']}'");
if($r->fetch_assoc()) {
    $DB->query("UPDATE iptv_app_video_position SET resume_position = '$position', add_time = unix_timestamp(), resume_position_percent = '$position_percent'  WHERE file_id = $file_id AND profile_id = '{$ACCOUNT['profile_id']}' ");
} else {
    $DB->query("INSERT INTO iptv_app_video_position (profile_id,resume_position,file_id,vod_id,resume_position_percent,add_time) VALUES('{$ACCOUNT['profile_id']}','$position','$file_id','$vod_id','$position_percent',unix_timestamp())");
}
$json = array(
    'position' => $position,
    'file_id'  => $file_id
);



if($position_percent > 94 ) {

    unset($_GET);
    $_GET['file_id'] = $file_id;
    include 'set_ondemand_watched.php';


} else {

    set_completed($json);
    fastcgi_finish_request();

    //ADDING TO WATCHING...
    $r = $DB->query("SELECT vod_id FROM iptv_app_watching WHERE vod_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");
    if (!$r->fetch_assoc())
        $DB->query("INSERT INTO iptv_app_watching (vod_id,profile_id,add_time) VALUES('$vod_id','{$ACCOUNT['profile_id']}',UNIX_TIMESTAMP() ) ");
    else
        $DB->query("UPDATE iptv_app_watching SET add_time = UNIX_TIMESTAMP() WHERE vod_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}'  ");


    if($series_id) {

        $r = $DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");
        if ($r->fetch_assoc())
            $DB->query("UPDATE iptv_app_last_episode SET season_number = '$season_number', episode_number = '$episode_number', episode_id = '$episode_id', season_id = $season_id WHERE series_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

        else
            $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,episode_id,season_id,profile_id) VALUES('$vod_id', '$season_number', '$episode_number','$episode_id','$season_id', '{$ACCOUNT['profile_id']}'  )");


    }

}

exit;

if($series_row) {

    if($position_percent > 94 ) {
        //TRYING TO GET THE NEXT EPISODE FROM SAME SEASON...
        $r=$DB->query("SELECT id,number as episode_number FROM iptv_series_season_episodes WHERE season_id = '$season_id' AND number > $episode_number ORDER BY number ASC LIMIT 1 ");
        if($next_episode=$r->fetch_assoc()) {

            $r=$DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '$series_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");
            if(!$r->fetch_assoc())
                $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,season_id,episode_id,profile_id) VALUES('$series_id', '$season_number', '{$next_episode['episode_number']}','$season_id','{$next_episode['id']}', '{$ACCOUNT['profile_id']}'  )");
            else
                $DB->query("UPDATE iptv_app_last_episode SET season_number = '$season_number', episode_number = '{$next_episode['episode_number']}', episode_id = '{$next_episode['id']}', season_id = '$season_id' WHERE series_id = '$series_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");


        } else {

            $r=$DB->query("SELECT id,number as season_number FROM iptv_series_seasons WHERE series_id = '$series_id' AND number > '$season_number' ORDER BY number ASC LIMIT 1");
            if($season=$r->fetch_assoc()) {
                $r=$DB->query("SELECT number as episode_number,id, series_id FROM iptv_series_season_episodes WHERE season_id = '{$season['id']}' ORDER BY number ASC LIMIT 1 ");
                if($next_episode=$r->fetch_assoc()) {

                    $r=$DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '$series_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");
                    if(!$r->fetch_assoc())
                        $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,season_id,episode_id,profile_id) VALUES('$series_id', '{$season['season_number']}', '{$next_episode['episode_number']}','{$season['id']}','{$next_episode['id']}','{$ACCOUNT['profile_id']}'  )");
                    else
                        $DB->query("UPDATE iptv_app_last_episode SET season_number = '{$season['season_number']}', episode_number = '{$next_episode['episode_number']}', season_id = '{$season['id']}', episode_id = '{$next_episode['id']}' WHERE series_id = '$series_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

                }
            }

        }

    } else {

        $r = $DB->query("SELECT series_id FROM iptv_app_last_episode WHERE series_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");
        if ($r->fetch_assoc())
            $DB->query("UPDATE iptv_app_last_episode SET season_number = '$season_number', episode_number = '$episode_number' WHERE series_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

        else
            $DB->query("INSERT INTO iptv_app_last_episode (series_id,season_number,episode_number,profile_id) VALUES('$vod_id', '$season_number', '$episode_number', '{$ACCOUNT['profile_id']}'  )");

    }

} else if($position_percent > 94 ) {

    $r=$DB->query("SELECT movie_id FROM iptv_app_watched WHERE movie_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}'");
    if(!$r->fetch_assoc())
        $DB->query("INSERT INTO iptv_app_watched (movie_id,profile_id,time) VALUES('$voidid','{$ACCOUNT['profile_id']}',UNIX_TIMESTAMP())");

}

