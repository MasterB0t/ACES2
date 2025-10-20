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

} else if (!$ADMIN->hasPermission() ) {
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

$limit = $_GET['length'] > 9 && $_GET['length'] < 1001 ? (int)$_GET['length'] : 10;


$filters[] = " 1 = 1 ";
if(@$_GET['filter_status'] == 'added')
    $filters[] = 'c.added_id > 0';
else if(@$_GET['filter_status'] == 'not_added')
    $filters[] = 'c.added_id = 0';

if(!empty($_GET['filter_provider']))
    $filters[] = 'c.provider_id = '.(int)$_GET['filter_provider'];

if(@$_GET['filter_type'] == 'series')
    $filters[] = 'c.is_series = 1';
else if(@$_GET['filter_status'] == 'movies')
    $filters[] = 'c.is_series = 0';

if(!empty($_GET['filter_category']))
    $filters[] = 'c.category_id = '.(int)$_GET['filter_category'];

$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " c.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR c.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY c.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY c.name $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY c.vod_id $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY c.provider_id $order_dir ";
else if($_GET['order'][0]['column'] == 7 ) $Order = " ORDER BY c.added_time $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT c.id FROM iptv_provider_content_vod c
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT c.id FROM iptv_provider_content_vod c
 $Where GROUP BY c.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT c.*, p.host

    FROM iptv_provider_content_vod c
    LEFT JOIN iptv_xc_videos_imp p ON p.id = c.provider_id
    
        
$Where GROUP BY c.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $stream_id = $row['is_series'] == 1 ? $row['provider_series_id'] : $row['provider_movie_id'];

    $links = "<a href='#' title='Add Content' onclick=\"MODAL('modals/mProviderContent.php?ids={$row['id']}');\" > 
            <i style='margin:5px;' class='fa fa-add fa-lg'></i> </a>";

    if($row['is_series'])
    $links .= "<a href='provider_content_episodes.php?filter_series={$row['id']}' title='Episodes' > 
            <i style='margin:5px;' class='fa fa-list fa-lg'></i> </a>";

    else {
        $xc_account = new  \ACES2\IPTV\XCAPI\XCAccount($row['provider_id']);
//        $url = base64_encode("$xc_account->url/movie/$xc_account->username/$xc_account->password/{$stream_id}.mp4");
//        $links .= "<a href='/admin/IPTV/qplaylist.php?url=$url' title='Play in player' >
//            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

        $url = "$xc_account->url/movie/$xc_account->username/$xc_account->password/{$stream_id}.mp4";

        $links .= "<a href='vlc://$url' title='Play in player' >
            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";
    }

    $logo  = $limit < 100 ? "<img src='{$row['logo']}' width='80' height='120' />" : '';
    $is_series = $row['is_series'] ? 'Series' : 'Movie';
    $is_added = $row['added_id'] > 0 ? 'Added' : 'Not Added';
    $provider = $row['host'] ? : $row['provider_id'];

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name']."<br />$logo",
        $stream_id,
        $provider,
        $row['category_name'],
        $is_series,
        $is_added,
        date('Y-m-d', $row['added_time']),
        $links
    );

}

echo json_encode($json);