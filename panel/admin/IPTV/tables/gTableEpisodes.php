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

if(isset($_GET['filter_episode_id'])) {
    $filters[] = " e.id = " .(int)$_GET['filter_episode_id'];
}

if(isset($_GET['filter_series'])) {
    $filters[] = " s.series_id = ".(int)$_GET['filter_series'];
}

if(isset($_GET['filter_season'])) {
    $filters[] = " s.id = ".(int)$_GET['filter_season'];
}

if(isset($_GET['filter_server'])) {
    $filters[] = " srv.id = ".(int)$_GET['filter_server'];
}

if(isset($_GET['filter_status'])) {
    $filters[] = " e.status = ".(int)$_GET['filter_status'];
}


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " e.title LIKE '%$s%' AND $filter_sql ";
    $s_encode  = urlencode($_GET['search']['value']);
    $search .= " OR f.source_file LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR e.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY e.id $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY e.title $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY s.name $order_dir ";
else if($_GET['order'][0]['column'] == 5 ) $Order = " ORDER BY e.number $order_dir ";
else if($_GET['order'][0]['column'] == 6 ) $Order = " ORDER BY s.number $order_dir ";
else if($_GET['order'][0]['column'] == 7 ) $Order = " ORDER BY srv.id $order_dir ";
else if($_GET['order'][0]['column'] == 8 ) $Order = " ORDER BY e.rate $order_dir ";
else if($_GET['order'][0]['column'] == 9 ) $Order = " ORDER BY e.release_date $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT e.id FROM iptv_series_season_episodes e
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT e.id FROM iptv_series_season_episodes e
    RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id
    RIGHT JOIN iptv_video_files f ON f.episode_id = e.id
    JOIN iptv_servers srv ON srv.id = f.server_id
 $Where GROUP BY e.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT e.*,s.number as season_number, srv.name as server_name, o.name as series_name,s.series_id, 
       f.id as file_id,f.source_file,f.transcoding,f.resolution,f.video_codec,f.audio_codec 
    FROM iptv_series_season_episodes e
    RIGHT JOIN iptv_video_files f ON f.episode_id = e.id
    RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id
    RIGHT JOIN iptv_ondemand o ON o.id = s.series_id
    JOIN iptv_servers srv ON srv.id = f.server_id

$Where GROUP BY e.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formEpisode.php?id={$row['id']}&series_id={$row['series_id']}' title='Edit Episode' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Episode' onClick=\"MODAL('modals/mVideoMassRemove.php?remove_episodes=1&ids={$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

//    $links .= "<a href='/admin/IPTV/qplaylist.php?file_id={$row['file_id']}' title='Play in player' >
//        <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    if($row['transcoding'] == 'redirect')
        $links .= "<a  href='vlc://{$row['source_file']}' title='Play in player' >
            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";
    else
        $links .= "<a href='#!' onclick='PlayInPlayer(\"file\", {$row['file_id']} )' title='Play in player' >
                <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    $quality = '';
    //$r_file= $DB->query("SELECT source_file,transcoding,resolution,video_codec,audio_codec
    //            FROM iptv_video_files WHERE episode_id = '{$row['id']}'");
    //if($row_file = $r_file->fetch_assoc()) {

        $source_file = urldecode($row['source_file']);

        if(filter_var($row['source_file'],FILTER_VALIDATE_URL)) {
            //$t = $row['transcoding'] == 'redirect' ? "Redirect:" : "Downloaded From: ";
            $t = match($row['transcoding']) {
                'redirect' => "Redirect: ",
                'stream' => "Streaming From: ",
                default => "Downloaded From: "
            };

        }
        $source_file =  "<br/>$t {$source_file}" ;

        $c = '';
        if(!empty($row['resolution']) ) {
            $res = explode('x',$row['resolution']);
            $c = "({$row['video_codec']}/{$row['audio_codec']})";
            if($res[0] >= 1920 && $res[1] >= 1080 ) $quality = "FHD ({$row['resolution']})";
            else if($res[0] >= 1280 && $res[1] >= 720 ) $quality = "HD ({$row['resolution']})";
            else $quality = "SD ({$row['resolution']})";

            $quality .= " <br/>$c";
        }

   // }


    $logo = '';
    if($row['logo']) {
        $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
            ? $logo = "<img width=80 height='45' src='{$row['logo']}'/>"
            : "<img width=80 src='".HOST."/logos/{$row['logo']}'/>";
    }

    $title = "";
    switch($row['status']) {
        case 0:
            if($row['percent'] < 1 )
                $title = "<b class='text-warning'>{$row['title']} (Downloading) $source_file</b>";
            else
                $title = "<b class='text-warning'>{$row['title']} (Processing {$row['percent']}%) $source_file</b>";
            break;
        case 1:
            $title = "<b class='text-success'>{$row['title']} $source_file</b>";
            break;

        case 2:
            $title = "<b class='text-warning'>{$row['title']} (Moving {$row['percent']}%) $source_file</b>";
            break;

        case -1:
            $title = "<b class='text-danger'>Stopped {$row['title']} $source_file</b>";
            break;

        case -2:
            $title = "<b class='text-danger'>Fail {$row['title']} $source_file</b>";
    }



    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $logo,
        $title,
        $row['series_name'],
        $quality,
        $row['number'],
        $row['season_number'],
        $row['server_name'],
        $row['rate'],
        $row['release_date'],
        $links
    );

}

echo json_encode($json);