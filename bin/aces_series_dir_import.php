<?php

ini_set("error_log", "/home/aces/logs/series_dir_import");

set_time_limit(0);
//error_reporting(0);



error_log("\n\n\nIMPORT START");

include "/home/aces/stream/config.php";
include "/home/aces/panel/class/TMDB.php";


function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    //print_r($files);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}




$ID = (int)$argv[1]; 
if(!$ID) die;


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){  die(); }


$r=$DB->query("SELECT * FROM iptv_proccess WHERE id = $ID ");
if(!$row=mysqli_fetch_array($r)) die;

register_shutdown_function(function(){
    global $DB, $ID;
    $DB->query("DELETE FROM iptv_proccess WHERE id = $ID ");
});

$pid = getmypid();
$DB->query("UPDATE iptv_proccess SET pid = '$pid' WHERE id = $ID ");

$args = unserialize($row['args']);
if($args == false )
    $args = json_decode($row['args'], true);

$do_not_download_images = (bool)$args['do_not_download_images'];
$scan_dir = urldecode($args['directory']);


$Bouquets = $args['bouquets'];

//$TMDB_API = null;
//$r2=$DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
//$TMDB_API=mysqli_fetch_array($r2)['value'];

$TMDB = new TMDB($do_not_download_images);
if($args['tmdb_lang'])
    $TMDB->LANG = $args['tmdb_lang'];

//$category_id = $args['category'];
if(!empty($args['category'])) $args['categories'][0] = $args['category'];
$categories =  $args['categories']; 
$category_id = $args['categories'][0];

$no_recursive = false;
if($args['no_recursive']) $no_recursive = true;

$one_series = '';
$series_new_episodes=[];

$add_if_info = 0;
$MAX_DOWNLOAD = 10;

function logfile($msg) {
    file_put_contents("/home/aces/logs/series_dir_import.log", "\n[ ".date('Y:m:d H:i:s')." ] $msg \n\n", FILE_APPEND );
}

logfile("\n\n\nSCAN START");

$r=$DB->query("SELECT * FROM iptv_servers WHERE id = '$SERVER_ID' ");
$server=mysqli_fetch_array($r);

$r=$DB->query("SELECT * FROM iptv_servers WHERE id = '1' ");
$main_servers = mysqli_fetch_array($r);

$Allow_Duplicated_episodes = 0;
$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.vod_duplicate_episode' ");
$Allow_Duplicated_episodes = $r->fetch_assoc()['value'] == 0 ? false : true;


switch($scan_dir) {

    case '/':
    case '/home':
    case '/home/':
    case '/home/aces':
    case '/home/aces/':
    case '/home/aces/vods':
    case '/home/aces/vods/':
        $DB->query("DELETE FROM iptv_proccess  WHERE id = $ID ");
        die;


}

if($no_recursive)  {

    $exp = explode("/",$scan_dir);
    $one_series = $exp[count($exp)-1];
    $scan_dir = str_replace("$one_series",'',$scan_dir);

}


$scans = 0 ;
$CONTENTS = glob("$scan_dir/*", GLOB_ONLYDIR);

function addSeries($series_name) {

    GLOBAL $TMDB, $DB, $category_id, $categories, $Bouquets;

    echo "ADDING SERIES";

    $sql_name = $DB->escape_string($series_name);

    $s_tmdb = $TMDB->search_imdb($series_name, 'series') ;
    $tmdb_id = (int)$s_tmdb[0]['id'];

    $DB->query("INSERT INTO iptv_ondemand (status,name,enable,category_id,type,tmdb_id,add_date)  
                    VALUES(1,'$sql_name',1,'$category_id','series','$tmdb_id',NOW()) ");
    $series_id = $DB->insert_id;
    $TMDB->update_ondemand($series_id);


    $s_tmdb = $TMDB->fetch_imdb_series($tmdb_id);

    foreach ($categories as $cat_id) {

        //ADDING TMDB CATEGORY
        if ($cat_id < 0) {

            $tmdb_category = null;
            if ($cat_id == -1) $tmdb_category = $s_tmdb['genres'][0]['name'];
            elseif ($cat_id == -2) $tmdb_category = $s_tmdb['genres'][1]['name'];
            elseif ($cat_id == -3) $tmdb_category = $s_tmdb['genres'][2]['name'];

            if (!is_null($tmdb_category)) {
                $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$tmdb_category')");
                if ($rcat_row = $rcat->fetch_assoc()) {
                    $cat_id = $rcat_row['id'];
                } else {
                    $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$tmdb_category') ");
                    $cat_id = $DB->insert_id;
                }

                //BECAUSE ONLY THE TMDB GENRE HAVE BEEN SET AS CATEGORY LET SET IT AS MAIN.
                if($category_id < 1 ) {
                    $DB->query("UPDATE iptv_ondemand SET category_id = '$cat_id' WHERE id = '$series_id'");
                }
            }

        }

        if ($cat_id) {
            $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES ('$series_id','$cat_id') ");
        }

    }


    //ALWAYS CREATE SEASON ONE.
    $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','1') ");
    $season_1_id = $DB->insert_id;
    $TMDB->update_season($season_1_id);

    if(is_array($Bouquets))
    foreach ($Bouquets as $b)
        $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) VALUES('$b','$series_id') ");

    return $series_id;

}

