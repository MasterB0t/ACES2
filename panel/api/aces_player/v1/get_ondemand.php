<?php

include 'main.php';

$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') ";
$sql_cat = '';

$limit = 0;
if(is_numeric($_GET['limit']) && $_GET['limit'] < 99 ) $limit = $_GET['limit'];

if(is_numeric($_GET['category_id']) )
    $sql_cat .= " AND i.category_id = '{$_GET['category_id']}' ";



if(isset($_GET['type'])) {

    if($_GET['type'] == 'movies' ) $sql .= "AND o.type = 'movies' ";
    else if($_GET['type'] == 'series' ) $sql .= "AND o.type = 'series' ";

}

$order_by = "p.i ASC";
if($_GET['sort_by'] == 'recent' ) $order_by = " o.id DESC ";


$page = (int)$_GET['page'];
if($page>0) $page--; //BECAUSE SQL START FROM 0 NOT 1


if($ACCOUNT['adults'] == 0 )  $sql .= ' AND cat.adults = 0 ';

$rt=$DB->query("SELECT o.id FROM iptv_in_category i
RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
WHERE o.status = 1  $sql
GROUP BY i.i ");

if($rt->num_rows < 1 ) {

    $json = array(
        "total_items" => 0,
        "current_page" => 1 ,
        "total_pages" => 0,
        "items" => []
    );

    echo json_encode($json);
    exit;

}

$total = $rt->num_rows;
if($limit == 0 ) $limit = $total;

$total_pages = $total/$limit;
if($total_pages < 1 ) { $sql_limit = "LIMIT 0,$limit"; $total_pages =1; $page = 0; }
else {
    if (!is_int($total_pages)) $total_pages = (int)$total_pages + 1; //FIX DECIMAL
    if ($page > $total / $limit) $page = ($total / $limit) - 1;
    $sql_limit = "LIMIT " . ($limit * $page) . ",$limit";
}


$r=$DB->query("SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i 
INNER JOIN iptv_ondemand o ON o.id = i.vod_id
INNER JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
INNER JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
WHERE o.status = 1  $sql
GROUP BY i.i ORDER BY $order_by  $sql_limit ");



$items = [];
while($row=mysqli_fetch_assoc($r)) {

    if( strtotime($row['add_date']) < 1 ) $add_date = 0;
    else $add_date = strtotime($row['add_date']);

    $logo = '';
    if($row['logo']) $logo = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}";

    $items[] = array(
        'id' => $row['id'],
        'name' => $row['name'],
        'type' => 'movie',
        'logo' => $logo,
        'rating' => $row['rating'],
        'rating_5based' => round( ($row['rating']*.5), 1 ),
        'add_time' => "$add_date",
        'add_date' => $row['add_date'],
        'category_id' => $row['category_id'],
        'category_name' => $row['category_name'],
        'container_extension' => 'mp4',
    );

}


$json = array(
    "total_items" => $total,
    "current_page" => $page + 1 ,
    "total_pages" => $total_pages,
    "items" => $items
);


echo json_encode($json);


