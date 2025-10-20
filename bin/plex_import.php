<?php

include "/home/aces/stream/config.php";
include "/home/aces/panel/class/TMDB.php";

set_time_limit(0);
ini_set("error_log",'/home/aces/logs/plex_import.log');
ini_set('memory_limit', '-1');



$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die();

$pid = (int)$argv[1];

register_shutdown_function(function(){
    global $DB,$pid;
    $DB->query("DELETE FROM iptv_proccess WHERE id = $pid  ");
});

$r=$DB->query("SELECT args FROM iptv_proccess WHERE id = '$pid' ");
if(!$ARGS = json_decode($r->fetch_assoc()['args'],1))
    exit;



$TMDB = new TMDB();

$PlexServer = $ARGS['host'];
$PlexToken = $ARGS['plex_token'];
$ServerID = $ARGS['server_id'];

$r=$DB->query("SELECT * FROM iptv_servers WHERE id = '$ServerID' ");
if(!$server = $r->fetch_assoc())
    error_log("Could not get server #$ServerID");

$Downloading=[];
$BOUQUETS = $ARGS['bouquets'];
$PARALLEL_DOWNLOADS = $ARGS['parallel_download'];
$TRANSCODING = $ARGS['transcoding'];
$FromCategory=(int)$ARGS['import_from_category'];
$TMDB_LANG = $ARGS['tmdb_lang'];
$ImportMovies = $ARGS['import_movies'];
$ImportSeries = $ARGS['import_series'];
$CATEGORIES = $ARGS['categories'];
$Total = 0;
$Current = 0;

function wait() {

    global $DB, $Downloading, $PARALLEL_DOWNLOADS;

    while(true) {
        $sql  = implode(",", $Downloading);
        $rd=$DB->query("SELECT id FROM iptv_video_files WHERE is_processing > 0 AND id in ($sql)");
        if($rd->num_rows >= $PARALLEL_DOWNLOADS) {

            sleep(5);

        } else break;

    }
    //REMOVING FROM DOWNLOADING...
    foreach($Downloading as $d) {
        $r3=$DB->query("SELECT id FROM iptv_video_files WHERE is_processing = 0 AND id = $d ");
        if($r3->fetch_assoc()) {
            $s = array_search($d, $Downloading);
            unset($Downloading[$s]);
        }

    }
}

function get($path) {

    global $PlexToken, $PlexServer;

    $ch = curl_init($PlexServer.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'X-Plex-Token: '.$PlexToken,
    ));

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $resp = curl_exec($ch);
    return json_decode($resp, true);

}

function addVod($vod){

    global $DB, $BOUQUETS ,$TMDB, $PlexToken, $PlexServer, $TMDB_LANG, $CATEGORIES, $TRANSCODING, $FromCategoryName;

    $genres = explode(";",@$vod['Genre'][0]['tag']);

    if(count($genres) < 2)
    $genres = array(
        @$vod['Genre'][0]['tag'],
        @$vod['Genre'][1]['tag'],
        @$vod['Genre'][2]['tag']
    );

    $about = $DB->escape_string($vod['summary']);
    $cast = array();

    $director = @$DB->escape_string($vod['Director'][0]['tag']);

    $name = $DB->escape_string($vod['title']);

    $type = $vod['type'] == 'movie' ? 'movies' : 'series';

    $tmdb_id = getTMDB("/library/metadata/".$vod['ratingKey']);

    foreach($vod['Role'] as $c ) {
        $cast[] = $DB->escape_string($c['tag']);
    } $cast = implode(",", $cast);

    $status = $TRANSCODING == 'redirect' || $TRANSCODING == 'stream' ? 1 : 0;

    $DB->query("INSERT INTO iptv_ondemand (status, enable, name,  type, genre1, genre2, genre3, rating, 
                           age_rating, year, release_date, trailer_link, about, cast, director, add_date, tmdb_id , tmdb_lang)

        VALUES ($status, 1, '$name', '$type', '{$genres[0]}', '{$genres[1]}', '{$genres[2]}', '{$vod['audienceRating']}', '{$vod['contentRating']}',
                '{$vod['year']}', '{$vod['originallyAvailableAt']}','','$about', '$cast', '$director',  NOW(), '$tmdb_id', '$TMDB_LANG' ) ");

    $vod_id = $DB->insert_id;

    foreach($CATEGORIES as $cat_id) {

        //ADD TO PLEX CATEGORY.
        if($cat_id == -1 ) {

            $rcat = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$FromCategoryName') ");
            if (!$category_id = mysqli_fetch_assoc($rcat)['id']) {
                $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$FromCategoryName') ");
                $category_id = $DB->insert_id;
            }

        } else if($cat_id == -2 ) {

            foreach ($genres as $g){
                if(!empty($g)) {
                    $rcat = $DB->query("SELECT id FROM iptv_stream_categories
                                            WHERE lower(name) = lower('$g') ");
                    if (!$category_id = mysqli_fetch_assoc($rcat)['id']) {
                        $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$g') ");
                        $category_id = $DB->insert_id;
                    }
                }
            }

        } else
            $category_id = $cat_id;

        if(!@$first_cat) {
            $DB->query("UPDATE iptv_ondemand SET category_id = '$category_id' WHERE id = '$vod_id'");
            $first_cat = 1;
        }
        $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES('$vod_id','$category_id')  ");

    }

    $logo = "v$vod_id.jpg";
    $back_logo = "vb$vod_id.jpg";

    if(!file_put_contents('/home/aces/panel/logos/'.$logo,
        file_get_contents($PlexServer.$vod['thumb']."?X-Plex-Token=$PlexToken"))) $logo = '';

    if(!file_put_contents('/home/aces/panel/logos/'.$back_logo,
        file_get_contents($PlexServer.$vod['art']."?X-Plex-Token=$PlexToken"))) $back_logo = '';


    $DB->query("UPDATE iptv_ondemand SET logo = '$logo', back_logo = '$back_logo' WHERE id = '$vod_id'");

    foreach($BOUQUETS as $b )
        $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) VALUES('$b','$vod_id') ");

    return $vod_id;
}

