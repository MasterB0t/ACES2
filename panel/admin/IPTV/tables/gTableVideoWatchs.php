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

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL) ) {
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


if(isset($_GET['filter_server'])) {
    $filters[] = "w.server_id = " . (int)$_GET['filter_server'];
}

switch(@$_GET['filter_type']) {
    case 'folder' : $filters[] = " w.is_xc = 0 AND w.is_plex = 0 "; break;
    case 'xtreamcodes' : $filters[] = " w.is_xc = 1 "; break;
}


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " w.watch LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR w.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY w.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY w.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT w.id FROM iptv_folder_watch w
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT w.id FROM iptv_folder_watch w
 $Where GROUP BY w.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT w.*, s.name as server_name
    FROM iptv_folder_watch w
    LEFT JOIN iptv_servers s ON s.id = w.server_id
$Where GROUP BY w.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $params = json_decode($row['params'], true) ?: unserialize($row['params']);

    if($row['is_xc']) {
        $type = "XtreamCodes<br />{$params['host']}";
        $watch = $row['watch'] ? : $params['host'];
        $links = "<a href='form/formVideoWatch.php?id={$row['id']}&xc_account=' title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    } else if($row['is_plex']) {
        $type = "Plex";
        $watch = "Host : {$params['host']}";
        $links = "<a href='form/formVideoWatch.php?id={$row['id']}' title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    } else {
        $type = "Folder";
        $watch = $row['watch'] ?: urldecode($params['directory']);
        $links = "<a href='form/formVideoWatch.php?id={$row['id']}' title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    }

    $r2=$DB->query("SELECT id FROM iptv_proccess WHERE id = '{$row['pid']}' ");
    if($r2->num_rows < 1 ) {
        $last_run = date('D d, H:i', $row['last_run']);
        $links .= "<a href='#!' title='Start Now' onClick=\"startWatch( '{$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-arrows-rotate fa-lg'></i> </a>";
    } else {
        $last_run = "<b class='badge badge-success p-1'> Running Now </b>";
        $links .= "<a href='#!' title='Start Now' onClick=\"stopWatch( '{$row['pid']}' );\"> 
            <i style='margin:5px;' class='fa fa-stop fa-lg'></i> </a>";
    }
    $last_run = $r2->num_rows < 1
        ? date('D d, H:i', $row['last_run'])
        : "<b class='badge badge-success p-1'> Running Now </b>";



    $links .= "<a href='#!' title='Remove' onClick=\"removeWatch( '{$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $is_enabled = $row['enabled'] ?
        "<b class='badge badge-success '>Enabled</b>" :
        "<b class='badge badge-danger'>Disabled</b>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $type,
        $watch,
        $is_enabled,
        $row['server_name'],
        $row['interval_mins'],
        $last_run,
        $links
    );

}

echo json_encode($json);