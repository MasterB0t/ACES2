<?php

include 'main.php';

$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') ";
$sql_cat = '';

$limit = 0;
if (is_numeric($_GET['limit']) && $_GET['limit'] < 99) $limit = $_GET['limit'];


$page = (int)$_GET['page'];
if ($page > 0) $page--; //BECAUSE SQL START FROM 0 NOT 1


if ($ACCOUNT['adults'] == 0) $sql .= ' AND cat.adults = 0 ';

$rt = $DB->query("SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i 
RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
RIGHT JOIN iptv_app_favorites f ON f.vod_id = o.id AND f.profile_id = '{$ACCOUNT['profile_id']}'
WHERE o.status = 1  $sql
GROUP BY o.id ORDER BY p.i ");

$total = $rt->num_rows;
if($total == 0 ) {

    $json = array(
        "total_items" => 0,
        "current_page" =>  1,
        "total_pages" => 0,
        "items" => []
    );

    echo json_encode($json);

    exit;
}
if ($limit == 0) $limit = $total;

$total_pages = $total / $limit;
if (!is_int($total_pages)) $total_pages = (int)$total_pages + 1; //FIX DECIMAL
if ($page > $total / $limit) $page = ($total / $limit) - 1;
$sql_limit = "LIMIT " . ($limit * $page) . ",$limit";


$r = $DB->query("SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i 
RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id
RIGHT JOIN iptv_app_favorites f ON f.vod_id = o.id AND f.profile_id = '{$ACCOUNT['profile_id']}'
WHERE o.status = 1  $sql
GROUP BY o.id ORDER BY f.add_time DESC  $sql_limit ");

$items = [];
while ($row = mysqli_fetch_assoc($r)) {

    if (strtotime($row['add_date']) < 1) $add_date = 0;
    else $add_date = strtotime($row['add_date']);

    $logo = '';
    if ($row['logo']) $logo = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}";

    $items[] = array(
        'id' => $row['id'],
        'name' => $row['name'],
        'type' => 'movie',
        'logo' => $logo,
        'rating' => $row['rating'],
        'rating_5based' => round(($row['rating'] * .5), 1),
        'add_time' => "$add_date",
        'add_date' => $row['add_date'],
        'category_id' => $row['category_id'],
        'category_name' => $row['category_name'],
        'container_extension' => 'mp4',
    );

}

$json = array(
    "total_items" => $total,
    "current_page" => $page + 1,
    "total_pages" => $total_pages,
    "items" => $items
);


echo json_encode($json);