<?php

header('Content-Type: application/json');
header('Connection: close');

logfile("get_ordered_list_vod.php");

$p = 0;
if(is_numeric($_GET['p']) && $_GET['p'] > 0 ) { 
    if($_GET['p'] > 1 ) $p = (($_GET['p']-1) * 14 );
    else $p = 0;  
} else $_GET['p'] = 1;

//$SQL_GENRE = '';
//if(is_numeric($_GET['genre']) && $_GET['genre'] > 0 ) $SQL_GENRE = "AND category_id = {$_GET['genre']}";

$SQL_ADULTS = '';
if( $MAG_ADULTS == 0 ) $SQL_ADULTS = " AND cat.adults = 0 ";

$SQL_CAT = '';$SQL_IN_CAT = '';
if($_GET['category'] == 'movies') $SQL_CAT = " AND type = 'movies' ";
else if($_GET['category'] == 'series') $SQL_CAT = " AND type = 'series' ";
else if($_GET['category'] == 'replay') $SQL_CAT = " AND type = 'replay' ";
else if($_GET['category'] == 'adults') $SQL_CAT = " AND type = 'adults' ";
//else if(is_numeric($_GET['category']) && $_GET['category'] > 0 ) $SQL_CAT = " AND o.category_id = '{$_GET['category']}' ";
else if(is_numeric($_GET['category']) && $_GET['category'] > 0 ) { 
    $SQL_CAT_JOIN  = "  RIGHT JOIN iptv_in_category ic ON ic.category_id = '{$_GET['category']}' AND ic.vod_id = o.id "
    . " LEFT JOIN iptv_stream_categories cat ON ic.category_id = cat.id ";
} else {
    //REMOVE THIS TO SPEED UP THINGS IT WAS NECESSARY ?????
//    $SQL_CAT_JOIN = " RIGHT JOIN iptv_in_category ic ON ic.vod_id = o.id "
//    . " LEFT JOIN iptv_stream_categories cat ON ic.category_id = cat.id  ";

    $SQL_CAT_JOIN = "RIGHT JOIN  iptv_stream_categories cat ON o.category_id = cat.id ";

}


$SQL_SEARCH = '';
if($_GET['search']) $SQL_SEARCH = "AND o.name like '%".$DB->real_escape_string($_GET['search'])."%' ";

if($_GET['sortby'] == 'title') $sort_by = 'o.name';
else if($_GET['sortby'] == 'rating' || $_GET['sortby'] == 'top') $sort_by  = 'o.rating';
else $sort_by = 'o.id';

$SQL_FAV = '';
if($_GET['fav'] == 1  ) {
    if(!is_array($FAVS['video'])) {
        $js = array (
            'js' =>
                array (
                    'total_items' => 0,
                    'max_page_items' => 14,
                    'selected_item' => 0,
                    'cur_page' => 0,
                    'data' => []
                ),
            'text' => '',
        );

        echo json_encode($js);
        die;
    }


    $f = implode(',',$FAVS['video']);
    $SQL_FAV = " AND o.id IN ( $f ) ";
}

$sql_type = '';
if(empty($MAG_EXTRA_OPTIONS['no_series_club'])) { 

    if($_GET['type'] == 'series') $sql_type = "AND type = 'series'";
    else $sql_type = "AND type = 'movies'";
}

