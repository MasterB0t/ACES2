<?php

include 'main.php';

$categories = array();

$SQL_ADULTS = '';
if($ACCOUNT['adults'] == 0 ) $SQL_ADULTS = ' AND cat.adults = 0 ';

//$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
//        RIGHT JOIN iptv_stream_categories cat on cat.id = o.category_id
//        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS
//        GROUP BY cat.id ORDER BY cat.ordering
//");

//$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o
//        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id
//        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
//        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS
//        GROUP BY cat.id ORDER BY cat.ordering
//");

$building_cache = 0;
if($config['iptv']['api_cache']) {
    ignore_user_abort(true);

    $cache_filename = str_replace("'", '', $ACCOUNT['bouquets']);
    $cache_filename = explode(",",$cache_filename);
    sort($cache_filename);
    $cache_filename = implode("_",$cache_filename);
    if($ACCOUNT['adults'] ) $cache_filename .= "_wa";
    else $cache_filename .= "_na";

    $Cache = new Cache("/home/aces/cache/series_categories_$cache_filename", $config['iptv']['api_cache'],$config['iptv']['api_cache_compressed'] );

    if ($data = $Cache->get(true)) {
        echo $data;
        fastcgi_finish_request();
        if(!$Cache->isExpired()) {
            die;
        } else {

            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/build_cache_series_cats".$cache_filename)) {
                exit;
            }

            $building_cache = 1;
            //touch("/home/aces/run/build_cache_series_cats" . $cache_filename);


        }
    }

}


$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}')
        GROUP BY cat.id ORDER BY cat.s_ordering
");

while($row=mysqli_fetch_assoc($r)) { $categories[] = array('category_id' => $row['id'], 'category_name' => $row['name'], 'parent_id' => 0 ) ; }

if(!$building_cache)
    echo json_encode($categories);
else {
    $a=0;
    //unlink("/home/aces/run/build_cache_series_cats" . $cache_filename);
}


fastcgi_finish_request();

//$Cache->saveit(json_encode($categories));
if($config['iptv']['api_cache'])
    $Cache->saveit(json_encode($categories));
    //SaveCache("/home/aces/cache/series_categories_$cache_filename",json_encode($categories));

