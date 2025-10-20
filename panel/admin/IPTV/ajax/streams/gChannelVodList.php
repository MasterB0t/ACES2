<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}


session_write_close();
$json['data']=array();

$DB = new \ACES2\DB();
$search = $DB->escString($_POST['search']);
if(strlen($search) <  3 ) { echo json_encode($json); exit; }


$r=$DB->query("SELECT id,name,type FROM iptv_ondemand WHERE name like '%$search%' AND status = 1 ");
while($row=$r->fetch_assoc()) {

    $categories=[];
    $rc=$DB->query("SELECT c.name FROM iptv_stream_categories c "
        . "RIGHT JOIN iptv_in_category i ON i.category_id = c.id "
        . " WHERE i.vod_id = {$row['id']} ");

    while($crow=$rc->fetch_assoc()) $categories[] = $crow['name'];

    $in_cats = "in (".implode(", ", $categories)." ) ";

    if($row['type'] == 'movies') {
        $rf=$DB->query("SELECT id FROM iptv_video_files WHERE movie_id = {$row['id']} ");
        if($frow=$rf->fetch_assoc()) {}
            $json['data'][] = array('name'=>"$in_cats [MOVIE] {$row['name']}",'id'=>$frow['id']);
    } else {
        $rs=$DB->query("SELECT id,number FROM iptv_series_seasons WHERE series_id = '{$row['id']}' ORDER BY number ");
        while($srow=$rs->fetch_assoc()) {
            $episodes=array();
            $re=$DB->query("SELECT e.id,e.number,f.id as file_id FROM iptv_series_season_episodes e INNER JOIN iptv_video_files f ON f.episode_id = e.id 
                                     WHERE e.season_id = '{$srow['id']}' AND e.status = 1 order by e.number ");
            while($erow=$re->fetch_assoc()) { $episodes[]=$erow['file_id'];  }
            if(count($episodes)>0) $json['data'][] = array('name'=>"{$in_cats} [SERIES] {$row['name']} SEASON {$srow['number']}",'id'=>implode(',',$episodes) );
        }
    }
}


echo json_encode($json); exit;