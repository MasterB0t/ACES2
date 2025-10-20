<?php

include 'main.php';

$building_cache = 0;


if($config['iptv']['api_cache']) {
    ignore_user_abort(true);

    $cache_filename = str_replace("'", '', $ACCOUNT['bouquets']);
    $cache_filename = explode(",",$cache_filename);
    sort($cache_filename);
    $cache_filename = implode("_",$cache_filename);
    if($ACCOUNT['adults'] ) $cache_filename .= "_wa";
    else $cache_filename .= "_na";

    $Cache = new Cache("/home/aces/cache/series_streams_$cache_filename", $config['iptv']['api_cache'],$config['iptv']['api_cache_compressed']);
    if ($data = $Cache->get(true)) {
        echo $data;
        fastcgi_finish_request();
        if(!$Cache->isExpired()) {
            die;
        } else {

            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/build_cache_series_".$cache_filename)) {
                exit;
            }

            $building_cache = 1;
            //touch("/home/aces/run/build_cache_series_" . $cache_filename);

        }
    }

}

$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') "; $sql_cat='';

if($filter_cat = (int)$_GET['category_id']) {

    //$sql_cat = " AND cat.id = '$filter_cat' ";
    $join_category = "RIGHT JOIN iptv_in_category i ON i.category_id  = $filter_cat AND i.vod_id = o.id ";
    
}

if($ACCOUNT['adults'] == 0 )  $sql .= ' AND cat.adults = 0 ';


$d = array();$i=1;

//$r=$DB->query("SELECT o.* FROM iptv_ondemand_in_bouquet p INNER JOIN iptv_ondemand o ON ( o.id = p.video_id ) RIGHT JOIN iptv_stream_categories cat on cat.id = o.category_id WHERE o.status = 1 AND o.type = 'series' $sql GROUP BY o.id ORDER BY p.i  ");

//$r=$DB->query("SELECT o.*,cat.name as category_name, cat.id as category_id
//FROM iptv_in_category i
//RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
//RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
//RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
//WHERE o.status = 1 AND o.type = 'series' $sql
//GROUP BY i.i ORDER BY p.i ");


$r=$DB->query("SELECT o.id,o.name,o.genre1,o.genre2,o.genre3,o.add_date,o.trailer_link,o.logo,o.about,o.cast,o.director,
       o.release_date,o.rating, o.back_logo,o.runtime_seconds,
       cat.name as category_name, cat.id as category_id
FROM iptv_ondemand o
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
$join_category
WHERE o.status = 1 AND o.type = 'series' $sql 
GROUP BY o.id ORDER BY p.i");

ob_start();

while($row=$r->fetch_assoc()) {


    $categories = [];
    $r_cats = $DB->query("SELECT i.category_id FROM iptv_in_category i
                   RIGHT JOIN iptv_stream_categories c ON c.id = i.category_id
                   WHERE vod_id = '{$row['id']}'");

    $category_id = 0;
    while($row_cats = $r_cats->fetch_assoc()) {
        if(!$category_id) $category_id = (int)$row_cats['category_id'];
        $categories[] = (int)$row_cats['category_id'];
    }


    if( strtotime($row['add_date']) < 1 ) $add_date = 0;
    else $add_date = strtotime($row['add_date']);
    
    $genres = $row['genre1'];
    if($row['genre2']) $genres .= " / {$row['genre2']}";
    if($row['genre3']) $genres .= " / {$row['genre3']}";
    
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

    $d[] = array( 
        'num' => $i,
        'name' => $row['name'],
        'series_id' => (int)$row['id'],
        'cover' => $logo,
        'plot' => $row['about'],
        'cast' => $row['cast'],
        'director' => $row['director'],
        'genre' => $genres,
        'releaseDate' => $row['release_date'],
        'last_modified' =>  (string)$add_date,
        'rating' => $row['rating'],
        'rating_5based' => round( ($row['rating']*.5), 1 ),
        'backdrop_path' => $back_logo,
        'youtube_trailer' => $row['trailer_link'],
        'episode_run_time' => $row['runtime_seconds']/60,
        'category_id' => $category_id,
        'category_ids' => $categories,
    );
    $i++;
}


if(!$building_cache)
    echo json_encode($d);
else {
    $a=0;
    //unlink("/home/aces/run/build_cache_series_" . $cache_filename);
}
  
ob_end_flush();
fastcgi_finish_request();


//$Cache->saveit(json_encode($d));
if($config['iptv']['api_cache'])
    $Cache->saveit(json_encode($d));
    //SaveCache("/home/aces/cache/series_streams_$cache_filename",json_encode($d));