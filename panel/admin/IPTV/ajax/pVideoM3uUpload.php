<?php

$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();


if (!$ADMIN->isLogged(true)) {
    $json['not_logged'] = 1;
    echo json_encode($json);
    exit;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    $json['error'] = "You don't have privileges to perform this action.";
    echo json_encode($json);
    exit;
}

$SERVER = new \ACES2\IPTV\SERVER((int)$_POST['server_id']);

//$ALLOW_DUPLICATE_EPISODES = $ADMIN->get_setting("iptv.vod_duplicate_episode");

if (!in_array($_POST['transcoding'], array('copy', 'h264:aac', 'symlink'))) {
    $json['error'] = "Not valid transcoding.";
    echo json_encode($json);
    exit;
}

$categories=[];
if(is_array($_POST['categories']))
    foreach($_POST['categories'] as $category) {
        $categories[]=(int)$category;
    }
if(count($categories) < 1)
    setAjaxError("Select at least one category.");
$category_id = $categories[0];


$bouquets = [];
if (is_array($_POST['bouquets'])) {
    foreach ($_POST['bouquets'] as $b) {
        $bouquets[] = (int)$b;
    }
}


$MAX_DOWNLOAD = (int)$_POST['max_download'] ?: 1;

//$force_import = 0;
//if (!empty($_POST['force_import'])) $force_import = 1;
$force_import = (bool)$_POST['force_import'];
$force_import = true;

if (empty($_FILES['m3u_file']['tmp_name'])) {
    $json['error'] = "Please select a playlist file to upload.";
    echo json_encode($json);
    exit;
}

if (!$handle = fopen($_FILES['m3u_file']['tmp_name'], "r")) {
    $json['error'] = 'Error uploading file.';
    echo json_encode($json);
    exit;
}


