<?php

//header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
//header('Connection: close');

ini_set('memory_limit','500M');
set_time_limit ( 0 );

include_once '/home/aces/panel/functions/logs.php';
include_once '/home/aces/stream/config.php';
include_once '/home/aces/panel/includes/init.php';

$AUTH = 0 ;
$TMDB_API_KEY='';

function set_error($msg='',$error_code=400) {

    GLOBAL $AUTH;
    http_response_code($error_code);
    echo json_encode( array('status'=>0, 'error_message' => $msg )  ); die;

}

function set_completed($data='') {

    $json = array('status' => 1 );
    if(!is_array($data)) $json['data'] = $data;

    echo json_encode($json);
}


function _LOG($msg) {
    if(is_array($msg)) $msg = print_r($msg,1);
    error_log($msg."\n", 3, "/home/aces/logs/aces_api.log");
}

$DB = new \ACES\DB;
if($DB->connect_errno > 0){ die; }


if(empty($_GET['token']) || empty($_GET['username']) || empty($_GET['password']) )
{ echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }

$ARMOR = new \ACES\Armor();
if( $ARMOR->isBlock('iptv-player_api') )
    { _LOG("ARMOR"); echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }


$USERNAME = $DB->escape_string($_GET['username']);
$PASSWORD = $DB->escape_string($_GET['password']);
$TOKEN = $DB->escape_string($_GET['token']);


$r=$DB->query("SELECT a.id,a.limit_connections,a.subcription,a.status,a.bouquets,a.adults,a.only_mag, 
    d.id as device_id, d.last_profile_id as profile_id, TIMESTAMPDIFF(SECOND, NOW(), a.subcription) as expire_in
    FROM  iptv_app_devices d
    RIGHT JOIN iptv_devices a ON a.id = d.account_id
    WHERE d.token = '$TOKEN' AND a.username = '$USERNAME' AND a.token = '$PASSWORD'
");


if(!$ACCOUNT=$r->fetch_assoc() ) {
    _LOG("NO DB");
    $ARMOR->action('iptv-player_api');
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die;
}


if($ACCOUNT['only_mag'] != 0 ) { _LOG("ONLY MAG");
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die; }

if($ACCOUNT['status'] != 1 || $ACCOUNT['expire_in'] < 1 ) {

    if ($ACCOUNT['expire_in'] < 1) $status = 'expired';
    else if ($ACCOUNT['status'] != 1) $status = 'blocked';
    else $status = 'active';

    $account_info = array(
        'status' => $status,
        'expire_time' => (string)strtotime($ACCOUNT['subcription']),
        'max_connections' => $ACCOUNT['limit_connections'],
        'active_connections' => $active_conns

    );

    echo json_encode($account_info);
    exit;

}

$ACCOUNT['bouquets'] = join("','", unserialize($ACCOUNT['bouquets']) );

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;