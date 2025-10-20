<?php 
//define('ACES_VER', '%VERSION%');

if(defined('DEBUG'))
    error_reporting(E_ALL);
else
    error_reporting(0);

set_time_limit(0);

//ini_set("error_log",'/home/aces/logs/php-aces.log');
ini_set("error_log",'/home/aces/logs/xc_video_importer.log');
ini_set('memory_limit', '-1');



//if(is_file("/home/aces/run/aces_xc_video_importer.pid")) {
//    if(!$pid = file_get_contents('/home/aces/run/aces_xc_video_importer.pid')) { error_log("Fail to read pid file."); die; }
//    if(posix_getpgid($pid)) die;
//}
//if(!file_put_contents('/home/aces/run/aces_xc_video_importer.pid',getmypid())) { error_log("Could not write pid file."); die; }
//
//
//file_put_contents("/home/aces/tmp/aces_xc_video_importer_progress",0);



function close(){

    global $ID,$DB,$SERVER_ID;

    //$DB->query("UPDATE iptv_xc_videos_imp SET progress = 0  WHERE id = '$ID' ");
    $DB->query("DELETE FROM iptv_proccess WHERE id = $ID");

    exit;

}

register_shutdown_function('close');

include "/home/aces/stream/config.php";
include_once "/home/aces/panel/class/TMDB.php";

function CURL($url) {

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

    $resp = curl_exec($curl);
    return json_decode($resp,1);

}


$ID = (int)$argv[1]; 
if(!$ID) die;

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }


$r=$DB->query("SELECT * FROM iptv_proccess WHERE id = '$ID'");
if(!$row=$r->fetch_assoc()) die;
$row = json_decode($row['args'],1);


$pid = getmypid();
$DB->query("UPDATE iptv_proccess set pid = '$pid' WHERE id = '$ID'");


$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.vod_duplicate_episode' ");
$Allow_Duplicated_episodes = $r->fetch_assoc()['value'] == 0 ? false : true;

//$ID = $row['id'];


$PORTAL=$row['host'];
$PORT=$row['port'];
$USERNAME=$row['username'];
$PASSWORD=$row['password'];
$MAX_DOWNLOAD = $row['parallel_download'];
if(empty($row['transcoding'])) $TRANSCODING = 'copy';
else $TRANSCODING = $row['transcoding'];
if($MAX_DOWNLOAD == 0 ) $MAX_DOWNLOAD = 1;
$BOUQUETS= $row['bouquets'];
$CATEGORIES = $row['categories'];
$DO_NOT_DOWNLOAD_IMAGES = (bool)$row['do_not_download_images'];

$TMDB = new TMDB($DO_NOT_DOWNLOAD_IMAGES);
if($row['tmdb_lang']) $TMDB->LANG = $row['tmdb_lang'];

$tmdb_get_info = true;
if($row['get_info_from'] != 'tmdb')
    $tmdb_get_info = false;

$only_from_cat = '';
if($row['import_from_category'])
    $only_from_cat = '&category_id='.$row['import_from_category'];


$force_import = 0;
if(!empty($row['force_import'])) $force_import = 1;

$r=$DB->query("SELECT id,address,port,sport,api_token FROM iptv_servers WHERE id = '{$row['server_id']}' ");
$server=$r->fetch_assoc();

$series_new_episodes = [];

$LAST_MOVIE_ADDED = $row['last_import_movie'];
$LAST_SERIES_ADDED = $row['last_import_series'];
$LAST_MOVIE_ADDED = 0;
$LAST_SERIES_ADDED  = 0;

//$url = str_replace("/", "", $PORTAL);
//
//if(strpos($url, "https:") !== false  ) {
//
//    $url = str_replace("https:", "", $url);
//    $url = "https://$url"; }
//else if(strpos($url, "http:") !== false  ) {
//
//    $url = str_replace("http:", "", $url);
//    $url = "http://$url";
//} else {
//
//    $url = "http://".$url;
//}
//
//if($PORT)
//    $url = $url.":$PORT";

$url = $PORTAL;


