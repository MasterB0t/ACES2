<?php


use ACES2\IPTV\Video;

define('ACES_ROOT', '/home/aces/');
define('DOC_ROOT', '/home/aces/panel/');

include "/home/aces/panel/ACES2/DB.php";
include "/home/aces/panel/ACES2/IPTV/Video.php";
include "/home/aces/panel/ACES2/IPTV/Episode.php";
include "/home/aces/panel/ACES2/IPTV/Season.php";
include "/home/aces/panel/ACES2/Settings.php";
include "/home/aces/panel/ACES2/IPTV/Settings.php";

include "/home/aces/panel/ACES2/IPTV/TMDB/TMDB.php";
include "/home/aces/panel/ACES2/IPTV/TMDB/Movie.php";
include "/home/aces/panel/ACES2/IPTV/TMDB/Episode.php";
include "/home/aces/panel/ACES2/IPTV/TMDB/Season.php";
include "/home/aces/panel/ACES2/IPTV/TMDB/Series.php";

$download_logos = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::VOD_DONT_DOWNLOAD_LOGOS);

$db = new \ACES2\DB;
$r=$db->query("SELECT id,name FROM iptv_ondemand ");
while($vod_id = $r->fetch_assoc()['id']) {

    $Video = new Video($vod_id);
    echo "Updating $Video->name\n";

    if($Video->tmdb_id) {
        $Video->updateFromTMDB($download_logos);
    }

    if($Video->is_series) {
        $r_season = $db->query("SELECT id FROM iptv_series_seasons where series_id = '$Video->id' ");
        while($row=$r_season->fetch_assoc()) {

            $season = new \ACES2\IPTV\Season($row['id']);
            $season->updateFromTMDB(false);

            $r_episodes = $db->query("SELECT id FROM iptv_series_season_episodes 
                        WHERE season_id = '$season->id' ");

            while($episode_row=$r_episodes->fetch_assoc()) {
                $episode = new \ACES2\IPTV\Episode($episode_row['id']);
                $episode->updateFromTMDB($download_logos);
            }

        }


    }

}