<?php

unset($_GET['action']);
require_once 'index.php';

$limit = (int)$_GET['limit'] < 100 ? (int)$_GET['limit'] : 10;

$page = (int)$_GET['page'];
if ($page > 0) $page--;

if($_GET['type'] == "home") {

    $cats[] = array('id'=> 'recent', 'name' => "Recently Added");
    $cats[] = array('id'=> 'new_release', 'name' => "New Release");
    $total = 2;

    $r=$DB->query("SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,age_rating,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date,
       cat.name as category_name, cat.id as category_id
FROM iptv_app_watching f
RIGHT JOIN iptv_ondemand o ON o.id = f.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id
WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}' AND cat.adults = 0
GROUP BY o.id LIMIT 1");
    if($r->num_rows>0) {
        $total++;
        $cats[] = array('id'=> 'watching', 'name' => "Continue Watching");
    }

    $r=$DB->query("SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,age_rating,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date,
       cat.name as category_name, cat.id as category_id
FROM iptv_app_favorites f
RIGHT JOIN iptv_ondemand o ON o.id = f.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id
WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}' AND cat.adults = 0
GROUP BY o.id LIMIT 1 ");
    if($r->num_rows>0) {
        $total++;
        $cats[] = array('id' => 'favorites', 'name' => "favorites");
    }


    $r=$DB->query("SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,age_rating,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date,
       cat.name as category_name, cat.id as category_id FROM iptv_app_new_episodes f
RIGHT JOIN iptv_ondemand o ON o.id = f.series_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id
WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}' AND cat.adults = 0
GROUP BY o.id LIMIT 1");
    if($r->num_rows>0) {
        $total++;
        $cats[] = array('id' => 'new_episodes', 'name' => "New Episodes");
    }

    echo json_encode(
        array(
            "total_items" => $total,
            "current_page" => 1,
            "total_pages" => 1,
            "categories" => $cats
        )
    );

    exit;

}



$SQL_TYPE =  $_GET['type'] == 'series' ? " AND o.type = 'series' " : " AND o.type = 'movies' ";
$ORDERING =  $_GET['type'] == 'series' ? 'cat.s_ordering' : 'cat.m_ordering';
//TODO FILTER ADULT BASED ON ACCOUNT.
$SQL_ADULT = $ACCOUNT['adults'] == 0 ? "  AND cat.adults = 0 " : "AND cat.adults = 1";


$r_total=$DB->query("SELECT cat.id, cat.name FROM iptv_ondemand o
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 $SQL_TYPE  AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}')
        GROUP BY cat.id ORDER BY $ORDERING 
");



$total = $r_total->num_rows;

$total_pages = $total / $limit;
if ($total_pages < 1) {
    $sql_limit = "LIMIT 0,$limit";
    $total_pages = 1;
    $page = 0;
} else {
    if (!is_int($total_pages)) $total_pages = (int)$total_pages + 1; //FIX DECIMAL
    if ($page > $total / $limit) $page = ($total / $limit) - 1;
    $sql_limit = "LIMIT " . ($limit * $page) . ",$limit";
}



$r=$DB->query("SELECT cat.id, cat.name FROM iptv_ondemand o
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 $SQL_TYPE  AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}')
        GROUP BY cat.id ORDER BY $ORDERING $sql_limit
");

$categories = [];
while($row=$r->fetch_assoc()) {
    $categories[] = $row;
}


echo json_encode(
    array(
        "total_items" => $total,
        "current_page" => $page + 1,
        "total_pages" => $total_pages,
        "categories" => $categories
    )
);

exit;