if ($_FILES['m3u_file']['size'] > 10000000) {
    $json['error'] = 'This file is too big.';
    echo json_encode($json);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
if (false === $logo_ext = array_search(
        $finfo->file($_FILES['m3u_file']['tmp_name']), array(
        'm3u8' => 'text/plain',
        'm3u' => 'text/plain',
        'text' => 'text/plain'
    ), true
    )) {
    $json['error'] = 'Please enter a valid M3U playlist.' . $finfo->file($_FILES['m3u_file']['tmp_name']);
    echo json_encode($json);
    exit;
}


$TMDB_API = null;
$r2 = $DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
$TMDB_API = mysqli_fetch_assoc($r2)['value'];

ignore_user_abort(true);
set_time_limit(0);
session_write_close();

$r = $DB->query("INSERT INTO iptv_proccess  (name,status,description,server_id) 
    VALUES('m3u_vod_upload',1,'Uploading {$_FILES['m3u_file']['full_path']}','$SERVER->id')");
$proccess_id = $DB->insert_id;

register_shutdown_function(function() {
    global $proccess_id,$DB ;
    $DB->query("DELETE FROM iptv_proccess WHERE id = '$proccess_id' ");
});

$json['complete'] = 1;
echo json_encode($json);
fastcgi_finish_request();
error_reporting(0);


$total_m3u_lines = count(file($_FILES['m3u_file']['tmp_name']));
$current_m3u_line = 0;


$TMDB = new TMDB();

if ($_POST['type'] == 'movies') {

    while (($line = fgets($handle)) !== false) {

        $current_m3u_line++;

        //REMOVING NEW LINE.
        $line = trim(preg_replace('/\s+/', ' ', $line));

        $found_in_db = 0;
        $id_tmdb = null;
        $i_tmdb = null;
        $name = '';
        $category_m3u = '';
        $url = '';

        if (strpos($line, 'EXTINF:')) {

            if (preg_match('/group-title="(.*?)"/', $line, $g) === 1) {
                $category_m3u = trim($g[1]); ///mb_convert_encoding(utf8_encode($g[1]), "UTF-8", "auto");
            }

            $ex = explode(',', $line);
            //$name = $DB->escString( trim($ex[ count($ex) - 1 ]) );
            $name = $DB->escString(trim($ex[1]));


            //if (!$name = mb_convert_encoding(utf8_encode($name), "UTF-8", "auto")) AcesLogD("FAIL COVERT $name");

            $url = trim(fgets($handle));
            $current_m3u_line++;
            $p = pathinfo($url);
            $ext = strtolower(trim($p['extension']));
            //TMDB Class will get the year on name.
            ///preg_match_all('!\d+!', $name, $year_matches);
            //preg_match_all('/(19|20)\d{2}/', $name, $year_matches);

            if ($tmdb_info = $TMDB->search_imdb($name, 'movies')) {
                $name = $tmdb_info[0]['original_title'];
                $id_tmdb = $tmdb_info[0]['id'];
            }

            if ($ext == 'mp4' || $ext == 'mkv' || $ext == 'wmv' || $ext == 'flv' || $ext == 'avi' || true) {

                $r = $DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$name') ");
                if (!$r->fetch_assoc() || $force_import) {

                    $DB->query("INSERT INTO iptv_ondemand (name,status,enable,category_id,type,tmdb_id,add_date) 
                        VALUES('$name',0,1,$category_id,'movies','{$id_tmdb}',NOW()) ");
                    $vod_id = $DB->insert_id;
                    $TMDB->update_ondemand($vod_id);

                    foreach ($bouquets as $b) $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) 
                        VALUES('$b','$vod_id') ");

                    $m_tmdb = $TMDB->fetch_imdb_movie($id_tmdb);
                    foreach ($categories as $cat_id) {

                        //ADDING TMDB CATEGORY
                        if ($cat_id < 1) {

                            $tmdb_category = null;
                            //USE THE CATEGORY FROM MEU8
                            if ($cat_id == 0 && $category_m3u) $tmdb_category = $category_m3u;
                            elseif ($cat_id == -1) $tmdb_category = $m_tmdb['genres'][0]['name'];
                            elseif ($cat_id == -2) $tmdb_category = $m_tmdb['genres'][1]['name'];
                            elseif ($cat_id == -3) $tmdb_category = $m_tmdb['genres'][2]['name'];

                            if (!is_null($tmdb_category)) {
                                $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$tmdb_category')");
                                if ($rcat_row = $rcat->fetch_assoc()) {
                                    $cat_id = $rcat_row['id'];
                                } else {
                                    $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$tmdb_category') ");
                                    $cat_id = $DB->insert_id;
                                }

                                //BECAUSE ONLY THE TMDB GENRE HAVE BEEN SET AS CATEGORY LET SET IT AS MAIN.
                                if ($category_id < 1)
                                    $DB->query("UPDATE iptv_ondemand SET category_id = '$cat_id' WHERE id = '$vod_id'");
                            }


                        }

                        if ($cat_id) {
                            $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES ('$vod_id','$cat_id') ");
                        }

                    }


                    $trans = $_POST['transcoding'];
                    if ($trans == 'symlink') $trans = 'redirect';
                    $container = $ext;

                    $r = $DB->query("INSERT INTO iptv_video_files (movie_id,type,transcoding,container,server_id,source_file) 
                        VALUES($vod_id,'movie','$trans','$container','$SERVER->id','$url')");
                    $file_id = $DB->insert_id;

                    $srv_resp = $SERVER->send_action($SERVER::ACTION_PROCESS_VOD, array('file_id' => $file_id));

                    if ($_POST['transcoding'] != 'symlink')
                        while (true) {

                            $r = $DB->query("SELECT id FROM iptv_ondemand WHERE status = 0 ");
                            if ($r->num_rows < $MAX_DOWNLOAD) break;

                            sleep(1);

                        }


                }
            }

        }

        $r_proccess = $DB->query("SELECT id FROM iptv_proccess WHERE id = '$proccess_id' ");
        if ($r_proccess->num_rows < 1 ) {
            die;
        } else {
            $progress = (int)(($current_m3u_line / $total_m3u_lines) * 100);
            $DB->query("UPDATE iptv_proccess SET progress  = '$progress' WHERE id = '$proccess_id'");
        }

    }
} else if ($_POST['type'] == 'series') {

    $series_id = 0;
    $series_added = [];
    while (($line = fgets($handle)) !== false) {

        $current_m3u_line++;

        //REMOVING NEW LINE.
        $line = trim(preg_replace('/\s+/', ' ', $line));

        $found_in_db = 0;
        $id_tmdb = '';
        $name = '';
        $category_m3u = '';
        $url = '';

        if (strpos($line, 'EXTINF:')) {

            if (preg_match('/group-title="(.*?)"/', $line, $g) === 1) {
                $category_m3u = trim($g[1]);
            }

            $ex = explode(',', $line);
            $name = $DB->escString(trim($ex[1]));

            $url = trim(fgets($handle));
            $current_m3u_line++;
            $p = pathinfo($url);
            $ext = strtolower(trim($p['extension']));


            preg_match_all('/(s)+(\d+)/ui', $name, $ms);
            preg_match_all('/(e)+(\d+)/ui', $name, $me);

            $season_number = $ms[2][0];
            $episode_number = $me[2][0];

            $name = explode($ms[0][0], $name)[0];
            $name = str_replace($ms[0][0], '', $name);
            $name = str_replace($me[0][0], '', $name);
            $name = trim($DB->escString($name));


            if ($ext == 'mp4' || $ext == 'mkv' || $ext == 'wmv' || $ext == 'flv' || $ext == 'avi' || true) {

                $r = $DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$name') and type = 'series' ");
                if (!$series_id = mysqli_fetch_assoc($r)['id']) {

                    if ($tmdb_info = $TMDB->search_imdb($name, 'series')) {
                        $name = $DB->escString($tmdb_info[0]['name']);
                        $id_tmdb = $tmdb_info[0]['id'];
                    }

                    $r = $DB->query("SELECT id,tmdb_id FROM iptv_ondemand WHERE lower(name) = lower('$name') and type = 'series' ");
                    $series_id = mysqli_fetch_assoc($r)['id'];

                }

                if (!$series_id ) {

                    if ($force_import)
                        $force_import = false;

                    $DB->query("INSERT INTO iptv_ondemand (name,status,enable,category_id,type,tmdb_id,add_date) 
                        VALUES('$name',1,1,$category_id,'series','{$id_tmdb}',NOW()) ");
                    $series_id = $DB->insert_id;
                    $series_added[strtolower($name_m3u)] = $series_id;
                    $TMDB->update_ondemand($series_id);


                    $m_tmdb = $TMDB->fetch_imdb_series($id_tmdb);
                    foreach ($categories as $cat_id) {

                        //ADDING TMDB CATEGORY
                        if ($cat_id < 1) {

                            $tmdb_category = null;
                            //USE THE CATEGORY FROM MEU8
                            if ($cat_id == 0 && $category_m3u) $tmdb_category = $category_m3u;
                            elseif ($cat_id == -1) $tmdb_category = $m_tmdb['genres'][0]['name'];
                            elseif ($cat_id == -2) $tmdb_category = $m_tmdb['genres'][1]['name'];
                            elseif ($cat_id == -3) $tmdb_category = $m_tmdb['genres'][2]['name'];

                            if (!is_null($tmdb_category)) {
                                $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$tmdb_category')");
                                if ($rcat_row = $rcat->fetch_assoc()) {
                                    $cat_id = $rcat_row['id'];
                                } else {
                                    $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$tmdb_category') ");
                                    $cat_id = $DB->insert_id;
                                }

                                //BECAUSE ONLY THE TMDB GENRE HAVE BEEN SET AS CATEGORY LET SET IT AS MAIN.
                                if ($category_id < 1)
                                    $DB->query("UPDATE iptv_ondemand SET category_id = '$cat_id' WHERE id = '$series_id'");
                            }


                        }

                        if ($cat_id) {
                            $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES ('$series_id','$cat_id') ");
                        }

                    }

                    $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','1') ");

                }

                //CHECK IF THE SEASON EXIST.
                $r = $DB->query("SELECT id FROM iptv_series_seasons WHERE series_id = '$series_id' AND number = '$season_number' ");
                if (!$season_id = mysqli_fetch_assoc($r)['id']) {
                    $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','$season_number') ");
                    $season_id = $DB->insert_id;
                    $TMDB->update_season($season_id);
                }

                if ($ALLOW_DUPLICATE_EPISODES) {
                    //ALLOWING DUPLICATE EPISODES.
                    $r = $DB->query("SELECT id FROM iptv_video_files WHERE source_file = '$url' ");
                } else {
                    //CHECK IF EPISODE EXIST.
                    $r = $DB->query("SELECT id FROM iptv_series_season_episodes WHERE season_id = '$season_id' AND number = '$episode_number' ");
                }

                if (!$r->fetch_assoc()) {

                    $e_info = null;
                    $e_tmdb = null;
                    $name = "$name S$season_number E$episode_number";
                    $DB->query("INSERT INTO iptv_series_season_episodes (season_id,number,title,server_id) 
                        VALUES('$season_id','$episode_number','$name','$SERVER->id') ");
                    $episode_id = $DB->insert_id;
                    $TMDB->update_episode($episode_id);


                    $trans = $_POST['transcoding'];
                    if ($trans == 'symlink') $trans = 'redirect';
                    $container = $ext;
                    //$filename = urlencode($filename);

                    $r = $DB->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,container,server_id,source_file) 
                        VALUES($episode_id,'episode','$trans','$container','$SERVER->id','$url')");
                    $file_id = $DB->insert_id;

                    $srv_resp = $SERVER->send_action($SERVER::ACTION_PROCESS_VOD, array('file_id' => $file_id));

                    if ($_POST['transcoding'] != 'symlink')
                        while (true) {

                            $r = $DB->query("SELECT id FROM iptv_series_season_episodes WHERE status = 0 ");
                            if ($r->num_rows < $MAX_DOWNLOAD) break;

                            sleep(1);

                        }

                }

            }

        }


        $r_proccess = $DB->query("SELECT id FROM iptv_proccess WHERE id = '$proccess_id' ");
        if ($r_proccess->num_rows < 1 ) {
            die;
        } else {
            $progress = (int)(($current_m3u_line / $total_m3u_lines) * 100);
            $DB->query("UPDATE iptv_proccess SET progress  = '$progress' WHERE id = '$proccess_id'");
        }

    }

}

$DB->query("DELETE FROM iptv_proccess WHERE id = '$proccess_id' ");