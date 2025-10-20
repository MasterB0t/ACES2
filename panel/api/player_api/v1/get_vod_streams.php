<?php

include 'main.php';

$building_cache = 0;


if($config['iptv']['api_cache'] ) {

    ignore_user_abort(true); //IGNORE ABORT TO BUILD CACHE.
    $cache_filename = str_replace("'", '', $ACCOUNT['bouquets']);
    $cache_filename = explode(",",$cache_filename);
    sort($cache_filename);
    $cache_filename = implode("_",$cache_filename);
    if($ACCOUNT['adults']) $cache_filename .= "_wa";
    else $cache_filename .= "_na";


    $Cache = new Cache("/home/aces/cache/vods_streams_$cache_filename", $config['iptv']['api_cache'],$config['iptv']['api_cache_compressed'] );
    if ($data = $Cache->get(true)) {
        echo $data;
        fastcgi_finish_request();
        if(!$Cache->isExpired()) {
            die;
        } else {


            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/build_cache_vods_".$cache_filename)) {
                exit;
            }

            $building_cache = 1;
            //touch("/home/aces/run/build_cache_vods_" . $cache_filename);


        }
    }

}

$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') ";
$sql_cat = '';
if($filter_cat = (int)$_GET['category_id']) {
    
    //$sql_cat .= " AND cat.id = '$filter_cat' ";
    $join_category = "RIGHT JOIN iptv_in_category i ON i.id  = $filter_cat AND i.vod_id = o.id ";

}

if($ACCOUNT['adults'] == 0 )  $sql .= ' AND cat.adults = 0 ';



$d = array();


$r=$DB->query("SELECT o.id,o.name,o.genre1,o.genre2,o.genre3,o.add_date,o.trailer_link,o.logo,o.about,o.cast,o.director,
       o.release_date,o.rating, o.back_logo,o.runtime_seconds,
       cat.name as category_name, cat.id as category_id
FROM iptv_ondemand o
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
$sql_cat
WHERE o.status = 1 AND o.type != 'series' $sql 
GROUP BY o.id ORDER BY p.i");


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

    $logo = '';
    if(!empty($row['logo']))
    $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
        ? $row['logo']
        : "{$protocol}://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}" ;


    $d[] = array(
        'num' => $row['id'],
        'name' => $row['name'],
        'stream_type' => 'movie',
        'stream_id' => (int)$row['id'],
        'stream_icon' => $logo,
        'rating' => $row['rating'],
        'rating_5based' => round( ($row['rating']*.5), 1 ),
        'added' => "$add_date",
        'category_id' => $row['category_id'],
        'category_ids' => $categories,
        'container_extension' => 'mp4',
        'custom_sid' => $row['id'],
        'direct_source' => '');

}



//IF NOT BUILDING AND SCRIPT REACH HERE MEAN THERE IS ANOTHER BUILDING..
if(!$building_cache) {
    echo json_encode($d);
    //exit;
} else {
    $a=1;
    //unlink("/home/aces/run/build_cache_vods_" . $cache_filename);
}


fastcgi_finish_request();

//$Cache->saveit(json_encode($d));

if($config['iptv']['api_cache']) {
    //SaveCache("/home/aces/cache/vods_streams_$cache_filename",json_encode($d));
    $Cache->saveit(json_encode($d));
}


//ob_end_flush();