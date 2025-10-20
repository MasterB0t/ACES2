<?php

include 'main.php';

if(! isset($_GET['series_id']) || !is_numeric($_GET['series_id']) || $_GET['series_id'] < 1) die;

$r=$DB->query("SELECT o.*,l.season_number as next_season, l.episode_number as next_episode FROM iptv_ondemand o 
           LEFT JOIN iptv_app_last_episode l ON l.series_id = o.id AND l.profile_id = '{$ACCOUNT['profile_id']}'
           WHERE o.status = 1 AND o.type = 'series' AND o.id = '{$_GET['series_id']}'  ");
if(!$row_series=$r->fetch_assoc()) die;

$genres = $row_series['genre1'];
if($row_series['genre2']) $genres .= " / {$row_series['genre2']}";
if($row_series['genre3']) $genres .= " / {$row_series['genre3']}";
$tmdb_id = $row_series['tmdb_id'];



if(empty($row_series['release_date'])) $row_series['release_date'] = '0000-00-00';

if($row_series['trailer_link'])
    $row_series['trailer_link']=str_replace('https://www.youtube.com/watch?v=','',$row_series['trailer_link']);


$cast = array_slice(explode(',',trim($row_series['cast']) ), 0 , 5 );

$info = array(
    'name' => $row_series['name'],
    'cover' => "http://{$_SERVER['HTTP_HOST']}/logos/{$row_series['logo']}",
    'plot' => $row_series['about'],
    'cast' => $cast,
    'director' => $row_series['director'],
    'genre' => $genres,
    'releaseDate' => $row_series['release_date'],
    'last_modified' => (string)(int)strtotime($row_series['release_date']),
    'rating' => $row_series['rating'],
    'rating_5based' => round( ($row_series['rating']*.5), 1 ),
    'backdrop_path' => array ("http://{$_SERVER['HTTP_HOST']}/logos/{$row_series['back_logo']}"),
    'youtube_trailer' => $row_series['trailer_link'],
    'episode_run_time' => 0,
    'category_id' => $row_series['category_id'],
    'is_series' => 1,
    'next_season' => $row_series['next_season'],
    'next_episode' => $row_series['next_episode'],
);


$episodes = array();
$seasons = array();
$rs=$DB->query("SELECT s.id,s.number, count(e.id) as episode_count FROM iptv_series_seasons s "
    . " RIGHT JOIN iptv_series_season_episodes e ON e.season_id = s.id and e.status = 1  "
    . " WHERE s.series_id = '{$_GET['series_id']}'  GROUP BY s.id ORDER BY s.number ");

while($row=mysqli_fetch_array($rs)) {

    $e = array();
    $re=$DB->query("SELECT e.*,f.duration,f.container  FROM iptv_series_season_episodes e
        INNER JOIN iptv_video_files f ON f.episode_id = e.id 
        WHERE e.season_id = {$row['id']} AND e.status = 1 ORDER BY e.number");
    while($row_e=mysqli_fetch_array($re)) {

        $img=null;

        if(empty($row_e['logo'])) {

            $TMDB_INFO=getEpisodeInfo($tmdb_id,$row['number'],$row_e['number'],$row_e['id']);

            $row_e['logo'] =  $TMDB_INFO['logo'];
            $row_e['release_date'] = $TMDB_INFO['air_date'];

        }

        $duration='00:00:00';
        if($row_e['duration']) {
            $duration = gmdate("H:i:s", $row_e['duration']);
        }

        $resume_position_percent = 0;$resume_position=0;

        $r4 = $DB->query("SELECT id FROM iptv_video_files WHERE episode_id = '{$row_e['id']}' ");
        $file_id = $r4->fetch_assoc()['id'];
        $r3 = $DB->query("SELECT resume_position,resume_position_percent FROM iptv_app_video_position WHERE profile_id = '{$ACCOUNT['profile_id']}' AND file_id = '$file_id' ");
        if($row3=$r3->fetch_assoc()) {  $resume_position = $row3['resume_position']; $resume_position_percent = $row3['resume_position_percent']; }

        $watched = 0;
        $r4=$DB->query("SELECT episode_id FROM iptv_app_watched WHERE episode_id = '{$row_e['id']}' AND profile_id = '{$ACCOUNT['profile_id']}'  ");
        if($r4->fetch_assoc()) $watched = 1;

        $e[] = array(
            'id' => $row_e['id'],
            'file_id' => $file_id,
            'resume_position' => (int)$resume_position,
            'resume_position_percent' => $resume_position_percent,
            'episode_number'=> (int)$row_e['number'],
            'watched' => $watched,
            'title' => $row_e['title'],
            'container_extension' => $row_e['container'],
            'plot' => $row_e['about'],
            'info' => array(
                'movie_image'=>"http://{$_SERVER['HTTP_HOST']}/logos/".$row_e['logo'],
                'backdrop_path'=>"http://{$_SERVER['HTTP_HOST']}/logos/".$row_e['logo'],
                'tmdb_id'=>$tmdb_id,
                'plot' =>'',
                'duration_secs'=> $row_e['duration'],
                'duration' => $duration,
                'video'=>array(),
                'audio'=>array(),
                'bitrate' => 1,
                'rating' => 0,
                'season' => $row['number']
            ),
            //'custom_sid'=>null,
            //'added'=>'0',
            'season_number'=>(int)$row['number']);


    }
    $episodes[$row['number']] = $e;

    $seasons[] = array (
        'air_date' => $row_e['release_date'],
        'episode_count' => $row['episode_count'],
        'id' => $row['id'],
        'name' => "Season {$row['number']}",
        'overview' => '',
        'season_number' => $row['number'],
        'cover' => "http://{$_SERVER['HTTP_HOST']}/logos/{$row_series['logo']}",
        'cover_big' => "http://{$_SERVER['HTTP_HOST']}/logos/{$row_series['logo']}"
    );

}

$json['seasons'] = $seasons;
$json['info'] = $info;
$json['episodes'] = $episodes;


echo json_encode($json);