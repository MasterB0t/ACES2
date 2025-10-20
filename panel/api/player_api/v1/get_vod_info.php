<?php

include 'main.php';

if(! isset($_GET['vod_id']) || !is_numeric($_GET['vod_id']) || $_GET['vod_id'] < 1) die;

$r=$DB->query("SELECT * FROM iptv_ondemand WHERE id = '{$_GET['vod_id']}' AND status = 1 ");
if(!$row=mysqli_fetch_array($r)) die;

$genres = $row['genre1'];
if($row['genre2']) $genres .= " / {$row['genre2']}";
if($row['genre3']) $genres .= " / {$row['genre3']}";

if(empty($row['release_date'])) $row['release_date'] = '0000-00-00';

if($row['trailer_link'])
    $row['trailer_link']=str_replace('https://www.youtube.com/watch?v=','',$row['trailer_link']);

$logo = '';
if(!empty($row['logo']))
    $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
        ? $row['logo']
        : "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}" ;



$back_logo = '';
if(!empty($row['back_logo']))
    $back_logo = filter_var($row['back_logo'], FILTER_VALIDATE_URL)
        ? $row['back_logo']
        : "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row['back_logo']}" ;


$d = array (
    'info' =>
        array (
            'movie_image' => $logo,
            'genre' => $genres,
            'plot' => $row['about'],
            'cast' => $row['cast'],
            'rating' => $row['rating'],
            'director' => $row['director'],
            'releasedate' => $row['release_date'],
            'duration_secs' => $row['runtime_seconds'],
            'duration' => gmdate("H:i:s", $row['runtime_seconds']),
            'video' =>
                array (),
            'audio' =>
                array (),
            'bitrate' => 1,
            'kinopoisk_url' => 'https://www.themoviedb.org/movie/'.$row['tmdb_id'],
            'name' => $row['name'],
            'o_name' => $row['name'],
            'cover_big' => "$logo",
            'episode_run_time' => 0,
            'youtube_trailer' => $row['trailer_link'],
            'actors' => $row['cast'],
            'description' => $row['about'],
            'age' => '',
            'rating_mpaa' => '',
            'rating_count_kinopoisk' => 0,
            'country' => '',
            'backdrop_path' =>
                array ($back_logo),
            'tmdb_id' => $row['tmdb_id'],
        ),
    'movie_data' =>
        array (
            'stream_id' => $row['id'],
            'name' => $row['name'],
            'added' => strtotime($row['add_date']),
            'category_id' => $row['category_id'],
            'container_extension' => 'mp4',
            'custom_sid' => '',
            'direct_source' => '',
        ),
);

echo json_encode($d);