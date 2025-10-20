<?php

unset($_GET['action']);
require_once 'index.php';

$series_id = (int)$_GET['series_id'];
if(!$series_id)
    setAjaxError("",\ACES\HttpStatusCode::BAD_REQUEST);

$r=$DB->query("SELECT s.id,s.number,s.logo as image_url,s.air_date,s.overview,s.air_date,s.tmdb_id FROM iptv_series_seasons s 
                RIGHT JOIN iptv_ondemand_in_bouquet b ON b.video_id = $series_id                                                       
                WHERE s.series_id = $series_id AND b.bouquet_id IN ('{$ACCOUNT['bouquets']}') ORDER BY s.number ");

$data=[];
while($row=$r->fetch_assoc()) {

    if(!filter_var($row['image_url'], FILTER_VALIDATE_URL))
        $row['image_url'] = $row['image_url'] ? $HOST."/logos/{$row['image_url']}" : null;

    $r_episodes = $DB->query("SELECT id FROM iptv_series_season_episodes WHERE season_id = {$row['id']}");
    if($r_episodes->fetch_assoc()) {
        $row['episodes_count'] = $r_episodes->num_rows;
    }

    $seasons[] = $row;
}


echo json_encode(array(
    "total_seasons" => $r->num_rows,
    "seasons" => $seasons
));