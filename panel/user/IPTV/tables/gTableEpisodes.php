<?php
header('Content-Type: application/json');
header('Connection: close');


$DB = new \ACES2\DB();


if (!$UserID=userIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

}

$USER = new \ACES2\IPTV\Reseller2($UserID);
if (!$USER->allow_vod_list ) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}


$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";

if($series_id=(int)$_GET['filter_series'])
    $filters[] = " s.series_id = $series_id ";


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " e.name LIKE '%$s%' AND $filter_sql ";
    
    if(is_numeric($_GET['search']['value']))
        $search .= " OR e.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY e.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY e.title $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY o.name $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY e.number $order_dir ";
else if($_GET['order'][0]['column'] == 4 ) $Order = " ORDER BY s.number $order_dir ";
else if($_GET['order'][0]['column'] == 5 ) $Order = " ORDER BY e.rate $order_dir ";
else if($_GET['order'][0]['column'] == 6 ) $Order = " ORDER BY e.release_date $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT e.id FROM iptv_series_season_episodes e
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT e.id FROM iptv_series_season_episodes e
    RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id
    RIGHT JOIN iptv_video_files f ON f.episode_id = e.id
 $Where GROUP BY e.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT e.*, s.number as season_number, o.name as series_name,s.series_id

    FROM iptv_series_season_episodes e
     JOIN iptv_series_seasons s ON e.season_id = s.id
     JOIN iptv_ondemand o ON o.id = s.series_id

$Where GROUP BY e.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $quality = '';
    $r_file= $DB->query("SELECT source_file,transcoding,resolution,video_codec,audio_codec 
                FROM iptv_video_files WHERE episode_id = '{$row['id']}'");
    if($row_file = $r_file->fetch_assoc()) {

        $source_file = urldecode($row_file['source_file']);

        if(filter_var($row_file['source_file'],FILTER_VALIDATE_URL)) {
            $t = $row_file['transcoding'] == 'redirect' ? "Redirect:" : "Downloaded From: ";
        }
        $source_file =  "<br/>$t {$source_file}" ;

        $c = '';
        if(!empty($row_file['resolution']) ) {
            $res = explode('x',$row_file['resolution']);
            $c = "({$row_file['video_codec']}/{$row_file['audio_codec']})";
            if($res[0] >= 1920 && $res[1] >= 1080 ) $quality = "FHD ({$row_file['resolution']})";
            else if($res[0] >= 1280 && $res[1] >= 720 ) $quality = "HD ({$row_file['resolution']})";
            else $quality = "SD ({$row_file['resolution']})";

            $quality .= " <br/>$c";
        }

    }

    $logo = '';
    if($row['logo']) {
        $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
            ? $logo = "<img width=80 src='{$row['logo']}'/>"
            : "<img width=80 src='".HOST."/logos/{$row['logo']}'/>";
    }
    
    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['title']."<br>{$logo}",
        $row['series_name'],
        $row['number'],
        $row['season_number'],
        $row['rate'],
        $row['release_date'],
    );

}

echo json_encode($json);