foreach ( $CONTENTS as $d ) {

    if ($no_recursive)
        $series = $one_series;

    else $series = $d;


    $series_id = 0;
    $tmdb_id = 0;

    $series = str_replace($scan_dir, '', $series);
    $series = str_replace("/", '', $series);
    $name = $series;

    //$t_name removing symbols from name to search on tmdb
    $t_name = str_replace("(", "", $series);
    $t_name = str_replace(")", "", $t_name);
    $t_name = str_replace("[", "", $t_name);
    $t_name = str_replace("]", "", $t_name);
    $t_name = str_replace(".", " ", $t_name);
    $t_name = str_replace("_", " ", $t_name);
    $t_name = str_replace("-", " ", $t_name);

    $remove = array('480p', '480i', '480', '720p', '720i', 'blueray', 'blueray', 'hdrip', 'hdtv', 'web', 'x264', '264', 'h264', 'aac', 'mp3', 'mp3', 'mpeg4', 'mpeg2');
    foreach ($remove as $r) $t_name = preg_replace("/\b($r)\b/i", '', $t_name);

    $sql_name = $DB->real_escape_string($t_name);
    $_LOG_SQL_NAME = $sql_name;

    $q_year = '';
    $query = '';

     $s_tmdb = $TMDB->search_imdb($t_name, 'series') ;

    //CHECK IF SERIES EXIST BY FOLDER NAME FIRST.
    $r=$DB->query("SELECT id,tmdb_id FROM iptv_ondemand WHERE lower(name) = lower('$sql_name') AND type = 'series' ");
    if($series_row=mysqli_fetch_assoc($r)) {
        //$s_tmdb['results'][0]['id'] = $series_row['tmdb_id'];
        $series_id = $series_row['id'];
    } else if($tmdb_id = $s_tmdb[0]['id']) {
        $sql_name = $DB->real_escape_string($s_tmdb[0]['name']);
        $r=$DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$sql_name') AND type = 'series'");
        $sql="SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$t_name') AND type = 'series'";
        if(!$series_id=mysqli_fetch_assoc($r)['id']) {
            $series_id = 0;
        }
    }


    //if($no_recursive) $dirs = glob("$scan_dir/$one_series/*");
    if($no_recursive) {
        $sell_output = shell_exec("find  $scan_dir/$one_series  -type f" );
        $dirs = preg_split('/\s+/', trim($sell_output));
         }
    else
        $dirs = getDirContents("$d");

    logfile("");

    foreach ( $dirs as $filename) {

        if (!is_dir($filename) && in_array(pathinfo($filename, PATHINFO_EXTENSION), array('mp4', 'mkv', 'avi', 'flv', 'wmv', 'm4v'))) {

            $file_episode = str_replace("$d/", '', $filename);
            $file_episode = str_replace("/", '', $file_episode);

            $file = pathinfo($filename);
            $ext = $file['extension'];

            //ADDING SERIES HERE IF THE FILE HAVENT BEEN ADDED.
            $r = $DB->query("SELECT id FROM iptv_video_files WHERE source_file = '".urlencode($filename)."' AND server_id =  {$server['id']}");
            if(!$r->fetch_assoc() && !$series_id)
                $series_id = addSeries($t_name);

            $e_info = null;

            //        preg_match_all('/(s)+(\d+)/ui', $file['filename'], $ms);
            //        preg_match_all('/(e)+(\d+)/ui', $file['filename'], $me);
            preg_match_all('/(s)+(\d+)/ui', $file['filename'], $ms);
            preg_match_all('/(e)+(\d+)/ui', $file['filename'], $me);

            if (!@$ms[2][0])
                preg_match_all('/(season)+(\d+)/ui', $file['filename'], $ms);

            if (!@$me[2][0])
                preg_match_all('/(episode)+(\d+)/ui', $file['filename'], $me);

            if (@$ms[2][0] && @$me[2][0]) {

                $e = @$me[2][0];
                $s = @$ms[2][0];

                //CHECK IF THE SEASON EXIST.
                $r = $DB->query("SELECT id FROM iptv_series_seasons WHERE series_id = '$series_id' AND number = '$s' ");
                if (!$row = mysqli_fetch_assoc($r)) {
                    $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','$s') ");
                    $season_id = $DB->insert_id;
                    $TMDB->update_season($season_id);
                } else $season_id = $row['id'];

                if($Allow_Duplicated_episodes) {
                    //ALLOWING DUPLICATE EPISODES.
                    $r = $DB->query("SELECT id FROM iptv_video_files WHERE source_file = '".urlencode($filename)."' AND server_id =  {$server['id']}");
                } else {
                    //CHECK IF EPISODE EXIST.
                    $r = $DB->query("SELECT id FROM iptv_series_season_episodes WHERE season_id = '$season_id' AND number = '$e' ");
                }

                if (!$r->fetch_assoc()) {

                    $series_new_episodes[] = $series_id;

                    $file['filename'] = $DB->real_escape_string($file['filename']);
                    $DB->query("INSERT INTO iptv_series_season_episodes (season_id,number,title,server_id) 
                            VALUES('$season_id','$e','{$file['filename']}','{$server['id']}') ");

                    $episode_id = $DB->insert_id;
                    $TMDB->update_episode($episode_id);

                    $DB->query("UPDATE iptv_ondemand SET add_date = NOW() WHERE id = '$series_id'");

                    $trans = 'symlink';
                    $container = $ext;
                    $filename = urlencode($filename);

                    $r = $DB->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,container,server_id,source_file) 
                                            VALUES($episode_id,'episode','$trans','$container','{$server['id']}','$filename')");
                    $file_id = $DB->insert_id;

                    $p['api_token'] = $server['api_token'];
                    $p['action'] = 'process_vod';
                    //$p['version'] = ACES_VER;
                    $p = array_merge($p, array('vod_id' => $file_id));
                    $j = urlencode(json_encode($p));

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://{$server['address']}:{$server['port']}/stream/api.php?data=$j");
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_exec($ch);

                }

            }
        }

    }

    $scans++;
    $progress = ($scans / count($CONTENTS)) * 100;
    $DB->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = $ID ");
    $r2=$DB->query("SELECT id FROM iptv_proccess WHERE id = $ID");
    if(!$r2->fetch_assoc()) die;

    if ($no_recursive) break;
}

