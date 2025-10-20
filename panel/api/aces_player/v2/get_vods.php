<?php

unset($_GET['action']);
require_once ('index.php');


$sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') ";

$addr = $_SERVER['HTTP_HOST'];
$ADDR = isset($_SERVER['HTTPS'] ) ? "https://".$addr : 'http://'.$addr;

$limit = 10;
if (is_numeric($_GET['limit']) && $_GET['limit'] < 101)
    $limit = $_GET['limit'];


if (isset($_GET['type'])) {

    if ($_GET['type'] == 'movies') $sql .= "AND o.type = 'movies' ";
    else if ($_GET['type'] == 'series') $sql .= "AND o.type = 'series' ";

}

$order_by = "p.i DESC";
if ($_GET['sort_by'] == 'recent') $order_by = " o.id DESC ";


$page = (int)$_GET['page'];
if ($page > 0) $page--; //BECAUSE SQL START FROM 0 NOT 1

if ($ACCOUNT['adults'] == 0)
    $sql .= ' AND cat.adults = 0 ';


$sql_cat = '';
//if(is_numeric($_GET['category_id']))
//    $sql_cat .= " AND i.category_id = '{$_GET['category_id']}' ";


if (is_numeric($_GET['category_id'])) {

    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id 
FROM iptv_in_category i
RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id AND i.category_id = {$_GET['category_id']}
WHERE o.status = 1  $sql 
GROUP BY o.id ORDER BY p.i DESC";


} else if ($_GET['category_id'] == 'favorites') {
    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id
FROM iptv_app_favorites f
RIGHT JOIN iptv_ondemand o ON o.id = f.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}'  $sql
GROUP BY o.id ORDER BY f.add_time  DESC";

} else if ($_GET['category_id'] == 'watching') {

    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id FROM iptv_app_watching w
RIGHT JOIN iptv_ondemand o ON o.id = w.vod_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
WHERE o.status = 1 AND w.profile_id = '{$ACCOUNT['profile_id']}' $sql
GROUP BY o.id ORDER BY w.add_time DESC";


} else if ($_GET['category_id'] == 'new_episodes') {

    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id FROM iptv_app_new_episodes f
RIGHT JOIN iptv_ondemand o ON o.id = f.series_id
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}'  $sql
GROUP BY o.id ORDER BY f.update_time DESC";

} else if($_GET['category_id'] == 'new_release') {

    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id FROM iptv_ondemand o 
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id
WHERE o.status = 1  $sql AND release_date > '2020-01-01'
GROUP BY o.id ORDER BY o.release_date,o.id DESC";

} else {

    //RECENTs
    $query = "SELECT o.id,o.name,o.type,o.genre1,o.genre2,o.genre3,o.rating,o.release_date,o.age_rating,age_rating_i,o.release_date,
       o.trailer_link,o.about,o.cast,o.director,o.logo,o.back_logo,o.add_date, o.logo_name,
       cat.name as category_name, cat.id as category_id FROM iptv_ondemand o 
RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id
WHERE o.status = 1  $sql
GROUP BY o.id ORDER BY o.id DESC";



}

$rt = $DB->query($query);


if ($rt->num_rows < 1) {

    $json = array(
        "total_items" => 0,
        "current_page" => 1,
        "total_pages" => 0,
        "vods" => []
    );

    echo json_encode($json);
    exit;

}

$total = $rt->num_rows;
if ($limit == 0) $limit = $total;

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


$r = $DB->query($query . "  $sql_limit ");


$items = [];
while ($row = $r->fetch_assoc()) {

    $genre_str = $row['genre1'];
    if($row['genre2']) $genre_str .= " / {$row['genre2']}";
    if($row['genre3']) $genre_str .= " / {$row['genre3']}";

    $genre = array( $row['genre1'], $row['genre2'], $row['genre3'] );

    $cast = array_slice(explode(',',trim($row['cast']) ), 0 , 5 );

    if (strtotime($row['add_date']) < 1) $add_date = 0;
    else $add_date = strtotime($row['add_date']);


    if(filter_var($row['logo'], FILTER_VALIDATE_URL)) $logo = $row['logo'];
    else $logo = $row['logo'] ? "$HOST/logos/{$row['logo']}" : null;

    if(filter_var($row['back_logo'], FILTER_VALIDATE_URL)) $back_logo = $row['back_logo'];
    else $back_logo = $row['back_logo'] ? "$HOST/logos/{$row['back_logo']}" : null;

    $y_trailer = ($row['trailer_link']) ?  str_replace('https://www.youtube.com/watch?v=','',$row['trailer_link']) : '';

    $is_series = $row['type'] == 'series' ? true : false ;

    $r_fav = $DB->query("SELECT vod_id FROM iptv_app_favorites WHERE profile_id = '{$ACCOUNT['profile_id']}' AND vod_id = {$row['id']} ");
    $is_fav = (bool)$r_fav->fetch_assoc();

    $release_year = (int)date("Y",strtotime($row['release_date']));

    $file_arr = array('file_id' => null, 'video_url' => null, 'resume_position' => null);
    if(!$is_series) {
        $r_file = $DB->query("SELECT id as file_id, container  FROM iptv_video_files WHERE movie_id = '{$row['id']}'");

        if($file_row = $r_file->fetch_assoc()) {

            $r_resume = $DB->query("SELECT resume_position FROM iptv_app_video_position 
                           WHERE file_id = '{$file_row['file_id']}' AND profile_id = '{$ACCOUNT['profile_id']}' "  );
            $resume_pos = $r_resume->fetch_assoc()['resume_position'];

            $file_arr = array('file_id' => $file_row['file_id'],
                'video_url' => $ADDR . "/movie/$USERNAME/$PASSWORD/{$file_row['file_id']}.{$file_row['container']}",
                'resume_position' => $resume_pos,
            );
        }

    }
    $items[] = array_merge( array(
        'id' => $row['id'],
        'is_series' => $is_series,
        'title' => $row['name'],
        'type' => $row['type'],
        'cover' => $logo,
        'logo_name' => empty($row['logo_name']) ? null : $row['logo_name'],
        'back_cover' => $back_logo,
        'genres_str' => $genre_str,
        'rating' => $row['rating'],
        'rating_5based' => round(($row['rating'] * .5), 1),
        'age_rating' => $row['age_rating'],
        'age_rating_int' => $row['age_rating_i'],
        'add_date' => $row['add_date'],
        'release_date' => $row['release_date'],
        'release_year' => $release_year,
        'category_id' => $row['category_id'],
        'category_name' => $row['category_name'],
        'is_favorite' => $is_fav,
        'plot' => $row['about'],
        'cast' => $cast,
        'cast_str' => implode(" /",$cast),
        'director' => $row['director'],
        'youtube_trailer' => $y_trailer,
    ), $file_arr);

}


$json = array(
    "total_items" => $total,
    "current_page" => $page + 1,
    "total_pages" => $total_pages,
    "limit_per_page" => $limit,
    "vods" => $items
);


echo json_encode($json);
