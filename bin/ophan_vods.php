<?php


include '/home/aces/stream/config.php';
include "/home/aces/panel/includes/init.php";

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die();

$DO_DELETE = true;

//$r_sources = $DB->query("SELECT id,episode_id FROM iptv_video_files WHERE episode_id > 0 ");
//while($row_source = $r_sources->fetch_array()){
//
//    $delete = false;
//    $source_id = $row_source['id'];
//    $episode_id = $row_source['episode_id'];
//    $season_id = 0;
//    $series_id = 0;
//
//
//    while(true) {
//
//        $r_episode = $DB->query("SELECT season_id FROM iptv_series_season_episodes
//                 WHERE id = $episode_id ");
//
//        if($r_episode->num_rows < 1) {
//            echo "NO EPISODE FOUND #$episode_id\n";
//            $delete = true;
//            break;
//        }
//
//        $season_id = $r_episode->fetch_array()['season_id'];
//        $r_season = $DB->query("SELECT series_id  FROM iptv_series_seasons WHERE id = $season_id ");
//        if(!$series_id = $r_season->fetch_array()['series_id']) {
//            echo "NO SEASON FOUND #$season_id\n";
//            $delete = true;
//            break;
//        }
//
//        $r_series = $DB->query("SELECT id FROM iptv_ondemand WHERE id = $series_id ");
//        if(!$r_series->fetch_assoc()) {
//            echo "NO SERIES FOUND #$series_id\n";
//            $delete = true;
//            break;
//        }
//
//        break;
//
//    }
//
//
//    if($delete) {
//        if($season_id) {
//            echo "DELETING SEASON #$season_id\n";
//            if($DO_DELETE)
//                $DB->query("DELETE FROM iptv_series_seasons WHERE id = $season_id");
//        }
//
//        if($episode_id) {
//            if($DO_DELETE) {
//                echo "DELETING EPISODE #$episode_id\n";
//                $DB->query("DELETE FROM iptv_series_season_episodes WHERE id = $episode_id");
//            }
//
//        }
//
//        if($DO_DELETE)
//            $DB->query("DELETE FROM iptv_video_files WHERE id = $source_id");
//
//    }
//
//    if($episode_id == 153022 ) {
//        echo "FOUND !!!";
//        die;
//    }
//
//
//
//}

$r_episodes=$DB->query("SELECT id FROM iptv_series_season_episodes ");
while($episode_id = $r_episodes->fetch_assoc()['id']){
    $r=$DB->query("SELECT id FROM iptv_video_files  WHERE episode_id='$episode_id' ");
    if($r->num_rows < 1 ) {
        $DB->query("DELETE FROM iptv_series_season_episodes WHERE id = '$episode_id' ");
        echo "DELETING EPISODE $episode_id\n";
    }
}

$r_movies = $DB->query("SELECT id FROM iptv_ondemand WHERE type = 'movies'");
while($movie_id = $r_movies->fetch_assoc()['id']) {
    $r=$DB->query("SELECT id FROM iptv_video_files  WHERE movie_id='$movie_id' ");
    if($r->num_rows < 1 ) {
        $DB->query("DELETE FROM iptv_ondemand WHERE id = '$movie_id' ");
        echo "DELETING MOVIE #$movie_id\n";
    }
}


