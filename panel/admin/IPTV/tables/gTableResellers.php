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

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS) ) {
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

if((int)$_GET['filter_user_id'])
    $filters[] = " r.id = ".$_GET['filter_user_id'];

if((int)$_GET['filter_owner'])
    $filters[] = " u2.id = ".$_GET['filter_owner'];

if($_GET['filter_status'] === "1" || $_GET['filter_status'] === "0")
    $filters[] = " r.status = ".$_GET['filter_status'];

$filter_sql = implode(" AND ", $filters);


$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = "  r.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR r.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY r.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY r.name $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY r.username $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY r.email $order_dir ";
else if($_GET['order'][0]['column'] == 4 ) $Order = " ORDER BY u2.username $order_dir ";
else if($_GET['order'][0]['column'] == 5 ) $Order = " ORDER BY i.credits $order_dir ";
else if($_GET['order'][0]['column'] == 7 ) $Order = " ORDER BY r.login_date $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT r.id FROM users r 
    LEFT JOIN iptv_user_info i ON i.user_id = r.id 
    LEFT JOIN users u2 ON u2.id = i.user_owner GROUP BY r.id ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT r.id FROM users r 
 LEFT JOIN iptv_user_info i ON i.user_id = r.id 
 LEFT JOIN users u2 ON u2.id = i.user_owner 
 $Where GROUP BY r.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT r.id,r.name,r.username,r.email,i.credits,r.login_date,r.login_ip,r.status,
       u2.username as main_username, u2.name as main_name FROM users r
    LEFT JOIN iptv_user_info i ON i.user_id = r.id
    LEFT JOIN users u2 ON u2.id = i.user_owner
    
    $Where $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $rall = $DB->query("SELECT count(id) as total FROM iptv_devices WHERE user_id = {$row['id']} ");
    $total_account=$rall->fetch_assoc()['total'];

    $ract = $DB->query("SELECT count(id) as active FROM iptv_devices WHERE user_id = {$row['id']} AND subcription > NOW() ");
    $active_account=$ract->fetch_assoc()['active'];

    $links = "<a href='form/formReseller.php?id={$row['id']}' title='Edit Reseller' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";

    $links .= "<a href='reseller_credit_logs.php?user_id={$row['id']}' title='Edit Credit Logs' > 
            <i style='margin:5px;' class='fa fa-book fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Remove Reseller' onClick=\"MODAL( 'modals/mRemoveReseller.php?ids={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $reseller_of = $row['main_username'] ? "{$row['main_name']}<br/>{$row['main_username']}" : "[Admin]";

    $name = match((int)$row['status']) {
        1 => "{$row['name']}<br /><span class='badge badge-success p-1'>Active</span>",
        0 => "{$row['name']}<br /><span class='badge badge-secondary p-1'>Disabled</span>",
        2 => "{$row['name']}<br /><span class='badge badge-danger p-1'>Blocked</span>"
    };

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $name,
        $row['username'],
        $row['email'],
        $reseller_of,
        $row['credits'],
        "$active_account/$total_account",
        "{$row['login_date']}<br />{$row['login_ip']}",
        $links
    );

}

echo json_encode($json);