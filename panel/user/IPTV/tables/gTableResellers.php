<?php 
header('Content-Type: application/json');
header('Connection: close');

if (!$UserID=userIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

}

$DB = new \ACES2\DB();
$USER = new \ACES2\IPTV\User($UserID);
$SubResellers = $USER->getResellers();
if(count($SubResellers) < 1 ){
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => $_GET['draw'],
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

$filters[] = " u.id in ( " . implode(',', $SubResellers) . ") ";

if((int)@$_GET['filter_reseller_of'] && in_array($_GET['filter_reseller_of'], $SubResellers ) ) {
    $filters[] = " i.user_owner = ".(int)$_GET['filter_reseller_of'];
}


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " u.name LIKE '%$s%' AND $filter_sql ";
    $search .= " OR u.email LIKE '%$s%' AND $filter_sql ";
    $search .= " OR u.username LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR u.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY u.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY u.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT u.id FROM users u
 ");
$json['recordsTotal'] = $r->num_rows;


$r=$DB->query("SELECT u.id 
    FROM users u
    RIGHT JOIN iptv_user_info i ON u.id = i.user_id
 $Where GROUP BY u.id ");
$json['recordsFiltered'] = $r->num_rows;



$r=$DB->query("SELECT u.*,i.credits , i.user_owner, o.name as owner_name , o.username as owner_username 

    FROM users u
    RIGHT JOIN iptv_user_info i ON u.id = i.user_id
    RIGHT JOIN users o ON o.id = i.user_owner

$Where GROUP BY u.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formReseller.php?id={$row['id']}' title='Edit Reseller' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Add/Remove Credits' onClick=\"MODAL( 'modals/mResellerCredits.php?reseller_id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-certificate fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Reseller' onClick=\"MODAL( 'modals/mResellerRemove.php?ids={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $links .= "<a href='credit_logs.php?reseller_id={$row['id']}' title='Edit Credit Logs' > 
            <i style='margin:5px;' class='fa fa-book fa-lg'></i> </a>";


    $r_accounts = $DB->query("SELECT id FROM iptv_devices WHERE id = '{$row['id']}' ");
    $r_accounts_active = $DB->query("SELECT id FROM iptv_devices WHERE id = '{$row['id']}' AND subcription > NOW() ");

    $accounts = "{$r_accounts_active->num_rows} / {$r_accounts->num_rows}";


    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $row['username'],
        $row['email'],
        "{$row['owner_name']} - {$row['owner_username']}",
        $row['credits'],
        $accounts,
        $links
    );

}

echo json_encode($json);