foreach($series_new_episodes as $series_id) {

    $r_fav = $DB->query("SELECT profile_id,account_id FROM iptv_app_favorites WHERE vod_id = '$series_id'");
    while($row=$r_fav->fetch_assoc()) {
        $r=$DB->query("SELECT profile_id FROM iptv_app_new_episodes WHERE profile_id = '{$row['profile_id']}' and series_id = '$series_id' ");
        if($r->fetch_assoc())
            $DB->query("UPDATE iptv_app_new_episodes SET update_time = UNIX_TIMESTAMP() WHERE profile_id = '{$row['profile_id']}' and series_id = '$series_id'");
        else
            $DB->query("INSERT INTO iptv_app_new_episodes (series_id, profile_id, update_time) VALUES('$series_id', '{$row['profile_id']}', UNIX_TIMESTAMP() )");
    }

    $r_episodes = $DB->query("SELECT profile_id FROM iptv_app_last_episode WHERE series_id = '$series_id'");
    while($row=$r_episodes->fetch_assoc()) {

        $r=$DB->query("SELECT profile_id FROM iptv_app_new_episodes WHERE profile_id = '{$row['profile_id']}' and series_id = '$series_id' ");
        if($r->fetch_assoc())
            $DB->query("UPDATE iptv_app_new_episodes SET update_time = UNIX_TIMESTAMP() WHERE profile_id = '{$row['profile_id']}' AND series_id = '$series_id'");
        else
            $DB->query("INSERT INTO iptv_app_new_episodes (series_id, profile_id, update_time) VALUES('$series_id', '{$row['profile_id']}', UNIX_TIMESTAMP() )");

    }

}