$r = $DB->query("SELECT bouquets,token,username FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=mysqli_fetch_array($r);
$device['bouquets'] = join("','", unserialize($device['bouquets']) );

$series_id = (int)$_GET['movie_id'];
$season_id = (int)$_GET['season_id'];
$episode_id = (int)$_GET['episode_id'];

//THIS MEAN WE WILL LIST SEASONS OF A SERIES.
if($series_id ) {

    if($episode_id) {

        $js = array (
            'js' =>
            array (
              'total_items' => '1',
              'max_page_items' => 14,
              'selected_item' => 0,
              'cur_page' => 0,
              'data' =>
              array (
                0 =>
                array (
                  'id' => $_GET['episode_id'],
                  'video_id' => $series_id,
                  'series_id' => $_GET['season_id'],
                  'file_type' => 'video',
                  'protocol' => 'custom',
                  'url' => "ffmpeg ".HOST."/series/{$device['username']}/$MAG_TOKEN/{$_GET['episode_id']}.mp4",
                  'file_name' => '',
                  'languages' => NULL,
                  'quality' => '0',
                  'volume_level' => '0',
                  'accessed' => '1',
                  'status' => '1',
                  'date_add' => '0000-00-00 00:00:00',
                  'date_modify' => '0000-00-00 00:00:00',
                  'tmp_link_type' => NULL,
                  'for_rent' => 0,
                  'name' => 'Play Episode',
                  'is_file' => true,
                  'cmd' => "ffmpeg ".HOST."/series/{$device['username']}/$MAG_TOKEN/{$_GET['episode_id']}.mp4",
                ),
              ),
            ),
            'text' => '',
          );

        echo json_encode($js);
        die;

    } else if(!empty($_GET['season_id']) && is_numeric($_GET['season_id'])) {

        //GETTING EPISODES OF SEASON
        //$r=$DB->query("SELECT * FROM iptv_series_season_episodes WHERE season_id = {$_GET['season_id']}");

        $r2_total = $DB->query("SELECT e.*,s.number as season_number FROM iptv_series_season_episodes e 
            INNER JOIN iptv_series_seasons s ON s.id = e.season_id WHERE e.season_id = {$_GET['season_id']} ");

        $r2=$DB->query("SELECT e.*,s.number as season_number FROM iptv_series_season_episodes e INNER JOIN iptv_series_seasons s ON s.id = e.season_id WHERE e.season_id = {$_GET['season_id']} LIMIT $p,14");

        $data = array();
        while($row=mysqli_fetch_array($r2)) {

            $d = array (
                  'id' => $row['id'],
                  'season_id' => $_GET['season_id'],
                  'series_number' => $row['season_number'],
                  'series_name' => $row['title'],
                  'series_original_name' => '',
                  'series_files' => '1',
                  'date_add' => '0000-00-00 00:00:00',
                  'date_modify' => '0000-00-00 00:00:00',
                  'name' => "Episode {$row['number']}. {$row['title']}",
                  'is_episode' => true,
                  'series' =>
                  array (
                  ),
                );

            $data[] = $d;

        }

        $js = array (
            'js' =>
            array (
              'total_items' => mysqli_num_rows($r2_total),
              'max_page_items' => 14,
              'selected_item' => 0,
              'cur_page' => $_GET['p'],
              'data' => $data
            ),
            'text' => '',
          );

        echo json_encode($js);
        die;

    } else {

        //GETTING SEASONS OF SERIES.

        $r_total_season = $DB->query("SELECT id FROM iptv_series_seasons WHERE series_id = $series_id ");

        $r2=$DB->query("SELECT s.id as season_id, s.number as season_number, s.logo as season_logo, s.air_date as season_air_date,
       s.overview as season_overview, o.director , o.cast, o.rating, o.name, o.category_id, o.tmdb_id
                        FROM iptv_series_seasons s 
                        INNER JOIN iptv_ondemand o ON o.id = $series_id 
                        WHERE s.series_id = $series_id LIMIT $p,14");


        $data = array(); $i =0;
        while($row=mysqli_fetch_array($r2)) {

            $episodes = array();
            $r_episodes = $DB->query("SELECT id,number FROM iptv_series_season_episodes WHERE status = 1 AND season_id = '{$row['season_id']}' ORDER BY `number` ");
            while($row_episode=$r_episodes->fetch_assoc()) { $episodes[] = (int)$row_episode['number']; }

            $logo = filter_var($row['season_logo'], FILTER_VALIDATE_URL) === false  ?
                HOST . "/logos/{$row['season_logo']}" :
                $row['season_logo'];

            $data[] = array(
                'id' => $row['season_id'].":".$row['season_number'],
                'video_id' => $series_id,
                'owner' => '',
                'name' => 'Season '.$row['season_number'],
                'old_name' => '',
                'o_name' => 'Season '.$row['season_number'],
                'fname' => '',
                'description' => $row['season_overview'],
                'pic' => '',
                'cost' => 0,
                'time' => 'N/a',
                'file' => '',
                'path' => str_replace(" ","_",$row['name']),
                'protocol' => '',
                'rtsp_url' => '',
                'censored' => 0,
                'series' => $episodes,
                'volume_correction' => 0,
                'category_id' => (string)$row['category_id'],
                'genre_id' => 0,
                'genre_id_1' => 0,
                'genre_id_2' => 0,
                'genre_id_3' => 0,
                'hd'  => 1,
                'genre_id_4' => 0,
                'cat_genre_id_1' => (string)$row['category_id'],
                'cat_genre_id_2' => 0,
                'cat_genre_id_3' => 0,
                'cat_genre_id_4' => 0,
                'director' => $row['director'],
                'actors' => $row['cast'],
                'year' => $row['season_air_date'],
                'accessed' => 1,
                'status' => 1,
                'disable_for_hd_devices' => 0,
                'added' => '2024-06-03 22:50:01', ///REMOVED
                'count' => 0,
                "tmdb_id" => $row['tmdb_id'],
                "tmdb" => $row['tmdb_id'],
                'count_first_0_5' => 0,
                'count_second_0_5' => 0,
                'vote_sound_good' => 0,
                'vote_sound_bad' => 0,
                'vote_video_good' => 0,
                'vote_video_bad' => 0,
                'rate' => $row['rating'],
                'last_rate_update' => '',
                'last_played' => '',
                'for_sd_stb' => 0,
                'rating_imdb' => (int)$row['rating'],
                'rating_count_imdb' => '',
                'rating_last_update' => '0000-00-00 00:00:00',
                'age' => '12+', //REMOVE
                'high_quality' => 0,
                'rating_kinopoisk' => (int)$row['rating'],
                'comments' => '',
                'low_quality' => 0,
                'is_series' => 1,
                'year_end' => 0,
                'autocomplete_provider' => 'imdb',
                'screenshots' => "",
                'is_movie' => 1,
                'lock' => 0,
                'fav' => 0,
                'for_rent' => 0,
                "genres_str"=> "",
                'screenshot_uri' => "$logo",
                'cmd' => base64_encode(json_encode(array('type'=>'series','season_id' => $row['season_id'], 'season_num' => $row['season_number'] ))),
                'today' => 'today', //REMOVE
                'has_files' => 0

            );


        }


        $js = array (
            'js' => 
                array (
                  'total_items' => $r_total_season->num_rows,
                  'max_page_items' => 14,
                  'selected_item' => 0,
                  'cur_page' => $_GET['p'],
                  'data' => $data
                ),
          );


        echo json_encode($js);
        die;
        
    }
    
}



//$rtotal = $DB->query("SELECT o.*,cat.adults, DATEDIFF(NOW(),o.add_date) as days_added FROM iptv_ondemand_in_bouquet p
//                INNER JOIN iptv_ondemand o ON p.video_id = o.id $sql_type
//                $SQL_CAT_JOIN
//                WHERE o.enable = 1  AND o.status = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS $SQL_CAT $SQL_SEARCH $SQL_FAV GROUP BY o.id ");
//                
//
//
//$r2=$DB->query("SELECT o.*,cat.adults, DATEDIFF(NOW(),o.add_date) as days_added FROM iptv_ondemand_in_bouquet p
//                INNER JOIN iptv_ondemand o ON p.video_id = o.id $sql_type
//                $SQL_CAT_JOIN
//                WHERE o.enable = 1  AND o.status = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS $SQL_CAT $SQL_SEARCH $SQL_FAV GROUP BY o.id ORDER BY $sort_by DESC  LIMIT $p,14");


$rtotal = $DB->query("SELECT o.id FROM iptv_ondemand_in_bouquet p
                INNER JOIN iptv_ondemand o ON p.video_id = o.id $sql_type
                $SQL_CAT_JOIN
                WHERE o.enable = 1  AND o.status = 1 AND p.bouquet_id IN ('{$device['bouquets']}')  $SQL_CAT $SQL_SEARCH $SQL_FAV GROUP BY o.id ");



$r2=$DB->query("SELECT o.id,o.name,o.about,o.year,o.genre1,o.genre2,o.genre3,o.type,o.logo, cat.adults, o.add_date, o.director, o.cast, o.rating
                FROM iptv_ondemand_in_bouquet p
                INNER JOIN iptv_ondemand o ON p.video_id = o.id $sql_type
                $SQL_CAT_JOIN
                WHERE o.enable = 1 AND o.status = 1 AND p.bouquet_id IN ('{$device['bouquets']}')  $SQL_CAT $SQL_SEARCH $SQL_FAV
                GROUP BY o.id ORDER BY $sort_by
                DESC LIMIT $p,14");




$data = array();
while($row=$r2->fetch_assoc()) {
    
    $days_passed = strtotime($row['add_date']);
    $days_passed = round( (time()-$days_passed) / (60 * 60 * 24));
    $row['days_added'] = $days_passed;
    
    $lock = 0;
    if($_GET['category'] == 0 && $row['adults'] == 1 && $MAG_ADULT_PIN) $lock = 1;
    
    if($row['add_date'] == '0000-00-00 00:00:00') $days = "Unknown";
    else if($row['days_added'] > 365 )  $days = "More than a Year"; 
    else if($row['days_added'] > 180 ) $days = "Last Year"; 
    else if($row['days_added'] > 150 ) $days = '5 Months ago';
    else if($row['days_added'] > 120 ) $days = '4 Months ago';
    else if($row['days_added'] > 90 ) $days = '3 Months ago';
    else if($row['days_added'] > 60 ) $days = '2 Months ago';
    else if($row['days_added'] > 30 ) $days = 'Last Month';
    else if($row['days_added'] > 14 ) $days = 'This Month';
    else if($row['days_added'] > 7 ) $days = 'Last Week';
    else if($row['days_added'] > 2 ) $days = 'This Week';
    else if($row['days_added'] > 1 ) $days = 'Two Days Ago';
    else if($row['days_added'] > 0 ) $days = 'Yesterday';
    else if($row['days_added'] == 0 ) $days =  'Today';
    else $days ='Unknown';

    $genres = '';
    if($row['genre1']) $genres = $row['genre1'];
    if($row['genre2']) $genres .= ", ".$row['genre2'];
    if($row['genre3']) $genres .= ", ".$row['genre3'];


    $time = round($row['runtime_seconds']/60);
    
    if($row['type'] == 'series') { $is_series = 1; $is_movie= true; $serie = array(); }
    else { $is_series = 0; $is_movie = true; $serie=false; } 
    
    $fav=0;
    if(is_array($FAVS['video']) && @in_array($row['id'],$FAVS['video'])) $fav=1;

    $logo = filter_var($row['logo'], FILTER_VALIDATE_URL) === false ?
        HOST . "/logos/{$row['logo']}" :
        $row['logo'] ;

    
    if($row['type'] == 'series') {

        $d = array (
            'id' => $row['id'].":".$row['id'],
            'owner' => '',
            'name' => $row['name'],
            'old_name' => '',
            'o_name' => $row['name'],
            'fname' => '',
            'description' => $row['about'],
            'pic' => $logo,
            'cost' => '0',
            'time' => '0',
            'file' => '',
            'path' => str_replace(" ", "_", $row['name']),
            'protocol' => '',
            'rtsp_url' => '',
            'censored' => '0',
            'series' =>  array ( ),
            'volume_correction' => '0',
            'category_id' => '1',
            'genre_id' => '0',
            'genre_id_1' => '0',
            'genre_id_2' => '0',
            'genre_id_3' => '0',
            'genre_id_4' => '0',
            'cat_genre_id_1' => '0',
            'cat_genre_id_2' => '0',
            'cat_genre_id_3' => '0',
            'cat_genre_id_4' => '0',
            'director' => $row['director'],
            'hd' => 1,
            'actors' => $row['cast'],
            'year' => $row['year'],
            'accessed' => '1',
            'status' => '1',
            'disable_for_hd_devices' => '0',
            'added' => $row['add_date'],
            'count' => '0',
            'count_first_0_5' => '0',
            'count_second_0_5' => '0',
            'vote_sound_good' => '0',
            'vote_sound_bad' => '0',
            'vote_video_good' => '0',
            'vote_video_bad' => '0',
            'rate' => $row['rating'],
            'last_rate_update' => NULL,
            'last_played' => NULL,
            'for_sd_stb' => '0',
            'kinopoisk_id' => '',
            'rating_kinopoisk' => $row['rating'],
            'rating_count_kinopoisk' => '',
            'rating_imdb' => $row['rating'],
            'rating_count_imdb' => '',
            'rating_last_update' => NULL,
            'age' => '',
            'rating_mpaa' => '',
            'high_quality' => '1',
            'comments' => NULL,
            'low_quality' => 0,
            'country' => '',
            'is_series' => '1',
            'year_end' => '0',
            'autocomplete_provider' => NULL,
            'screenshots' => '1',
            'is_movie' => true,
            'lock' => $lock,
            'fav' => $fav,
            'for_rent' => 0,
            'has_files' => 1,
            'screenshot_uri' => $logo,
            'genres_str' => $genres,
            'cmd' => "{$row['id']}",
            'yesterday' => $days
        );

            
    } else {

        $d=array (
            'id' => $row['id'],
            'owner' => '',
            'name' => $row['name'],
            'old_name' => '',
            'o_name' => $row['name'],
            'fname' => '',
            'description' => $row['about'],
            'pic' => $logo,
            'cost' => '0',
            'time' => $time,
            'file' => '',
            'path' => '',
            'protocol' => '',
            'rtsp_url' => "",
            'censored' => $row['adults'],
            'series' => array(),
            'volume_correction' => '0',
            'category_id' => '0',
            'genre_id' => '0',
            'genre_id_1' => '0',
            'genre_id_2' => '0',
            'genre_id_3' => '0',
            'genre_id_4' => '0',
            'cat_genre_id_1' => '0',
            'cat_genre_id_2' => '0',
            'cat_genre_id_3' => '0',
            'cat_genre_id_4' => '0',
            'hd' => 0,
            'director' => $row['director'],
            'actors' => $row['cast'],
            'year' => $row['year'],
            'accessed' => '1',
            'status' => '1',
            'disable_for_hd_devices' => '0',
            'added' => $row['add_date'],
            'count' => '1',
            'count_first_0_5' => '0',
            'count_second_0_5' => '1',
            'vote_sound_good' => '0',
            'vote_sound_bad' => '0',
            'vote_video_good' => '0',
            'vote_video_bad' => '0',
            'rate' => NULL,
            'last_rate_update' => NULL,
            'last_played' => '0000-00-00 00:00:00',
            'for_sd_stb' => '0',
            'kinopoisk_id' => '',
            'rating_kinopoisk' => $row['rating'],
            'rating_count_kinopoisk' => '',
            'rating_imdb' => $row['rating'],
            'rating_count_imdb' => '',
            'rating_last_update' => NULL,
            'age' => '',
            'rating_mpaa' => '',
            'high_quality' => '1',
            'comments' => NULL,
            'low_quality' => 0,
            'country' => '',
            'is_series' => 0,
            'year_end' => '0',
            'autocomplete_provider' => NULL,
            'screenshots' => '',
            'is_movie' => $is_movie,
            'lock' => $lock,
            'fav' => $fav,
            'for_rent' => 0,
            'has_files' => 0,
            'screenshot_uri' => $logo,
            'genres_str' => $genres,
            'cmd' => base64_encode(json_encode(array('type'=>'movies','movie_id' =>  $row['id'] )))  ,
            'yesterday' => $days
          );


    }
    
    $data[] = $d;
}




$total = mysqli_num_rows($rtotal);

if( $_REQUEST['p'] != 0 && is_numeric($_COOKIE['mag-last_vod_page']) &&  $_COOKIE['mag-last_vod_page'] > $_GET['p'] ) $selected = 14;
else $selected = 1;

$js = array (
  'js' => 
  array (
    'total_items' => $total,
    'max_page_items' => 14,
    'selected_item' => $selected,
    'cur_page' => $_GET['p'],
    'data' => $data
      
  ),
  'text' => '',
);


setcookie('mag-last_vod_page',$_GET['p']); 
echo json_encode($js);
die;