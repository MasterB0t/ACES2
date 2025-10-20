<?php

unset($_GET['action']);
require_once 'index.php';

$vod_id = (int)$_GET['vod_id'];


if(!$vod_id)
    setAjaxError("",\ACES\HttpStatusCode::BAD_REQUEST);

$r=$DB->query("SELECT o.*,c.name as category_name FROM iptv_ondemand o 
           RIGHT JOIN iptv_stream_categories c ON c.id = o.category_id
           RIGHT JOIN iptv_ondemand_in_bouquet b ON b.video_id = o.id
           WHERE o.id = '$vod_id' AND o.status = 1 AND b.bouquet_id IN ('{$ACCOUNT['bouquets']}')
");

if(!$row=$r->fetch_assoc())
    set_error("Not found.",\ACES\HttpStatusCode::NOT_FOUND);

$r2=$DB->query("SELECT vod_id FROM iptv_app_favorites WHERE profile_id = '{$ACCOUNT['profile_id']}' AND vod_id = $vod_id ");
$is_favorite = (bool)$r2->fetch_assoc();

$genre_str = $row['genre1'];
if($row['genre2']) $genre_str .= " / {$row['genre2']}";
if($row['genre3']) $genre_str .= " / {$row['genre3']}";

$genre = array( $row['genre1'], $row['genre2'], $row['genre3'] );

$age_rating = $row['age_rating'];
$age_rating_int = $row['age_rating_i'];

if(empty($row['release_date'])) $row['release_date'] = '0000-00-00';

if($row['trailer_link'])
    $row['trailer_link']=str_replace('https://www.youtube.com/watch?v=','',$row['trailer_link']);

$logo='';$back_logo='';

if(filter_var($row['logo'], FILTER_VALIDATE_URL)) $logo = $row['logo'];
else $logo = $row['logo'] ? "$HOST/logos/{$row['logo']}" : null;

if(filter_var($row['back_logo'], FILTER_VALIDATE_URL)) $back_logo = $row['back_logo'];
else $back_logo = $row['back_logo'] ? "$HOST/logos/{$row['back_logo']}" : null;


$runtime_str = '';

$release_year = (int)date("Y", strtotime($row['release_date']));


if($row['runtime_seconds'] < 3600 )
    $runtime_string = gmdate("i", $row['runtime_seconds']) . "m ";
else
    $runtime_string = gmdate("H", $row['runtime_seconds']) . "h " . gmdate("i", $row['runtime_seconds']) . "m ";

$cast = array_slice(explode(',',trim($row['cast']) ), 0 , 5 );

$is_series = $row['type'] == 'series';

$watched = false;

$file_arr = array('id' => null, 'video_url' => null, 'resume_position' => null, 'resume_percent' => null, 'duration' => null );
$episode = null;
if(!$is_series) {

    $r4=$DB->query("SELECT episode_id FROM iptv_app_watched WHERE episode_id = '{$episode['id']}' AND profile_id = '{$ACCOUNT['profile_id']}'  ");
    $watched = (bool)$r4->fetch_assoc();

    $r_file = $DB->query("SELECT id,container,duration  FROM iptv_video_files WHERE movie_id = '{$row['id']}'");
    if($file_row = $r_file->fetch_assoc()) {

        $r_resume = $DB->query("SELECT resume_position,resume_position_percent as resume_percent FROM iptv_app_video_position 
                       WHERE file_id = '{$file_row['id']}' AND profile_id = '{$ACCOUNT['profile_id']}' "  );
        $resume = $r_resume->fetch_assoc();

        $file_arr = array(
            'id' => $file_row['id'],
            'video_url' => $HOST . "/movie/$USERNAME/$PASSWORD/{$row['id']}.{$file_row['container']}",
            'resume_position' => (int)$resume['resume_position'],
            'resume_percent' => (int)$resume['resume_percent'],
            "duration" => (int)$file_row['duration']
        );
    }

} else {
    //GETTING NEXT EPISODE.

    $r_next=$DB->query("SELECT l.episode_id
        FROM iptv_app_last_episode l 
        RIGHT JOIN iptv_series_season_episodes e ON l.episode_id = e.id
        WHERE l.series_id = $vod_id AND e.status = 1 ");


    if(!$next_episode_id = $r_next->fetch_assoc()['episode_id']) {

        //HAVEN'T START WATCHING THE SERIES. GETTING FIRST EPISODE.
        $r_find = $DB->query("SELECT e.id as episode_id  FROM iptv_series_seasons s
             RIGHT JOIN iptv_series_season_episodes e ON s.id = e.season_id
             WHERE s.series_id = $vod_id ORDER BY s.number,e.number ASC LIMIT 1");

        error_log("SELECT e.id as episode_id  FROM iptv_series_seasons s
             RIGHT JOIN iptv_series_season_episodes e ON s.id = e.season_id
             WHERE s.series_id = $vod_id ORDER BY s.number,e.number ASC LIMIT 1");

        $next_episode_id = $r_find->fetch_assoc()['episode_id'] ?: null;

    }


    $r_episode=$DB->query("SELECT e.id,e.number,e.title,e.about as plot,e.runtime_seconds,e.logo as image,
       e.rate,e.release_date,e.tmdb_id,s.number as season_number
            FROM iptv_series_season_episodes e
            RIGHT JOIN iptv_series_seasons s ON s.id = e.season_id        
            WHERE e.id = '$next_episode_id' AND e.status = 1 ");

    if($episode = $r_episode->fetch_assoc()) {

        if(!filter_var($episode['image'], FILTER_VALIDATE_URL))
        $episode['image'] = $episode['image'] ? $HOST."/logos/".$episode['image'] : null;

        $r_file = $DB->query("SELECT id,resolution,tracks,container,audio_codec,video_codec,bitrate 
                            FROM iptv_video_files WHERE episode_id = '{$episode['id']}' ");
        $file = $r_file->fetch_assoc();
        $file['container'] = $file['container'] ?: "mp4";
        $file['video_url'] = $HOST."/series/$USERNAME/$PASSWORD/{$episode['id']}.{$file['container']}";


        $r_resume = $DB->query("SELECT resume_position,resume_position_percent as resume_percent FROM iptv_app_video_position 
                    WHERE profile_id = '{$ACCOUNT['profile_id']}' AND file_id = '{$file['id']}' ");

        $resume = $r_resume->fetch_assoc();
        $episode['resume_position'] = (int)$resume['resume_position'];
        $episode['resume_percent'] = (int)$resume['resume_percent'];
        $episode['video_file'] = $file;

    }

    //GETTING SEASONS.

}

$d = array (
    'id' => $row['id'],
    'type' => $row['type'],
    'is_series' => $is_series,
    'cover' => $logo,
    'back_cover' => $back_logo,
    'title' => $row['name'],
    'logo_name' => empty($row['logo_name']) ? null : $row['logo_name'],
    'plot' => $row['about'],
    'genre' => $genre,
    'genre_str' => $genre_str,
    'rating' => $row['rating'],
    'rating_5based' => round( ($row['rating']*.5), 1 ),
    'release_date' => $row['release_date'],
    'release_year' => $release_year,
    'add_date' => $row['add_date'],
    'runtime_seconds' => $row['runtime_seconds'],
    'runtime' => gmdate("H:i:s", $row['runtime_seconds']),
    'runtime_string' => $runtime_string,
    'youtube_trailer' => $row['trailer_link'],
    'cast' => $cast,
    'cast_str' => implode(" /",$cast),
    'director' => $row['director'],
    'age_rating_int' => $row['age_rating_i'],
    'age_rating' => $row['age_rating'],
    'tmdb_url' => 'https://www.themoviedb.org/movie/'.$row['tmdb_id'],
    'tmdb_id' => $row['tmdb_id'],
    'is_favorite' => $is_favorite,
    'category_id' => $row['category_id'],
    'category_name' => $row['category_name'],
    'video_file' => $file_arr,
    'next_episode' => $episode

);

echo json_encode($d);