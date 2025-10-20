<?php
header('Content-Type: application/json');
header('Connection: close');


$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();


if (!adminIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD) ) {
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

if(isset($_GET['filter_server_id']))
    $filters[] = " f.server_id = '".(int)$_GET['filter_server_id']."' ";

if(isset($_GET['filter_video_type'])) {
    $filters[] = match($_GET['filter_video_type']) {
        'episode' => " f.episode_id != 0 ",
        'movie' => "f.movie_id != 0 ",
        default => ''
    };
}




$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " r.name LIKE '%$s%' AND $filter_sql ";
    
    if(is_numeric($_GET['search']['value']))
        $search .= " OR r.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir']
    : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY r.file_id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY r.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT r.file_id FROM iptv_video_reports r
    INNER JOIN iptv_video_files f ON f.id = r.file_id
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT r.file_id FROM iptv_video_reports r
    INNER JOIN iptv_video_files f ON f.id = r.file_id
 $Where GROUP BY r.file_id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT r.*, f.movie_id,f.episode_id,f.server_id,f.source_file,s.name as server_name
    FROM iptv_video_reports r
    INNER JOIN iptv_video_files f ON f.id = r.file_id
    LEFT JOIN iptv_servers s ON s.id = f.server_id


$Where  $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='#!' title='Remove' onClick=\"MODAL( 'modals/mVideoReportRemove.php?ids={$row['file_id']}' ) ;\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";


    $links .= "<a href='/admin/IPTV/qplaylist.php?file_id={$row['file_id']}' title='Play in player' >
        <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    $server_name = is_null($row['server_name'])
        ? "[REMOVED] #{$row['server_id']}"
        : $row['server_name'];


    if($row['movie_id']> 0) {

        $video_id = "<a href='/admin/IPTV/videos.php?filter_video_id={$row['movie_id']}' title='{$row['movie_id']}' >Movie #{$row['movie_id']}</a>";

        $links .= "<a href='/admin/IPTV/form/formVideo.php?id={$row['movie_id']}' title='Edit Movie' >
        <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";

        $r2=$DB->query("SELECT name,logo FROM iptv_ondemand WHERE id = '{$row['movie_id']}'");
        $row2 = $r2->fetch_assoc();
        $name = $row2['name'];

        if($limit < 100 )
            $img = filter_var($row2['logo'], FILTER_VALIDATE_URL)
                ? "<img width='80' src='{$row2['logo']}'/>"
                : "<img width='80' src='".HOST."/logos/{$row2['logo']}'/>";

    } else {


        $video_id = "<a href='/admin/IPTV/episodes.php?filter_episode_id={$row['episode_id']}' title='{$row['movie_id']}' >Episode #{$row['episode_id']}</a>";

        $links .= "<a href='/admin/IPTV/form/formEpisode.php?id={$row['episode_id']}' title='Edit Episode' >
        <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";

        $r2=$DB->query("SELECT e.title,e.number as episode_number, s.number as season_number, o.name as series_name ,e.logo 
            FROM iptv_series_season_episodes e 
            RIGHT JOIN iptv_series_seasons s ON s.id = e.season_id
            RIGHT JOIN iptv_ondemand o on o.id = s.series_id
            WHERE e.id = '{$row['episode_id']}'");

        $row2 = $r2->fetch_assoc();
        $name = "Series {$row2['series_name']} E{$row2['episode_number']} S{$row2['season_number']} - {$row2['title']}'";

        if($limit < 100 && !empty($row2['logo']) )
            $img = filter_var($row2['logo'], FILTER_VALIDATE_URL)
                ? "<img width='120' src='{$row2['logo']}'/>"
                : "<img width='120' src='".HOST."/logos/{$row2['logo']}'/>";

    }

    $source_file = filter_var($row['source_file'], FILTER_VALIDATE_URL)
        ? $row['source_file']
        : urldecode($row['source_file']);

    $json['data'][] = array(
        'DT_RowId' => $row['file_id'],
        $row['file_id'],
        "$name<br>$img",
        \ACES2\IPTV\VideoReports::getStatus($row['type']),
        $video_id,
        $server_name,
        $source_file,
        $links
    );

}

echo json_encode($json);