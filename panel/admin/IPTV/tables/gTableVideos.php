<?php

header('Content-Type: application/json');
header('Connection: close');

use \ACES2\IPTV\Account;

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

$duplicate = false;
$filters[] = " 1 = 1 ";

if(isset($_GET['filter_video_id'])) {
    $filters[] = " v.id = " .(int)$_GET['filter_video_id'];
}

if(isset($_GET['filter_type'])) {
    if($_GET['filter_type'] == 'movies') $filters[] = "v.type = 'movies'";
    else if($_GET['filter_type'] == 'series') $filters[] = "v.type = 'series'";
    else if($_GET['filter_type'] == 'movie_failed') $filters[] = "v.type = 'movies' AND v.status = -2 ";
    else if($_GET['filter_type'] == 'no_tmdb') $filters[] = "v.tmdb_id = 0";
    else if($_GET['filter_type'] == 'duplicated') {$duplicate = true ;  $filters[] = " dup.id <> v.id "; }
    else if($_GET['filter_type'] == 'episodes_failed')  {
        $filter_join_episode_failed = " INNER JOIN iptv_series_seasons s ON s.series_id = v.id 
        INNER JOIN iptv_series_season_episodes e ON e.status = -2 AND e.season_id = s.id "; }
}

if(isset($_GET['filter_category'] )) {
    $cat_id = (int)$_GET['filter_category'];
    $filter_cat_join = "INNER JOIN iptv_in_category incat ON 
	incat.category_id = $cat_id AND v.id = incat.vod_id " ;
}

if(isset($_GET['filter_server'])) {
    $server_id = (int)$_GET['filter_server'];
    $filter_server_join = "INNER JOIN iptv_video_files f ON f.movie_id = v.id AND f.server_id = $server_id ";
}

$filter_bouquets_join = '';
if(isset($_GET['filter_bouquets'])) {
    $filter_bouquets_join = "INNER JOIN iptv_ondemand_in_bouquet bo ON bo.video_id = v.id AND bo.bouquet_id = " . (int)$_GET['filter_bouquets']. " ";
}


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " v.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR v.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY v.id $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY v.name $order_dir ";
else if($_GET['order'][0]['column'] == 8 ) $Order = " ORDER BY v.add_date $order_dir ";
else if($_GET['order'][0]['column'] == 9 ) $Order = " ORDER BY v.release_date $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT id FROM iptv_ondemand");
$json['recordsTotal'] = $r->num_rows;


if($duplicate)
    $r_filtered=$DB->query("SELECT DISTINCT v.id "
        . " FROM iptv_ondemand v "
        . " INNER JOIN iptv_ondemand dup ON dup.name = v.name "
        . " LEFT JOIN iptv_stream_categories cat ON cat.id = v.category_id "
        . @$filter_cat_join
        . @$filter_server_join
        . @$filter_bouquets_join
        . " $Where  GROUP BY v.id  ");

else
    $r_filtered=$DB->query("SELECT v.id "
        . " FROM iptv_ondemand v  "
        . " LEFT JOIN iptv_stream_categories cat ON cat.id = v.category_id  "
        . @$filter_cat_join
        . @$filter_server_join
        . @$filter_join_episode_failed
        . @$filter_bouquets_join
        . " $Where  GROUP BY v.id  ");

$json['recordsFiltered'] = $r_filtered->num_rows;


if($duplicate)
    $r=$DB->query("SELECT DISTINCT v.*,cat.name as category_name "
        . " FROM iptv_ondemand v  "
        . " INNER JOIN iptv_ondemand dup ON dup.name = v.name "
        . " INNER JOIN iptv_stream_categories cat ON cat.id = v.category_id  "
        . @$filter_cat_join
        . @$filter_join_episode_failed
        . @$filter_bouquets_join
        . " $Where GROUP BY v.id  $Order LIMIT $start,$limit ");


