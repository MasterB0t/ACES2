<?php

//if(defined('DEBUG'))
//    error_reporting(E_ALL);
//else
//    error_reporting(0);


set_time_limit(0);
ini_set('memory_limit', '-1');

$last_movie_import = 0;
$last_series_import = 0;

if(!$AccountID = (int)$argv[1])
    exit;

$LogFile = "/home/aces/logs/update_provider_content_{$AccountID}.log";
$f = @fopen($LogFile, "r+");
if ($f !== false) {
    ftruncate($f, 0);
    fclose($f);
}

ini_set("error_log", $LogFile);

#include_once '/home/aces/panel/ACES2/init.php';
require_once '/home/aces/stream/config.php';
require_once '/home/aces/panel/ACES2/DB.php';
require_once '/home/aces/panel/ACES2/Process.php';
require_once '/home/aces/panel/ACES2/IPTV/Process.php';
require_once '/home/aces/panel/ACES2/IPTV/XCAPI/XCAccount.php';
require_once '/home/aces/panel/ACES2/IPTV/XCAPI/Stream.php';

$DB = new \ACES2\DB;

$r=$DB->query("SELECT id FROM iptv_proccess WHERE name = 'provider_update_content' AND sid = '$AccountID'");
if($r->num_rows>0)
    exit;

$Process =  \ACES2\IPTV\Process::add(\ACES2\IPTV\Process::TYPE_UPDATE_PROVIDER_CONTENT, $SERVER_ID, $AccountID);

function progress() {
    global $DB, $process_id, $next_update, $total_count, $current;
    clearstatcache();
    if( $next_update > time() )
        return;
    $progress = (int)(($current / $total_count) * 100);
    $rp = $DB->query("SELECT id FROM iptv_proccess WHERE id = $process_id");
    if ($rp->num_rows < 1)
        exit;
    $DB->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = $process_id");
    $next_update = time() + 5;
}

