<?php
include 'main.php';


if(! isset($_GET['series_id']) || !is_numeric($_GET['series_id']) || $_GET['series_id'] < 1) die;

function getEpisodeInfo($series_imdb_id,$season_number,$episode_number,$episode_id) {

    global $TMDB_API_KEY, $DB;

    if(!$TMDB_API_KEY) {
        $r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
        if(!$TMDB_API_KEY=$r->fetch_assoc()['value']) return false;
    }

    $url = 'https://api.themoviedb.org/3/tv/'.$series_imdb_id.'/season/'.$season_number.'/episode/'.$episode_number.'?api_key='.$TMDB_API_KEY.'&language=en-US';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $e_tmdb = json_decode(curl_exec($ch),1);

    if(!$e_tmdb)  return false;

    $logo_local_path = "/home/aces/tmp/se-$episode_id.jpg";
    $fp = fopen ($logo_local_path, 'w+') ;
    $ch = curl_init("http://image.tmdb.org/t/p/w500".$e_tmdb['still_path']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    $img_name = "e-".md5_file($logo_local_path).".jpg";
    copy($logo_local_path, "/home/aces/panel/logos/$img_name" );
    unlink($logo_local_path);

    $e_tmdb['logo'] = $img_name;

    $DB->query("UPDATE iptv_series_season_episodes SET release_date = '{$e_tmdb['air_date']}', logo = '$img_name', rate = '{$e_tmdb['vote_average']}' WHERE id = $episode_id  ");

    return $e_tmdb;

}



$r=$DB->query("SELECT * FROM iptv_ondemand WHERE status = 1 AND type = 'series' and id = '{$_GET['series_id']}'  ");
if(!$row_series=mysqli_fetch_array($r)) die;

$genres = $row_series['genre1'];
if($row_series['genre2']) $genres .= " / {$row_series['genre2']}";
if($row_series['genre3']) $genres .= " / {$row_series['genre3']}";
$tmdb_id = $row_series['tmdb_id'];



if(empty($row_series['release_date'])) $row_series['release_date'] = '0000-00-00';
    
if($row_series['trailer_link'])
    $row_series['trailer_link']=str_replace('https://www.youtube.com/watch?v=','',$row_series['trailer_link']);

$logo = '';
if(!empty($row['logo']))
    $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
        ? $row['logo']
        : "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}" ;


$back_logo = '';
if(!empty($row['back_logo']))
    $logo = filter_var($row['back_logo'], FILTER_VALIDATE_URL)
        ? $row['back_logo']
        : "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row['back_logo']}" ;

$info = array( 
    'name' => $row_series['name'],
    'cover' => "$logo",
    'plot' => $row_series['about'],
    'cast' => $row_series['cast'],
    'director' => $row_series['director'],
    'genre' => $genres,
    'releaseDate' => $row_series['release_date'],
    'last_modified' => (string)(int)strtotime($row_series['release_date']),
    'rating' => $row_series['rating'],
    'rating_5based' => round( ($row_series['rating']*.5), 1 ),
    'backdrop_path' => array ($back_logo),
    'youtube_trailer' => $row_series['trailer_link'],
    'episode_run_time' => 45,
    'category_id' => $row_series['category_id']
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

        $e[] = array( 'id' => $row_e['id'], 
            'episode_num'=> (int)$row_e['number'],
            'title' => $row_e['title'],
            'container_extension' => $row_e['container'],
            'info' => array(
                'movie_image'=>"{$protocol}://{$_SERVER['HTTP_HOST']}/logos/".$row_e['logo'],
                'backdrop_path'=>"{$protocol}://{$_SERVER['HTTP_HOST']}/logos/".$row_e['logo'],
                'tmdb_id'=>$tmdb_id,
                'plot' => $row_e['about'],
                'duration_secs'=> $row_e['duration'],
                'duration' => $duration,
                'video'=>array(),
                'audio'=>array(),
                'bitrate' => 1,
                'rating' => 0,
                'season' => $row['number']
            ),
            'custom_sid'=>null,
            'added'=>'0',
            'season'=>(int)$row['number'],
            'direct_source'=>'');

    }
    $episodes[$row['number']] = $e;
    
    $seasons[] = array (
      'air_date' => $row_e['release_date'],
      'episode_count' => $row['episode_count'],
      'id' => $row['id'],
      'name' => "Season {$row['number']}",
      'overview' => '',
      'season_number' => $row['number'],
      'cover' => "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row_series['logo']}",
      'cover_big' => "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row_series['logo']}"
    );

}

$json['seasons'] = $seasons;
$json['info'] = $info;
$json['episodes'] = $episodes;


echo json_encode($json);


