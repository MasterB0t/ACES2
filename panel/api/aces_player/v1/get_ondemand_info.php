<?php

include 'main.php';

$vod_id = (int)$_GET['vod_id'];

if(!$vod_id)
    set_error("NO VALID VOD ID OR MISSING");

$r=$DB->query("SELECT * FROM iptv_ondemand WHERE id = '{$_GET['vod_id']}' AND status = 1 ");
if(!$row=mysqli_fetch_array($r)) set_error("No info found...");


$favorite = 0;
$r2=$DB->query("SELECT vod_id FROM iptv_app_favorites WHERE profile_id = '{$ACCOUNT['profile_id']}' AND vod_id = $vod_id ");
if($r2->fetch_assoc()) $favorite = 1;


$genre_str = $row['genre1'];
if($row['genre2']) $genre_str .= " / {$row['genre2']}";
if($row['genre3']) $genre_str .= " / {$row['genre3']}";

$genre = array( $row['genre1'], $row['genre2'], $row['genre3'] );

if(empty($row['release_date'])) $row['release_date'] = '0000-00-00';

if($row['trailer_link'])
    $row['trailer_link']=str_replace('https://www.youtube.com/watch?v=','',$row['trailer_link']);

$logo='';$back_logo='';
if($row['logo']) $logo = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}";
if($row['back_logo']) $back_logo = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['back_logo']}";

$runtime_str = '';

$release_year = (int)date("Y",strtotime($row['release_date']));

if($row['runtime_seconds'] < 3600 )
    $runtime_string = gmdate("i", $row['runtime_seconds']) . "m ";
else
    $runtime_string = gmdate("H", $row['runtime_seconds']) . "h " . gmdate("i", $row['runtime_seconds']) . "m ";

$cast = array_slice(explode(',',trim($row['cast']) ), 0 , 5 );

$d = array (
    'id' => $row['id'],
    'type' => $row['type'],
    'logo' => $logo,
    'back_logo' => $back_logo,
    'name' => $row['name'],
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
    'director' => $row['director'],
    'description' => $row['about'],
    'age' => $row['age_rating_i'],
    'rating_mpaa' => $row['age_rating'],
    'rating_count_kinopoisk' => 0,
    'country' => '',
    'kinopoisk_url' => 'https://www.themoviedb.org/movie/'.$row['tmdb_id'],
    'tmdb_id' => $row['tmdb_id'],
    'is_favorite' => $favorite
);


$resume_position=0;
if($row['type'] == 'movies') { 
    $r4 = $DB->query("SELECT id,tracks  FROM iptv_video_files WHERE movie_id = '$vod_id' ");
    $file_info = $r4->fetch_assoc();
    $r3 = $DB->query("SELECT resume_position,resume_position_percent FROM iptv_app_video_position WHERE profile_id = '{$ACCOUNT['profile_id']}' AND file_id = '{$file_info['id']}' ");
    if($row3=$r3->fetch_assoc()) {  $resume_position = $row3['resume_position']; $resume_position_percent = $row3['resume_position_percent']; }
    $d['file_id'] = $file_info['id'];
    $d['resume_position'] = $resume_position;
    $d['resume_position_percent'] = $resume_position_percent;
    $d['is_series'] = 0;


    $tracks = json_decode($file_info['tracks'], true);
    $d['audio_tracks'] = $tracks['audio'];
    $d['subtitle_tracks'] = $tracks['subtitle'];

} else {
    $total_episodes = 0;
    $first_episode = [];
    $r_seasons = $DB->query("SELECT id,number FROM iptv_series_seasons WHERE series_id = '{$row['id']}' ");
    $d['total_seasons'] = $r_seasons->num_rows;
    while($row_seasons=$r_seasons->fetch_assoc()) {

        $r_episodes = $DB->query("SELECT id FROM iptv_series_season_episodes e WHERE status = 1 AND season_id = '{$row_seasons['id']}' ");
        $d['seasons'][] = array("season_number"=>$row_seasons['number'], 'episodes' => $r_episodes->num_rows );
        $total_episodes = $total_episodes + $r_episodes->num_rows;

        if(count($first_episode)<1) {
            $r_first_episode = $DB->query("SELECT e.number as episode_number, e.title, e.about, e.logo, e.id as episode_id,f.id as file_id, p.resume_position, p.resume_position_percent FROM iptv_series_season_episodes e 
                                    RIGHT JOIN iptv_video_files f ON f.episode_id = e.id
                                    LEFT JOIN iptv_app_video_position p ON p.file_id = f.id
                                    WHERE e.season_id = {$row_seasons['id']} AND status = 1  ORDER BY number ASC 
                                ");
            $first_episode = $r_first_episode->fetch_assoc();
            $first_episode['season_number'] = $row_seasons['number'];
            $first_episode['resume_position'] = 0;
            $first_episode['resume_position_percent'] = 0;
        }

    }
    $d['total_episodes'] = $total_episodes;
    $d['is_series'] = 1;

    $d['next_season'] = 0;
    $d['next_episode'] = 0;
    $d['next'] = $first_episode;


    $r=$DB->query("SELECT l.season_number, l.episode_number, e.title, e.about, e.logo, e.id as episode_id,f.id as file_id, p.resume_position, p.resume_position_percent FROM  iptv_app_last_episode l 
                             RIGHT JOIN iptv_series_season_episodes e ON e.id = l.episode_id
                             RIGHT JOIN iptv_video_files f ON f.episode_id = e.id                                                            
                             LEFT JOIN iptv_app_video_position p ON p.file_id = f.id                    
                             WHERE l.series_id = '{$row['id']}' AND l.profile_id = '{$ACCOUNT['profile_id']}' ");
    if($row_next=$r->fetch_assoc()) {
        $d['next_season'] = $row_next['season_number'];
        $d['next_episode'] = $row_next['episode_number'];
        $d['next'] = $row_next;

    }

}


echo json_encode($d);