try {

    $account = new \ACES2\IPTV\XCAPI\XCAccount($AccountID);
    $Process->setDescription("Getting $account->host content.");

    $DB->query("INSERT INTO iptv_proccess (name,description,sid) 
                    VALUES('provider_update_content', 'Updating Content of $account->host', '$account->id') ");
    $process_id = $DB->insert_id;

    register_shutdown_function(function() {
        global $process_id,$DB, $Process;
        $Process->remove();
        $DB->query("DELETE FROM iptv_proccess WHERE id = $process_id");
    });

    $Account= json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}"));
    $movies = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_vod_streams"),true);
    $series = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&&action=get_series"),true);
    $vod_cats = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_vod_categories"),true);
    $series_cats = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_series_categories"),true);
    $streams_cats = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_live_categories"),true);
    $streams = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_live_streams"),true);

    $total_count = count($movies) + count($series) + count($streams);
    $current = 0;


    $Process->setDescription("Getting streams from $account->host ");
    foreach($streams as $stream) {
        $XCStream = new \ACES2\IPTV\XCAPI\Stream($stream);
        $r=$DB->query("SELECT stream_id FROM iptv_provider_content_streams WHERE stream_id = '$XCStream->stream_id'");
        if($r->num_rows < 1 ) {

            $stream_name = $DB->escString($XCStream->name);
            $stream_epg = $DB->escString($XCStream->epg_channel_id);
            $stream_cat_name = $streams_cats[$XCStream->category_id];
            $stream_icon = $DB->escString($XCStream->stream_icon);

            $DB->query("INSERT INTO iptv_provider_content_streams 
                (num, name, stream_type, stream_id, stream_icon, category_id, category_name, epg_channel_id, custom_sid, provider_id) 
            VALUES('$XCStream->num', '$stream_name', '$XCStream->stream_type', '$XCStream->stream_id', '$stream_icon', '$XCStream->category_id', 
                   '$stream_cat_name', '$stream_epg', '$XCStream->custom_sid', '$AccountID')
            ");
        }

        $current++;
        $Process->calculateProgress($current , $total_count);
        if(!$Process->isAlive())
            exit;
    }


    $next_update = time() + 5;


    $Process->setDescription("Getting movies from $account->host ");
    foreach($movies as $movie) {
        $current++;

        $tmdb_id = 0;
        $name = $DB->escString($movie['name']);

        if($info = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_vod_info&vod_id={$movie['stream_id']}"),true)) {
            @$tmdb_id = (int)$info['info']['imdb_id'];
        }

        $category_name = '';
        foreach($vod_cats as $cat) {
            if($cat['category_id'] == $movie['category_id']) {
                $category_name = $DB->escString($cat['category_name']);
                break;
            }
        }

        $stream_url = "{$account->url}/movie/{$account->username}/{$account->password}/{$movie['stream_id']}.{$movie['container_extension']}";

        $r_file = $DB->query("SELECT movie_id FROM iptv_video_files WHERE source_file = '$stream_url' LIMIT 1 ");
        $f_id = (int)$r_file->fetch_assoc()['movie_id'];

        $r=$DB->query("SELECT id FROM iptv_provider_content_vod WHERE provider_movie_id = '{$movie['stream_id']}'
                                       AND provider_id = '$account->id' ");

        if($r->num_rows < 1) {
            $DB->query("INSERT INTO iptv_provider_content_vod (provider_id, provider_movie_id, name, logo, category_id,
                                       category_name, tmdb_id, added_time, added_id )

                        VALUES ('$account->id', '{$movie['stream_id']}', '$name', '{$movie['stream_icon']}' ,
                                '{$movie['category_id']}','$category_name', '{$tmdb_id}', '{$movie['added']}', '$f_id' )");

        }

        else
            $DB->query("UPDATE iptv_provider_content_vod SET 
                                     added_id = '$f_id' WHERE provider_movie_id = '{$movie['stream_id']}' ");

        $Process->calculateProgress($current , $total_count);
        if(!$Process->isAlive())
            exit;
    }


    $Process->setDescription("Getting series from $account->host");
    foreach($series as $vod) {

        $current++;

        $name = $DB->escString($vod['name']);
        $series_id = (int)$vod['series_id'];
        $tmdb_id = 0;

        $r=$DB->query("SELECT id FROM iptv_provider_content_vod WHERE provider_series_id = '{$vod['series_id']}'
                       AND provider_id = '$account->id' ");

        $category_name = '';
        foreach($series_cats as $cat) {
            if($cat['category_id'] == $vod['category_id']) {
                $category_name = $DB->escString($cat['category_name']);
                break;
            }
        }

        if( !$content_vod_id = $r->fetch_assoc()['id'] ) {
            $DB->query("INSERT INTO iptv_provider_content_vod (provider_id, provider_series_id, name, logo, category_id, 
                                       category_name, tmdb_id, added_time, is_series )
                        VALUES ('$account->id', '{$vod['series_id']}', '$name', '{$vod['cover']}' , '{$vod['category_id']}',
                                '$category_name','{$tmdb_id}', '{$vod['last_modified']}', 1 )");
            $content_vod_id = $DB->insert_id;
        }

        $Process->calculateProgress($current , $total_count);
        if(!$Process->isAlive())
            exit;

        if($info = json_decode(file_get_contents("$account->url/player_api.php?username={$account->username}&password={$account->password}&action=get_series_info&series_id={$vod['series_id']}"),true)) {
            foreach($info['episodes'] as $episodes ) {
                foreach($episodes as $episode) {
                    $episode_id = (int)$episode['id'];
                    $episode_num = (int)$episode['episode_num'];
                    $episode_name = $DB->escString($episode['title']);
                    $season_number = (int)$episode['season'];
                    $episode_image = $episode['info']['movie_image'];

                    $stream_url = "{$account->url}/series/{$account->username}/{$account->password}/{$episode_id}.{$episode['container_extension']}";

                    $r_file = $DB->query("SELECT episode_id FROM iptv_video_files WHERE source_file = '$stream_url' LIMIT 1 ");
                    $f_id = (int)$r_file->fetch_assoc()['id'];

                    $r2=$DB->query("SELECT episode_id FROM iptv_provider_content_episodes 
                                WHERE episode_id = '{$episode_id}' AND provider_id = '$account->id' ");

                    if($r2->num_rows < 1) {
                        $DB->query("INSERT INTO iptv_provider_content_episodes 
                                (content_vod_id,  provider_id, episode_id, episode_number, season_number, series_id, name, image, added_id ) 
                                VALUES ('$content_vod_id','$account->id', '$episode_id', '$episode_num', '$season_number', '{$vod['series_id']}',
                                        '$episode_name','$episode_image', '$f_id' )");
                    } else {
                        $DB->query("UPDATE iptv_provider_content_episodes SET added_id = '$f_id' 
                            WHERE provider_id = '{$account->id}' AND episode_id = '{$episode_id}' ");
                    }

                }
            }
        }

    }


} catch(\Exception $exp ) {
    error_log($exp->getMessage());
}

