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

if((int)$_GET['filter_stream_id']) {
    $filters[] = " stream_id = ".$_GET['filter_stream_id'];
}

if(isset($_GET['filter_status'])) {
    $filters[] = " s.type = ".(int)$_GET['filter_status'];
}

if(isset($_GET['filter_start_date'])  && isset($_GET['filter_end_date']) ) {

    $start_date = strtotime((int)$_GET['filter_start_date'] .'000000' );
    $end_date = strtotime((int)$_GET['filter_end_date'] . '235959');
    $filters[] = " log_time > '$start_date' AND log_time < '$end_date' ";

}

$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " s.source_url LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY s.log_time $order_dir ";
if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY s.last_time $order_dir ";
//else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY s.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT s.stream_id FROM iptv_stream_stats s
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT s.stream_id FROM iptv_stream_stats s
 $Where  ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT s.*, srv.name as server_name, UNIX_TIMESTAMP() as utime
    FROM iptv_stream_stats s
    INNER JOIN iptv_servers srv ON srv.id = s.server_id

$Where   $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $log_type = match((int)$row['type']) {
        \ACES2\IPTV\StreamStats::TYPE_STANDBY => '<span class="badge badge-warning">Stand By</span>',
        \ACES2\IPTV\StreamStats::TYPE_CONNECTING => '<span class="badge badge-danger">Connecting/Down</span>',
        \ACES2\IPTV\StreamStats::TYPE_STREAMING => '<span class="badge badge-success">Streaming</span>',
        default => '<span class="badge badge-light ">Shutoff</span>'
    };

    if($row['type'] == 3 )
        $log_type .= '<br />'.$row['source_url'];

    $time = $row['last_time'] == 0 ? $row['utime'] - $row['log_time'] : $row['last_time'];
    $time = DateBeautyPrint::simplePrint(time(), time() + $time);

    $json['data'][] = array(
        'DT_RowId' => $row['log_time'],
        date('Y-m-d H:i:s', $row['log_time']),
        $log_type,
        $time,
        $row['server_name'],
    );

}

echo json_encode($json);