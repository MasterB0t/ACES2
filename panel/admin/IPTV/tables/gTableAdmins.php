<?php

header('Content-Type: application/json');
header('Connection: close');

$PRO = 'http:';
if (isset($_SERVER['HTTPS'])) $PRO = 'https:';


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

} else if (!$ADMIN->hasPermission('') ) {
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

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = "  a.name LIKE '%$s%' OR a.username LIKE '%$s%' OR a.email LIKE '%$s%'  ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR a.id LIKE '%$s%' ";

}

$Where = "";
if($search)
    $Where = " WHERE $search ";



$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY a.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY a.name $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY a.username $order_dir ";
else if($_GET['order'][0]['column'] == 4 ) $Order = " ORDER BY a.email $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT id FROM admins a ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT id FROM admins a $Where ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT a.*,g.name as group_name FROM admins a 
    LEFT JOIN admin_groups g ON g.id = a.group_id
    $Where $Order LIMIT $start,$limit");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $group_name = "[FULL ADMIN]";
    if($row['full_admin'] == 0 )
        $group_name = $row['group_name'];

    $links = "<a href='form/formAdmin.php?id={$row['id']}' title='Edit Admin' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Admin' onClick=\"MODAL( 'modals/admin/mRemoveAdmin.php?id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $row['username'],
        $group_name,
        $row['email'],
        $links
    );

}

echo json_encode($json);