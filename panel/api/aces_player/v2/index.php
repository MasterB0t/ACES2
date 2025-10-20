<?php
header('Content-Type: application/json');

ini_set('memory_limit','500M');
set_time_limit ( 0 );

include_once '/home/aces/stream/config.php';
include_once $_SERVER['DOCUMENT_ROOT']."/class/db.php";
include_once $_SERVER['DOCUMENT_ROOT']."/class/TMDB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Armor/Armor.php";


if(!function_exists("_LOG")) {
function _LOG($msg) {
    if(is_array($msg)) $msg = print_r($msg,1);
    error_log($msg."\n", 3, "/home/aces/logs/aces_api.log");
} }

function set_error($msg='',$error_code=200) {

    GLOBAL $AUTH;
    http_response_code($error_code);
    echo json_encode( array( 'status' => false, 'error_message' => $msg )  ); die;

}

function set_completed($data='') {

    echo json_encode(array(
        'status' => true,
        'error_message' => '',
        'data' => $data
        )
    );
}


$DB = new \DB($DBHOST, $DBUSER, $DBPASS, $DATABASE );
if($DB->connect_errno > 0){ die; }


//$ARMOR = new \ACES\Armor();
//if( $ARMOR->isBlock('iptv-player_api') )
//{ _LOG("ARMOR"); echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }


$USERNAME = $DB->escape_string($_GET['username']);
$PASSWORD = $DB->escape_string($_GET['password']);
$TOKEN = $DB->escape_string($_GET['token']);

$HOST = isset($_SERVER['HTTPS'] ) ? "https://".$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

$r=$DB->query("SELECT a.id,a.limit_connections,a.subcription,a.status,a.bouquets,a.adults,a.only_mag, 
    d.id as device_id, d.last_profile_id as profile_id, TIMESTAMPDIFF(SECOND, NOW(), a.subcription) as expire_in
    FROM  iptv_app_devices d
    RIGHT JOIN iptv_devices a ON a.id = d.account_id
    WHERE d.token = '$TOKEN' AND a.username = '$USERNAME' AND a.token = '$PASSWORD'
");

if(!$ACCOUNT=$r->fetch_assoc() ) {
    http_response_code(401);
    echo json_encode(  array()  );  die;
}


if($ACCOUNT['only_mag'] != 0 ) {
    http_response_code(401);
    echo json_encode( array()  );  die; }

if($ACCOUNT['status'] != 1 || $ACCOUNT['expire_in'] < 1 ) {

    http_response_code( 403 );

    if ($ACCOUNT['expire_in'] < 1) $status = 'expired';
    else if ($ACCOUNT['status'] != 1) $status = 'blocked';

    $account_info = array(
        'status' => $status,
        'expire_time' => (string)strtotime($ACCOUNT['subcription']),
        'max_connections' => $ACCOUNT['limit_connections'],
        'active_connections' => 0,
        'auth' => 0
    );


    echo json_encode($account_info);
    exit;

}

$ACCOUNT['bouquets'] = join("','", unserialize($ACCOUNT['bouquets']) );


if(!empty($_GET['action'])) {

    $action = strtolower($_GET['action']);
    if (is_file("/home/aces/panel/api/aces_player/v2/{$action}.php")) {
        require("/home/aces/panel/api/aces_player/v2/{$action}.php");

    } else if(is_file("/home/aces/panel/api/aces_player/v1/{$action}.php")) {
        require_once("/home/aces/panel/api/aces_player/v1/{$action}.php");
    } else
        http_response_code(404);

} else {

    $r_conns=$DB->query("SELECT id FROM iptv_access WHERE device_id = '{$ACCOUNT['id']}'");

    $account_info = array(
        'id' => $ACCOUNT['id'],
        'status' => 'active',
        'expiration_time' => (string)strtotime($ACCOUNT['subcription']),
        'expiration_date' => $ACCOUNT['subcription'],
        'max_connections' => $ACCOUNT['limit_connections'],
        'active_connections' => $r_conns->num_rows,

    );

    echo json_encode($account_info);
    exit;

}