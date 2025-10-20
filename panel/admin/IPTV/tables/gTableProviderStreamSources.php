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

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS) ) {
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

if(isset($_GET['filter_providers'])) {
    $ids = [];
    foreach(explode(",", $_GET['filter_providers']) as $id) {
        if((int)$id > 0)
            $ids[] = $id;
    }
    if(count($ids) > 0)
        $filters[] = " xc.id in (" . implode(" , ", $ids) . ") ";
}

$filter_sql = implode(" AND ", $filters);



$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " ps.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR ps.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY ps.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY ps.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT ps.id FROM iptv_provider_content_streams ps
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT ps.id FROM iptv_provider_content_streams ps
    INNER JOIN iptv_xc_videos_imp xc ON xc.id = ps.provider_id 
 $Where GROUP BY ps.id ");
$json['recordsFiltered'] = $r->num_rows;

logD("SELECT ps.stream_id,ps.category_name,ps.name,xc.host as provider,xc.username,xc.password,
       xc.port

    FROM iptv_provider_content_streams ps
    INNER JOIN iptv_xc_videos_imp xc ON xc.id = ps.provider_id

$Where GROUP BY ps.id $Order LIMIT $start,$limit ");

$r=$DB->query("SELECT ps.stream_id,ps.category_name,ps.name,xc.host as provider,xc.username,xc.password,
       xc.port

    FROM iptv_provider_content_streams ps
    INNER JOIN iptv_xc_videos_imp xc ON xc.id = ps.provider_id

$Where GROUP BY ps.id $Order LIMIT $start,$limit ");




if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $url = $row['is_ssl'] ? 'https://' : 'http://';
    $url .= "{$row['provider']}:{$row['port']}/live/{$row['username']}/{$row['password']}/{$row['stream_id']}";

    $links = "<a onclick='addProviderSource(\"{$url}\");' href='#' title='Add Source' > 
            <i style='margin:5px;' class='fa fa-plus fa-lg'></i> </a>";

    $links .= "<a href='vlc://{$url}' title='Play On Player' > 
            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['stream_id'],
        $row['name'],
        $row['provider'],
        $links
    );

}

echo json_encode($json);