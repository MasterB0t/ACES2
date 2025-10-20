<?php

unset($_GET['action']);
require_once ('index.php');

$limit = (int)$_GET['limit'] > 0 && (int)$_GET['limit'] < 100 ? (int)$_GET['limit'] : 10;
if($page = (int)$_GET['page'])
    $page--; //BECAUSE SQL START FROM 0 NOT 1


$search = $DB->escape_string($_GET['search']);
$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') AND o.name like '%$search%' ";

$rt=$DB->query("SELECT o.id FROM iptv_ondemand o
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
WHERE o.status = 1  $sql
GROUP BY o.id ORDER BY o.name  ");

$total = $rt->num_rows;
$total_pages = $total/$limit;
if(!is_int($total_pages)) $total_pages = (int)$total_pages + 1 ; //FIX DECIMAL
if($page > $total/$limit) $page = (int)($total/$limit)-1;
$sql_limit = "LIMIT ".($limit*$page).",$limit";


$r=$DB->query("SELECT o.*,cat.name as category_name, o.id as category_id FROM iptv_ondemand o
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
WHERE o.status = 1  $sql
GROUP BY o.id ORDER BY o.name  $sql_limit");



if($r->num_rows < 1) {
    echo json_encode(array(
        "total_items" => $total,
        "current_page" => $page + 1,
        "total_pages" => $total_pages,
        "limit_per_page" => $limit,
        "vods" => []
    ));
    exit;
}

$items = [];
while ($row = $r->fetch_assoc()) {

    $genre_str = $row['genre1'];
    if($row['genre2']) $genre_str .= " / {$row['genre2']}";
    if($row['genre3']) $genre_str .= " / {$row['genre3']}";

    $genre = array( $row['genre1'], $row['genre2'], $row['genre3'] );

    $cast = array_slice(explode(',',trim($row['cast']) ), 0 , 5 );

    if (strtotime($row['add_date']) < 1) $add_date = 0;
    else $add_date = strtotime($row['add_date']);


    if(!filter_var($row['logo'], FILTER_VALIDATE_URL))
        $row['logo'] = $row['logo'] ? "$HOST/logos/{$row['logo']}" : null;

    if(!filter_var($row['back_logo'], FILTER_VALIDATE_URL))
        $row['back_logo'] = $row['back_logo'] ? "$HOSTY/logos/{$row['back_logo']}" : null;

    $y_trailer = ($row['trailer_link']) ?  str_replace('https://www.youtube.com/watch?v=','',$row['trailer_link']) : '';

    $is_series = $row['type'] == 'series';

    $r_fav = $DB->query("SELECT vod_id FROM iptv_app_favorites WHERE profile_id = '{$ACCOUNT['profile_id']}' AND vod_id = {$row['id']} ");
    $is_fav = (bool)$r_fav->fetch_assoc();

    $items[] = array(
        'id' => $row['id'],
        'is_series' => $is_series,
        'title' => $row['name'],
        'type' => $row['type'],
        'cover' => $row['logo'],
        'back_cover' => $row['back_logo'],
        'genres_str' => $genre_str,
        'rating' => $row['rating'],
        'rating_5based' => round(($row['rating'] * .5), 1),
        'rating_mpaa' => $row['age_rating'],
        'add_date' => $row['add_date'],
        'release_date' => $row['release_date'],
        'category_id' => $row['category_id'],
        'category_name' => $row['category_name'],
        'is_favorite' => $is_fav,
        'plot' => $row['about'],
        'cast' => $cast,
        'director' => $row['director'],
        'youtube_trailer' => $y_trailer,
    );

}


$json = array(
    "total_items" => $total,
    "current_page" => $page + 1,
    "total_pages" => $total_pages,
    "limit_per_page" => $limit,
    "vods" => $items
);

echo json_encode($json);