function processFile($file_id) {

    global $server, $TRANSCODING, $DB;

    if( $TRANSCODING == 'redirect' || $TRANSCODING == 'stream' ) {
        $DB->query("UPDATE iptv_video_files SET is_processing = 0 WHERE id = $file_id ");
        return true;
    }

    $p['api_token'] = $server['api_token'];
    $p['action'] = 'process_vod';
    $p = array_merge($p, array('file_id' => $file_id ) );
    $j = urlencode(json_encode($p));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://{$server['address']}:{$server['port']}/stream/api2.php?action=process_vod&data=$j");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    json_decode(curl_exec($ch), 1);

    wait();

}

function getTMDB($path) {

    $tmdb_id = 0;
    $info = get($path);

    $rating = $info['MediaContainer']['Metadata'][0]['Guid'];
    foreach($rating as $r) {
        if( strpos($r['id'], 'tmdb://') !== false )
            $tmdb_id = (int)str_replace("tmdb://", '', $r['id']);
    }

    return $tmdb_id;

}

$Library = get("/library/sections/$FromCategory");
$FromCategoryName=$Library['MediaContainer']['title1'];

$Vods = get("/library/sections/$FromCategory/all");
$Total = count($Vods['MediaContainer']['Metadata']);
foreach($Vods['MediaContainer']['Metadata'] as $vod) {
    $Current++;

    $progress = (int)(($Current/$Total) * 100);

    $r=$DB->query("SELECT args FROM iptv_proccess WHERE id = '$pid' ");
    if($r->num_rows<1)
        exit;

    $DB->query("UPDATE iptv_proccess SET progress =  '$progress' WHERE id = '$pid'");

    $name = $DB->escape_string($vod['title']);
    $r=$DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('$name')");
    $vod_id = (int)$r->fetch_assoc()['id'];

    if($vod['type'] == 'movie' && !$vod_id && $ImportMovies  ) {

        $vod_id = addVod($vod);

        $cont = $vod['Media'][0]['container'];
        $link = $PlexServer."/library/parts/".$vod['Media'][0]['Part'][0]['id']."?X-Plex-Token=$PlexToken";

        $r=$DB->query("INSERT INTO iptv_video_files (movie_id,type,transcoding,container,server_id,source_file,is_processing) 
                                VALUES($vod_id,'movie','$TRANSCODING','$cont','$ServerID','$link', 1)");
        $file_id = $DB->insert_id;
        $Downloading[] = $file_id;

        processFile($file_id);


    } else if($vod['type'] == 'show' && $ImportSeries ) {

        if(!$vod_id)
            $vod_id = addVod($vod);

        $Seasons = get($vod['key']);

        foreach($Seasons['MediaContainer']['Metadata'] as $season) {

            $about = $DB->escape_string($season['summary']);
            $season_number = (int)$season['index'];

            $r2 = $DB->query("SELECT id FROM iptv_series_seasons WHERE series_id = $vod_id AND number = '{$season['index']}' ");
            if (!$season_id = (int)$r2->fetch_assoc()['id']) {

                $tmdb_id = getTMDB(str_replace('children', '', $season['key']));

                $logo = "vs-$season_id.jpg";
                if(!file_put_contents('/home/aces/panel/logos/'.$logo,
                    file_get_contents($PlexServer.$vod['art']."?X-Plex-Token=$PlexToken"))) $logo = '';

                $DB->query("INSERT INTO iptv_series_seasons (series_id, number, logo, air_date, overview, tmdb_id) 
                    VALUES('$vod_id', '$season_number', '$logo', '', '$about', '$tmdb_id')");

                $season_id = $DB->insert_id;

                $Episodes = get($season['key']);
                foreach($Episodes['MediaContainer']['Metadata'] as $episode) {

                    $episode_number = (int)$episode['index'];
                    $r3=$DB->query("SELECT * FROM iptv_series_season_episodes WHERE season_id = '$season_id' 
                                    AND number = '$episode_number'");
                    if(!$r3->fetch_assoc()) {

                        $title = $DB->escape_string($episode['title']);
                        $about = $DB->escape_string($episode['summary']);

                        $tmdb_id = getTMDB(str_replace('children', '', $episode['key']));
                        $status = $TRANSCODING == 'redirect' || $TRANSCODING == 'stream' ? 1 : 0;
                        $DB->query("INSERT INTO iptv_series_season_episodes (status, season_id, number, title, about, rate, 
                                 release_date, server_id, tmdb_id) 
                            VALUES($status, '$season_id', '$episode_number', '$title', '$about', '', '{$episode['originallyAvailableAt']}',
                                     '$ServerID', '$tmdb_id')");

                        $episode_id = $DB->insert_id;

                        $cont = $episode['Media'][0]['container'];
                        $link = $PlexServer."/library/parts/".$episode['Media'][0]['Part'][0]['id']."?X-Plex-Token=$PlexToken";
                        $r=$DB->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,container,server_id,source_file,is_processing) 
                        VALUES($episode_id,'episode','$TRANSCODING','$cont','$ServerID','$link', 1)");
                        $file_id = $DB->insert_id;
                        $Downloading[] = $file_id;

                        processFile($file_id);


                    }

                }

            }
        }


    }

}

$DB->query("DELETE FROM iptv_proccess WHERE id = $pid ");