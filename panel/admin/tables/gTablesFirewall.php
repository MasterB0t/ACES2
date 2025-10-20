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

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";

if($_GET['filter_status'] === "active" ) {
    $filters[] = "f.status = 1" ;
} else if($_GET['filter_status'] === "disabled" )
    $filters[] = "f.status = 2";
else if($_GET['filter_status'] === "blocked" )
    $filters[] = "f.status = 3";

$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " f.ip_address LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY f.ip_address $order_dir ";
//else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY f.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT f.ip_address FROM firewall f
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT f.ip_address FROM firewall f
 $Where GROUP BY f.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT f.* 

    FROM firewall f

$Where GROUP BY f.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='#' onclick=\"MODAL('modals/mFirewallRule.php?id={$row['id']}');\" title='Edit Rule' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Rule' onClick=\"removeRule( '{$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";
    
    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['ip_address'],
        $row['rule'],
        $row['options'],
        $row['comments'],
        $links
    );

}

echo json_encode($json);