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

$filter = " 1 = 1 ";

$search = "";$Where = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " e.name LIKE '%$s%' AND $filter ";
    $search .= " e.url LIKE '%$s%' AND $filter ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR e.id LIKE '%$s%' AND $filter ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY e.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY e.name $order_dir ";

$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT id FROM iptv_epg_sources e ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT id FROM iptv_epg_sources e $Where ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT e.* FROM iptv_epg_sources e
    $Where $Order LIMIT $start,$limit");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='#' onClick=\"MODAL('modals/streams/mEpgSource.php?id={$row['id']}')\" title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Update TVG Ids' onClick=\"updateTVGIDs('{$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-arrows-rotate fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove' onClick=\"MODAL( 'modals/streams/mRemoveEpgSource.php?id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";


    $status = $row['status'] ?
        "<span class='p-1 badge badge-success'>Enabled</span>" :
        "<span class='p-1 badge badge-danger'>Disabled</span>" ;

    $err_msg = "<br /><span class='text-danger'>{$row['error_msg']}</span>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $status,
        $row['url'].$err_msg,
        $row['last_update'],
        $links
    );

}

echo json_encode($json);