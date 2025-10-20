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

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_ACCOUNTS) ) {
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

if(isset($_GET['filter_account_id'])) {
    $filters[] = " l.account_id = '".(int)$_GET['filter_account_id']."' ";
}



$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " a.name LIKE '%$s%' AND $filter_sql ";
    $search .= " OR a.username LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR l.account_id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY l.log_date $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY a.name $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY l.ip_address $order_dir ";
else if($_GET['order'][0]['column'] == 4 ) $Order = " ORDER BY l.user_agent $order_dir ";



$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT l.log_date FROM iptv_account_logs l
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT l.log_date FROM iptv_account_logs l
    LEFT JOIN iptv_devices a ON a.id = l.account_id
 $Where  ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT l.*,a.name,s.name as stream_name 

    FROM iptv_account_logs l
    LEFT JOIN iptv_devices a ON a.id = l.account_id
    LEFT JOIN iptv_channels s ON l.stream_id = s.id

$Where $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $dateTime = new \DateTime($row['log_date']);
    $diff = $dateTime->diff(new \DateTime(date('Y-m-d H:i:s')));
    if( $diff->d < 3  && $diff->m == 0 && $diff->y == 0 ) {
        $date = DateBeautyPrint::simplePrint(time(), strtotime($row['log_date'])) . " ago";
    } else {
        $date = $dateTime->format('D j \o\f M Y h:i:s A');
    }


    $log = is_null($row['stream_name']) ? "[STREAM REMOVED]" : "#{$row['stream_id']} - " . $row['stream_name'];
    $account_name = is_null($row['name'])
        ? "#{$row['account_id']} - [ACCOUNT REMOVED]"
        : "<a  href='/admin/IPTV/accounts.php?filter_account_id={$row['account_id']}'>#{$row['account_id']} - {$row['name']}</a>";

    $json['data'][] = array(
        'DT_RowId' => $row['log_date'],
        $date,
        $account_name,
        $log,
        $row['ip_address'],
        $row['user_agent'],
    );

}

echo json_encode($json);