else
    $r=$DB->query("SELECT v.*,cat.name as category_name "
        . " FROM iptv_ondemand v  "
        . " LEFT JOIN iptv_stream_categories cat ON cat.id = v.category_id   "
        . @$filter_join_episode_failed
        . @$filter_server_join
        . @$filter_cat_join
        . @$filter_join_episode_failed
        . @$filter_bouquets_join
        . "$Where  GROUP BY v.id  $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formVideo.php?id={$row['id']}' title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove' onClick=\"MODAL('modals/mVideoMassRemove.php?remove_videos=1&ids={$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";
    if($row['type'] == 'series')
        $links .= "<a href='episodes.php?filter_series={$row['id']}' title='Episodes' > 
            <i style='margin:5px;' class='fa fa-list fa-lg'></i> </a>";

    else {
        $r_file = $DB->query("SELECT id,transcoding,source_file FROM iptv_video_files WHERE movie_id = '{$row['id']}'");
        if($row_file = $r_file->fetch_assoc()) {
//            $links .= "<a href='/admin/IPTV/qplaylist.php?file_id=$f_id' title='Play in player' >
//            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

            if($row_file['transcoding'] != 'redirect')
                $links .= "<a href='#!' onclick='PlayInPlayer(\"file\", {$row_file['id']} )' title='Play in player' >
                <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";
            else
                $links .= "<a href='vlc://{$row_file['source_file']}'  title='Play in player' >
                <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";
        }
    }

    $categories=array();
    $rcats = $DB->query("SELECT c.id,c.name FROM iptv_in_category inc "
        . " JOIN iptv_stream_categories c ON inc.category_id = c.id "
        . " WHERE inc.vod_id = {$row['id']} LIMIT 5");
    while($cat_rows=$rcats->fetch_array())  $categories[] = $cat_rows['name'];
    if(count($categories) == 0 ) $categories=array('[NONE]');

    if($row['type'] == 'series')  {

        $server = "N/A";

        $r3 = $DB->query("SELECT v.id, COUNT(e.id) as episodes,e.status as episode_status, COUNT(ss.id) as seasons,
       COUNT(DISTINCT CASE WHEN e.status = 0 THEN e.id END) AS episodes_processing

            FROM iptv_ondemand v 
            LEFT JOIN iptv_series_seasons ss ON v.id = ss.series_id and v.type = 'series' 
            LEFT JOIN iptv_series_season_episodes e ON e.season_id = ss.id
            WHERE v.id = '{$row['id']}' 
        ");

        $row2 = $r3->fetch_assoc();

        if($row2['episodes_processing']) {
            $status = "<p> <b style='color:orange'>{$row['name']}  (Processing {$row2['episodes_processing']} ) {$row['episodes']} </b></p> ";
        } else $status = " <p> <b style='color:green'> {$row['name']} ( {$row2['episodes']} Episodes )  </b></p> ";

    } else {

        $file_source = '';

        $r3 = $DB->query(" SELECT v.id,s.name as server_name, f.resolution, f.frames, f.bitrate, f.audio_codec,
        f.video_codec, f.source_file, f.transcoding, f.id as video_id, COUNT(pc.id) as play_count 
 
                FROM iptv_ondemand v  
                LEFT JOIN iptv_video_files f ON f.movie_id = v.id 
                LEFT JOIN iptv_servers s ON f.server_id = s.id
                LEFT JOIN iptv_video_play_count pc ON pc.video_file_id = f.id
                WHERE v.id = '{$row['id']}'
                
        ");

        $row2=$r3->fetch_assoc();
        $server = $row2['server_name'];
        $source_file = '';

        if($ADMIN->hasPermission()){
            if(filter_var($row2['source_file'],FILTER_VALIDATE_URL)) {

                $t = match($row2['transcoding']) {
                    'redirect' => "Redirect: ",
                    'stream' => "Streaming From: ",
                    default => "Downloaded From: "
                };

                $source_file = "<br />$t " .urldecode($row2['source_file']);

            } else $source_file = "<br/>" . urldecode($row2['source_file']);
            $source_file = iconv(mb_detect_encoding($source_file, mb_detect_order(), true), "UTF-8", $source_file);
        }

        if($row['status'] == 0 ) {
            if($row['percent'] < 1 ) $status = "<p><b style='color:orange'> {$row['name']} (Downloading)$source_file</b></p>";
            else $status = "<p>  <b style='color:orange'> {$row['name']}(Processing {$row['percent']}%)$source_file</b></p>";
        } else if($row['status'] == 2 )  $status = "<p>{$row['name']} <b style='color:orange'>(Moving {$row['percent']}%)$source_file</b></p>";

        else if( $row['status'] == -1 ) $status = "<b style='color:red'> {$row['name']}Stopped$source_file</b>";
        else if($row['status'] == -2 ) $status = "<b style='color:red'> {$row['name']} Fail$source_file</b>";
        else if($row['status'] == 1 ) $status = "<b style='color:green'>{$row['name']}$source_file</b>";


    }

    $quality = '';$c='';
    if(!empty($row2['resolution']) ) {
        $res = explode('x',$row2['resolution']);
        $c = "({$row2['video_codec']}/{$row2['audio_codec']})";
        if($res[0] >= 1920 && $res[1] >= 1080 ) $quality = "FHD ({$row2['resolution']})";
        else if($res[0] >= 1280 && $res[1] >= 720 ) $quality = "HD ({$row2['resolution']})";
        else $quality = "SD ({$row2['resolution']})";

        $quality .= " <br/>$c";
    }

    $logo = '';
    if($limit < 99 ) {
        if($row['logo']) {
            $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
                ? $logo = "<img width=80 height=120 src='{$row['logo']}'/>"
                : "<img width=80 height=120 src='http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}'/>";
        }
    } else {
        $logo = '[HIDDEN]';
    }


    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $logo,
        $status,
        strtoupper($row['type']),
        implode(', ',$categories),
        $quality,
        $row['type'] == 'series' ? 'N/A' : (int)$row2['play_count'],
        $server,
        $row['add_date'],
        $row['release_date'],
        $links
    );

}

echo json_encode($json);