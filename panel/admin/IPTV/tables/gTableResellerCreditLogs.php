<?php
header('Content-Type: application/json');
header('Connection: close');

use \ACES2\IPTV\Account;
use \ACES2\IPTV\CreditLog;



$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();

function emptyTable() {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}

if (!adminIsLogged(false)) {
    emptyTable();

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS) ) {
    emptyTable();
}


$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$user_id = (int)$_GET['filter_user_id'];
if(!$user_id)
    emptyTable();

$filters[] = " c.from_user_id = $user_id ";

if( (int)$_GET['filter_to_account_id'] )
    $filters[] = " c.to_account_id = '".(int)$_GET['filter_to_account_id']."' ";

if( !empty($_REQUEST['filter_to_user_id']) )
    $filters[] = " c.to_user_id = '".(int)$_REQUEST['filter_to_user_id']."' ";


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " c.time LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR c.time LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY c.time $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY c.type $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT c.time FROM iptv_credit_logs c
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT c.time FROM iptv_credit_logs c
 $Where  ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT c.* , p.name as package_name, d.username as account_username, 
       r.username as reseller_userame, r.name as reseller_name , d.demo as is_trial

    FROM iptv_credit_logs c
    LEFT JOIN iptv_devices d ON d.id = c.to_account_id
    LEFT JOIN users r ON r.id = c.to_user_id
    LEFT JOIN iptv_bouquet_packages p ON p.id = c.package_id


$Where $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $action = '';
    if($row['to_account_id'] > 0 ) {

        $trial = $row['is_trial'] == 1 ? "TRIAL" : "";

        $action = match((int)$row['type']) {
            CreditLog::TYPE_RESELLER_ADD_ACCOUNT => "CREATE $trial ACCOUNT",
            CreditLog::TYPE_RESELLER_UPDATE_ACCOUNT => "UPDATE ACCOUNT",
            CreditLog::TYPE_USER_ACCOUNT_REFUNDED => "REFUND ACCOUNT",
            default => ""
        };

            $link = "Account : <a target='_blank' href='/admin/IPTV/form/formAccount.php?id={$row['to_account_id']}'>
                #{$row['to_account_id']} {$row['account_username']}</a>";
            $pack_name = $row['package_name'];


    }  else  {

        $action = match((int)$row['type']) {
            CreditLog::TYPE_RESELLER_REMOVE_RESELLER => "REMOVE RESELLER",
            CreditLog::TYPE_RESELLER_CREATE_RESELLER => "ADD RESELLER",
            CreditLog::TYPE_RESELLER_TO_RESELLER => "UPDATE RESELLER",
            default => ""
        };

        $link = "Reseller :<a  target='_blank' href='/admin/IPTV/form/formReseller.php?id={$row['to_user_id']}'>
                #{$row['to_user_id']} {$row['reseller_name']}</a>";
        $pack_name = 'N/A';

    }

    $credits = $row['credits'];
//    $credits = $row['credits'] < 0 ?
//        "REFUNDED " .  ($row['credits'] * 1) . " CREDITS" :
//        "USED " . $row['credits'] . " CREDITS";

    $json['data'][] = array(
        'DT_RowId' => "{$row['from_user_id']}-{$row['time']}",
        date("M d, Y H:i", $row['time']),
        $action,
        $link,
        $pack_name,
        $credits,
        $row['remaining_credits'],
    );



}

echo json_encode($json);