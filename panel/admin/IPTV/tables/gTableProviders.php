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

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";



$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " p.host LIKE '%$s%' AND $filter_sql ";
    $search .= " OR p.username LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR p.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY p.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY p.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT p.id FROM iptv_xc_videos_imp p
 ");
$json['recordsTotal'] = $r->num_rows;


$r=$DB->query("SELECT p.id FROM iptv_xc_videos_imp p
 $Where GROUP BY p.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT p.* 

    FROM iptv_xc_videos_imp p

$Where GROUP BY p.id $Order LIMIT $start,$limit ");



if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $r_movies = $DB->query("SELECT id FROM iptv_provider_content_vod  WHERE provider_id='{$row['id']}' AND is_series = 0 ");
    $r_seires = $DB->query("SELECT id FROM iptv_provider_content_vod  WHERE provider_id='{$row['id']}' AND is_series = 1 ");
    $r_streams = $DB->query("SELECT id FROM iptv_provider_content_streams  WHERE provider_id='{$row['id']}' ");

    $links = "<a href='#' onClick=\"MODAL( 'modals/mXCAccount.php?account_id={$row['id']}' );\" title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Force Refresh' onClick=\"forceRefresh('{$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-arrows-rotate fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Update Content' onClick=\"updateContent('{$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-download fa-lg'></i> </a>";
    $links .= "<a href='provider_content.php?filter_provider={$row['id']}' title='Provider Content' > 
            <i style='margin:5px;' class='fa fa-list fa-lg'></i> </a>";
    $links .= "<a href='#' onclick='clearContent({$row['id']});' title='Clear Content' > 
            <i style='margin:5px;' class='fa fa-broom fa-lg'></i> </a>";

    $links .= "<a href='#' onclick='removeProvider({$row['id']});' title='Remove Account' > 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $max_conns = 0;
    $active_conns = 0;
    $exp_date = '';
    $XC = new \ACES2\IPTV\XCAPI\XCAccount($row['id']);
    try {
        $max_conns = (int)$XC->getMaxConnections();
        $active_conns = (int)$XC->getActiveConnections();
        $status = $XC->getStatus();
        $b = $status == 'Active' ? 'badge-success' : 'badge-danger';
        $exp_date = $XC->getExpireDate()."<br /><span class='badge $b'>$status</span>";
    } catch( Exception $e ) {
        $ignore = 0;
    }

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $XC->host,
        $row['username'],
        $active_conns."/".$max_conns,
        $exp_date,
        $r_streams->num_rows,
        $r_movies->num_rows,
        $r_seires->num_rows,
        $links
    );

}

echo json_encode($json);