$movies = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_vod_streams$only_from_cat"),true);
$vod_cats = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_vod_categories"),true);
$series_cats = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_series_categories"),true);
$series = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_series$only_from_cat"),true);


if($row['import_movies']) {

    for($i=$LAST_MOVIE_ADDED;isset($movies[$i]);$i++){

        $year = 2020;

        unset($genre,$about);

        $m = $movies[$i];

        $o_name  = mysqli_real_escape_string($DB,trim($m['name']));
        $m['name'] = $o_name;

        $m['name'] = str_replace("(","",$m['name']);
        $m['name'] = str_replace(")","",$m['name']);
        $m['name'] = str_replace("."," ",$m['name']);
        $m['name'] = str_replace("_"," ",$m['name']);
        $m['name'] = str_replace("-"," ",$m['name']);
        $m['name'] = str_replace("[","",$m['name']);
        $m['name'] = str_replace("]","",$m['name']);



        //CHECK IF MOVIES IS ALREADY ADDED TO DB.
        $r=$DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('{$m['name']}') ");
        if(!mysqli_fetch_array($r) ) {
            //CHECK FOR MOVIE IF IS ON SERVER
            //if ( 1 < round( exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 http://$PORTAL:$PORT/movie/$USERNAME/$PASSWORD/{$m['stream_id']}.{$m['container_extension']}")) ) {

                $tmdb_id='';$found=0;$genres='';$year='';$release_date='';$about='';$qname='';$trailer='';$runtime='';$cast='';$director='';

                if($info = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_vod_info&vod_id={$m['stream_id']}"),true)) {
                    $tmdb_id = $info['info']['imdb_id'];
                    $year=explode('-',$info['info']['releasedate'])[0];
                }

                if(!$tmdb_id) {
                    $results_tmdb = $TMDB->search_imdb($m['name'],'movies',$year);
                    $tmdb_id = $results_tmdb[0]['id'];
                }

                if($tmdb_id) {
                    $r_dup = $DB->query("SELECT id FROM iptv_ondemand WHERE tmdb_id = '$tmdb_id'");
                    if ($r_dup->fetch_assoc()) $found = 1;
                } else {

                    $r_dup = $DB->query("SELECT id FROM iptv_video_files WHERE source_file = '$url/movie/$USERNAME/$PASSWORD/{$m['stream_id']}.{$m['container_extension']}'");
                    if($r_dup->fetch_assoc())$found = 1;
                }


                if( $found == 0 || $force_import ) {

                    if($r = $DB->query("INSERT INTO iptv_ondemand (name,enable,type,year,tmdb_id,add_date)  VALUES('$o_name',1,'movies','{$year}','$tmdb_id',NOW()) ") ) {

                        $vod_id = $DB->insert_id;
                        foreach($BOUQUETS as $b ) $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) VALUES('$b','$vod_id') ");


                        $category_id = null;
                        $first_cat = 0;
                        foreach($CATEGORIES as $cat_id) {

                            //ADD TO XC CATEGORY.
                            if($cat_id == -1 ) {
                                //FINDING THE CATEGORY ID FROM XC
                                foreach($vod_cats as $c ) { if($c['category_id'] == $info['movie_data']['category_id'] ) { $cat_name = $c['category_name']; break; }  };
                                $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$cat_name') " );
                                if(!$category_id=mysqli_fetch_assoc($rcat)['id']) {
                                    $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$cat_name') ");
                                    $category_id = $DB->insert_id;
                                }

                            } else $category_id = $cat_id;

                            if(!$first_cat) {
                                $DB->query("UPDATE iptv_ondemand SET category_id = '$category_id' WHERE id = '$vod_id'");
                                $first_cat = 1;
                            }
                                $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES('$vod_id','$category_id')  ");

                        }


                        if(!$tmdb_id && $tmdb_get_info) {
                            $results_tmdb = $TMDB->search_imdb($m['name'],'movies',$year);
                            if($results_tmdb[0]['id']) {
                                $tmdb_id = $results_tmdb[0]['id'];
                                $DB->query("UPDATE iptv_ondemand SET tmdb_id = '$tmdb_id' WHERE id = '{$vod_id}'");
                            }
                        }

                        if($tmdb_id && $tmdb_get_info)
                            $TMDB->update_ondemand($vod_id);
                        else {
                            //INFO FROM PROVIDER
                            unset($_genres,$_about,$_trailer,$_cover,$_backlogo);
                            $_genres = explode(",",$info['info']['genre']);
                            $_about = $DB->escape_string($info['info']['plot']);
                            if($info['info']['youtube_trailer'])
                                $_trailer = "https://www.youtube.com/watch?v=".$info['info']['youtube_trailer'];

                            if($info['info']['movie_image']) {

                                if($DO_NOT_DOWNLOAD_IMAGES) {
                                    $_cover = $info['info']['movie_image'];
                                } else {
                                    $_cover = "v$vod_id.jpg";
                                    file_put_contents("/home/aces/panel/logos/".$_cover,file_get_contents($info['info']['movie_image']));
                                }
                            }

                            if($info['info']['backdrop_path'][0]) {

                                if($DO_NOT_DOWNLOAD_IMAGES) {
                                    $_backlogo = $info['info']['backdrop_path'][0];

                                } else {
                                    $_backlogo = "vb$vod_id.jpg";
                                    file_put_contents("/home/aces/panel/logos/".$_backlogo,file_get_contents($info['info']['backdrop_path'][0]));
                                }

                            }

                            $info['info']['cast'] = $DB->escape_string($info['info']['cast']);
                            $info['info']['director'] = $DB->escape_string($info['info']['director']);


                            $DB->query("UPDATE iptv_ondemand SET  genre1 = '".trim($_genres[0])."', genre2 = '".trim($_genres[1])."', genre3 = '".trim($_genres[2])."',
                                    about = '$_about', director = '{$info['info']['director']}', cast = '{$info['info']['cast']}', release_date = '{$info['info']['releasedate']}', 
                                    trailer_link = '$_trailer', rating = '{$info['info']['rating']}', runtime_seconds = '{$info['info']['duration_secs']}', logo = '$_cover', back_logo = '$_backlogo'
                                    WHERE id =  $vod_id
                             ");

                        }


                        $container = $m['container_extension'];


                        $r=$DB->query("INSERT INTO iptv_video_files (movie_id,type,transcoding,container,server_id,source_file) 
                                                VALUES($vod_id,'movie','$TRANSCODING','$container','{$server['id']}','$url/movie/$USERNAME/$PASSWORD/{$m['stream_id']}.{$m['container_extension']}')");
                        $file_id = $DB->insert_id;

                        if(is_file("/home/aces/logs/vods/{$file_id}-prog.txt")) unlink("/home/aces/logs/vods/{$file_id}-prog.txt");

                        if(is_file("/home/aces/logs/vods/{$file_id}.log")) unlink("/home/aces/logs/vods/{$file_id}.log");

                        if(is_file("/home/aces/run/aces_process_video_{$file_id}.pid")) {
                                $pid = file_get_contents("/home/aces/run/aces_process_video_{$file_id}.pid");
                                exec( "kill -9 $pid " );
                        }

                        //$DB->query("UPDATE iptv_xc_videos_imp SET last_import_movie = {$m['num']} WHERE id = $ID ");

                        $p['api_token'] = $server['api_token'];
                        $p['action'] = 'process_vod';
                        //$p['version'] = ACES_VER;
                        $p = array_merge($p, array('vod_id' => $file_id ) );
                        $j = urlencode(json_encode($p));

                        $ch = curl_init();

                        $server_url = $server['sport'] ?
                            "https://{$server['address']}:{$server['sport']}/stream/api.php?data=$j" :
                            "http://{$server['address']}:{$server['port']}/stream/api.php?data=$j";


                        curl_setopt($ch, CURLOPT_URL, $server_url);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

                        $return = json_decode(curl_exec($ch), 1);

                    }

                }


            //} // else could not fetch info.

            if($row['import_series']) $d = 2;
            else $d = 1;

            $total_m = count($movies) - $LAST_MOVIE_ADDED;
            $progress = round(  (( ($i-$LAST_MOVIE_ADDED)  / $total_m ) * 100 ) / $d ) ;
            file_put_contents("/home/aces/tmp/aces_xc_video_importer_progress",$progress);
            $rpp=$DB->query("SELECT id FROM iptv_proccess WHERE id = '$ID'");
            if(!$rpp->fetch_assoc()) die;
            $DB->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = '$ID'");

            if($TRANSCODING != 'redirect' && $TRANSCODING != 'symlink')
            while(true) {

                //$api = json_decode(file_get_contents("http://$PORTAL:$PORT/player_api.php?username=$USERNAME&password=$PASSWORD"),true);
                $api = CURL("$url/player_api.php?username=$USERNAME&password=$PASSWORD");
                if($api['user_info']['active_cons'] < $MAX_DOWNLOAD ) break;

                sleep(10);
                $rpp=$DB->query("SELECT id FROM iptv_proccess WHERE id = '$ID'");
                if(!$rpp->fetch_assoc()) die;

            }

        }


    }

}


if($row['import_series']) {

    for($i=$LAST_SERIES_ADDED;isset($series[$i]); $i++) {


        $s = $series[$i];

        $found = 0;
        $name = mysqli_real_escape_string($DB,$s['name']);

        //IF SERIES DO NOT EXIST.
        $r=$DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$name')");
        if( !($row_series=$r->fetch_assoc()) || $force_import ) {


            $image = '';$back_image='';$runtime = 0;$trailer='';
            $release_date  = $s['releaseDate'];
            $year=explode('-',$s['releaseDate'])[0];

            $genres = array();
            if(strpos($s['genre'],",") !== false ) $g = explode(',',$s['genre']);
            else $g = explode('/',$s['genre']);
            foreach($g as $y => $v ) $genres[] = trim($v);

            $tmdb_id = $TMDB->search_imdb($name,'series',$year)[0]['id'];

            $r_found_by_imdb = $DB->query("SELECT id FROM iptv_ondemand WHERE tmdb_id = '$tmdb_id' ");
            if($series_id=$r_found_by_imdb->fetch_assoc()['id'] ) {
                $found = 1;
            }

            if($found == 0 || $force_import ) {

                if (!$r = $DB->query("INSERT INTO iptv_ondemand (name,enable,status,type,release_date,year,tmdb_id,add_date) 
                        VALUES('$name',1,1,'series','$release_date','$year','$tmdb_id',NOW()) ")) {
                    echo mysqli_error($DB);
                    die;
                }

                $series_id = $DB->insert_id;
                if($tmdb_get_info)
                    $TMDB->update_ondemand($series_id);
                else {

                    //INFO FROM PROVIDER
                    unset($_genres,$_about,$_trailer,$_cover,$_backlogo);
                    $_genres = explode(",",$s['genre']);
                    $_about = $DB->escape_string($s['plot']);
                    if($s['youtube_trailer'])
                        $_trailer = "https://www.youtube.com/watch?v=".$s['youtube_trailer'];

                    if($s['cover']) {

                        if($DO_NOT_DOWNLOAD_IMAGES) {
                            $_cover = $s['cover'];
                        } else {
                            $_cover = "v$series_id.jpg";
                            file_put_contents("/home/aces/panel/logos/".$_cover,file_get_contents($s['cover']));
                        }

                    }

                    if($s['backdrop_path'][0]) {
                        if($DO_NOT_DOWNLOAD_IMAGES) {
                            $_backlogo = $s['backdrop_path'][0];
                        } else {
                            $_backlogo = "vb$series_id.jpg";
                            file_put_contents("/home/aces/panel/logos/" . $_backlogo, file_get_contents($s['backdrop_path'][0]));
                        }
                    }

                    $s['cast'] = $DB->escape_string($s['cast']);
                    $s['director'] = $DB->escape_string($s['director']);

                    $DB->query("UPDATE iptv_ondemand SET  genre1 = '".trim($_genres[0])."', genre2 = '".trim($_genres[1])."', genre3 = '".trim($_genres[2])."',
                                    about = '$_about', director = '{$s['director']}', cast = '{$s['cast']}', release_date = '{$s['releasedate']}', 
                                    trailer_link = '$_trailer', rating = '{$s['rating']}',  logo = '$_cover', back_logo = '$_backlogo'
                                    WHERE id =  '$series_id' ");

                }


                $category_id = null;
                $first_cat = 0;
                foreach ($CATEGORIES as $cat_id) {

                    //ADD TO XC CATEGORY.
                    if ($cat_id == -1) {
                        //FINDING THE CATEGORY ID FROM XC
                        foreach ($series_cats as $c) {
                            if ($c['category_id'] == $s['category_id']) {
                                $cat_name = $c['category_name'];
                                break;
                            }
                        };
                        $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$cat_name') ");
                        if (!$category_id = mysqli_fetch_assoc($rcat)['id']) {
                            $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$cat_name') ");
                            $category_id = $DB->insert_id;
                        }

                    } else $category_id = $cat_id;

                    if (!$first_cat) {
                        $DB->query("UPDATE iptv_ondemand SET category_id = '$category_id' WHERE id = '$series_id'");
                        $first_cat = 1;
                    }

                    $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES('$series_id','$category_id')  ");

                }

                //ADDING FIRST SEASON...
                $r = $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','1') ");
                $season_1 = $DB->insert_id;
                $TMDB->update_season($season_1);


                foreach ($BOUQUETS as $b)
                    $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) VALUES('$b','$series_id') ");

            }

        } else { 
            $series_id = $row_series['id'];
        }

        //$seasons = json_decode(file_get_contents("http://$PORTAL:$PORT/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_series_info&series_id={$s['series_id']}"),true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"$url/player_api.php?username=$USERNAME&password=$PASSWORD&action=get_series_info&series_id={$s['series_id']}");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        //echo curl_exec($ch); die;
        if(!$seasons = json_decode(curl_exec($ch),1)) {
            $seasons = []; //IGNORE

        }


        foreach($seasons['seasons'] as $season_info ) { 

            //CHECK IF SEASON ALREADY ADDED.

            $r=$DB->query("SELECT id FROM iptv_series_seasons WHERE series_id = '$series_id' AND number = '{$season_info['season_number']}' ");
            if(!mysqli_fetch_array($r)) {
                $DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','{$season_info['season_number']}') ");
                $new_season_id = $DB->insert_id;
                $TMDB->update_season($new_season_id);
            }

        }

        //ADDING EPISODES
        $new_episodes = 0;
        foreach($seasons['episodes'] as $e ) { 

            foreach($e as $episode_info) { 
                
                if($episode_info['season'] == 0 ) $episode_info['season'] = 1;

                $rseason=$DB->query("SELECT id FROM iptv_series_seasons WHERE number = '{$episode_info['season']}' AND series_id = '$series_id' ");
                if(!$season_id=mysqli_fetch_array($rseason)['id']) { 
                    //BECAUSE OF THE BUG OF XCUI THAT EPISODES CAN BE IN NO SEASON LET FORCE TO SEASON ONE. 
                    $r=$DB->query("INSERT INTO iptv_series_seasons (series_id,number) VALUES('$series_id','{$episode_info['season']}') ");
                    $season_id = $DB->insert_id;
                }

                $episode_info['episode_num'] = (int)$episode_info['episode_num'];

                if($Allow_Duplicated_episodes) {
                    //ALLOWING DUPLICATE EPISODES.
                    $r_episode=$DB->query("SELECT id FROM iptv_video_files 
                        WHERE source_file = '$url/series/$USERNAME/$PASSWORD/{$episode_info['id']}.{$episode_info['container_extension']}' ");

                } else {
                    //CHECK IF EPISODE EXIST.
                    $r_episode=$DB->query("SELECT id FROM iptv_series_season_episodes 
                        WHERE season_id = '$season_id' AND number = '{$episode_info['episode_num']}' ");

                }

                if(!$r_episode->fetch_assoc()) {
                    $new_episodes++;

                    $series_new_episodes[] = $series_id;

                    $title= mysqli_escape_string($DB, $episode_info['title']);

                    $DB->query("INSERT INTO iptv_series_season_episodes (title,number,season_id,server_id) 
                                            VALUES('$title','{$episode_info['episode_num']}','$season_id',1) ");
                    $e_id = $DB->insert_id;
                    $DB->query("UPDATE iptv_ondemand SET add_date = NOW() WHERE id = $series_id");


                    if($tmdb_get_info) {
                        $TMDB->update_episode($e_id);
                    } else {
                        //FROM PROVIDER
                        unset($_about,$_cover);

                        $_about = $DB->escape_string($episode_info['episode_num']['info']['plot']);

                        if($episode_info['episode_num']['info']['movie_image']) {
                            if($DO_NOT_DOWNLOAD_IMAGES) {
                                $_cover = $episode_info['episode_num']['info']['movie_image'];
                            } else {
                                $_cover = "vep{$episode_info['episode_num']}.jpg";
                                file_put_contents("/home/aces/panel/logos/".$_cover,file_get_contents($episode_info['episode_num']['info']['movie_image']));
                            }

                        }

                        $DB->query("UPDATE iptv_series_season_episodes SET about = '$about', 
                                       runtime_seconds = '{$episode_info['episode_num']['info']['duration_secs']}', logo = '$_cover'  
                                   WHERE id = '$e_id' ");

                    }
                    
                    $container = (!$m['container_extension']) ? 'mp4' : $m['container_extension'] ;

                    $DB->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,server_id,source_file) 
                                        VALUES($e_id,'episode','$TRANSCODING','{$server['id']}','$url/series/$USERNAME/$PASSWORD/{$episode_info['id']}.{$episode_info['container_extension']}')");
                    $file_id = $DB->insert_id;

                    #exec(" nohup php /home/aces/bin/aces_process_video.php $file_id- > /dev/null & " );

                    $p['api_token'] = $server['api_token'];
                    $p['action'] = 'process_vod';
                    //$p['version'] = ACES_VER;
                    $p = array_merge($p, array('vod_id' => $file_id ) );
                    $j = urlencode(json_encode($p));

                    $ch = curl_init();

                    $server_url = $server['sport'] ?
                        "https://{$server['address']}:{$server['sport']}/stream/api.php?data=$j" :
                        "http://{$server['address']}:{$server['port']}/stream/api.php?data=$j";

                    curl_setopt($ch, CURLOPT_URL, $server_url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

                    $return = json_decode(curl_exec($ch), 1);


                    if($TRANSCODING != 'redirect' && $TRANSCODING != 'symlink')
                        while(true) {

                            $api = json_decode(file_get_contents("$url/player_api.php?username=$USERNAME&password=$PASSWORD"),true);
                            if($api['user_info']['active_cons'] < $MAX_DOWNLOAD ) break;

                            sleep(10);
                            $rpp=$DB->query("SELECT id FROM iptv_proccess WHERE id = $ID");
                            if(!$rpp->fetch_assoc()) die;


                        }

                }

            }


        }

        if($row['import_movies']) { $d = 2; $m = 50; } 
        else { $d = 1; $m = 0 ; } 
        
        $total_s = count($series) - $LAST_SERIES_ADDED;
        $progress = round(  (( ($i-$LAST_SERIES_ADDED)  / $total_s ) * 100 ) / $d ) +  $m  ;
        
        file_put_contents("/home/aces/tmp/aces_xc_video_importer_progress",$progress);

        $rpp=$DB->query("SELECT id FROM iptv_proccess WHERE id = '$ID' ");
        if(!$rpp->fetch_assoc()) die;
        $DB->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = '$ID'");

        //$DB->query("UPDATE iptv_xc_videos_imp SET last_import_series = '{$series[$i]}' WHERE id = $ID ");



        if($new_episodes) {
            //NEW EPISODES FOR ACES APP...
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



    }
}


$DB->query("DELETE FROM iptv_proccess WHERE id = '$ID'");
unlink('/home/aces/run/aces_xc_video_importer.pid');