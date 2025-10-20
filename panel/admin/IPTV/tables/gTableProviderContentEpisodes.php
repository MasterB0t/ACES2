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


$limit = $_GET['length'] > 9 && $_GET['length'] < 1001 ? (int)$_GET['length']: 10;

$filters[] = " 1 = 1 ";

if(!empty($_GET['filter_season']))
    $filters[] = " e.season_number = '". (int)$_GET['filter_season']."' ";

if(!empty($_GET['filter_series']))
    $filters[] = " e.content_vod_id = '". (int)$_GET['filter_series']."' ";

if(@$_GET['filter_status'] == 'added')
    $filters[] = 'e.added_id > 0';
else if(@$_GET['filter_status'] == 'not_added')
    $filters[] = 'e.added_id = 0';

$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " e.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR e.episode_id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY e.episode_id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY e.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT e.episode_id FROM iptv_provider_content_episodes e
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT e.episode_id FROM iptv_provider_content_episodes e
 $Where GROUP BY e.episode_id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT e.*, v.name as series_name , e.provider_id
       
    FROM iptv_provider_content_episodes e
    LEFT JOIN iptv_provider_content_vod v ON e.content_vod_id = v.id

$Where GROUP BY e.episode_id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $xc_account = new  \ACES2\IPTV\XCAPI\XCAccount($row['provider_id']);
//    $url = base64_encode("$xc_account->url/episodes/$xc_account->username/$xc_account->password/{$row['episode_id']}.mp4");
//    $links = "<a href='/admin/IPTV/qplaylist.php?url=$url' title='Play in player' >
//            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    $url = "$xc_account->url/episode/$xc_account->username/$xc_account->password/{$row['episode_id']}.mp4";
    $links = "<a href='vlc://{$url}' title='Play in player' >
            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL))
        $links .= "<a href='#' onclick=\"MODAL('/admin/IPTV/modals/mProviderAddEpisode.php?ids={$row['episode_id']}');\"  >
                <i style='margin:5px;' class='fa fa-plus fa-lg'></i> </a>";


    $logo = $limit < 101 ? "<img src='{$row['image']}' width='120' />" : "[HIDDEN]";
    $is_added = $row['is_added'] ? 'Added' : 'Not Added';
    $provider = $row['host'] ? : $row['provider_id'];

    $json['data'][] = array(
        'DT_RowId' => $row['episode_id'],
        $row['episode_id'],
        $logo,
        $row['name'],
        $row['episode_number'],
        $row['season_number'],
        $row['series_name'],
        $links
    );

}

echo json_encode($json);