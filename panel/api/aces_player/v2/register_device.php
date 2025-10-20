<?php

function _LOG($msg) {
    if(is_array($msg)) $msg = print_r($msg,1);
    error_log($msg."\n", 3, "/home/aces/logs/aces_api.log");
}

//set_time_limit ( 0 );

header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization");
header("Vary: Origin");
header('Content-Type: application/json');


include_once $_SERVER['DOCUMENT_ROOT'].'/functions/logs.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/ACES2/DB.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/ACES2/Armor/Armor.php';
include_once '/home/aces/stream/config.php';

//include_once '/home/aces/panel/includes/init.php';

function set_error($msg='') {

    GLOBAL $AUTH;
    echo json_encode( array('status'=>false, 'error_message' => $msg )  ); die;

}


$DB = new \ACES2\DB;
if($DB->connect_errno > 0)
    set_error("SYSTEM ERROR.");

if(empty($_GET['password']) || empty($_GET['username']) )  {
    echo json_encode(  array('status'=> 0  )  ); die;
}

//$ARMOR = new \ACES2\Armor();
//if( $ARMOR->isBlock('iptv-player_api') )
//{ echo json_encode(  array('status'=> 0 )  ); die; }

$PASSWORD = $DB->escape_string($_GET['password']);
$USERNAME = $DB->escape_string($_GET['username']);

$sql = " WHERE username = '$USERNAME' AND token = '$PASSWORD'  ";

$r=$DB->query("SELECT id,token,username,limit_connections,subcription,demo,status,add_date,bouquets,adults,only_mag, 
       TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM iptv_devices $sql  ");

if(!$ACCOUNT= $r->fetch_assoc() ) {

    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Server.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Firewall.php";

    $Armor = new \ACES2\ARMOR\Armor();
    $Armor->log_ban("iptv-account");
    if($Armor->isBan('iptv-account')) {
        $fw = ACES2\Firewall::add(
            'INPUT',
            'DROP',
            $_SERVER['REMOTE_ADDR'],
            '',
            '',
            '',
            'AUTO BLOCK'
        );
        $fw->appendRule(1);
    }
    echo json_encode(  array('status'=> 0, 'error_message' => "Wrong username or password" )  );  die;

}

if($ACCOUNT['expire_in'] < 1 )
    set_error("ACCOUNT EXPIRED");


if($ACCOUNT['status'] != 1 || $ACCOUNT['only_mag'] != 0 ) {
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die;
}

$device = json_decode(base64_decode($_GET['device']),true);

$platform = $DB->escape_string($device['platform']);
$version = $DB->escape_string($device['version']);
$uuid = $DB->escape_string($device['uuid']);
if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid) !== 1)) {
    set_error("UUID NOT VALID");
}

$model = $DB->escape_string($device['model']);
$manufacture = $DB->escape_string($device['manufacturer']);

$sdkVersion = $DB->escape_string($device['androidSdk']);
$androidRelease = $DB->escape_string($device['androidRelease']);
$resolution = $DB->escape_string($device['screenResolution']);

//$resolution = (int)$device['screen']['width']."X".(int)$device['screen']['height'];
//$diameter = (float)$device['screen']['diameter'];
//$dpi = (int)$device['screen']['xdpi']."X".(int)$device['screen']['ydpi'];
//$densityValue = (int)$device['screen']['densityValue'];
//$densityBucket = $DB->escape_string($device['screen']['densityBucket']);



$token = md5(time());


$r=$DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}' LIMIT 1 ");
if(!$profile_id=$r->fetch_assoc()['id']) {
    $DB->query("INSERT INTO iptv_app_profiles (account_id,name) VALUES('{$ACCOUNT['id']}','Default')");
    $profile_id = $r->num_rows;
}


$r=$DB->query("SELECT id FROM  iptv_app_devices WHERE account_id = '{$ACCOUNT['id']}' AND uuid = '$uuid' ");
if(!$row=$r->fetch_assoc()) {
    $DB->query("INSERT INTO iptv_app_devices (account_id,uuid,os_version,sdkVersion,platform,model,resolution,token) 
            VALUES('{$ACCOUNT['id']}','$uuid','$version','$sdkVersion','$platform','$model','$resolution','$token') ");
    $account_id = $DB->insert_id;
} else {
    AcesLogD("UPDATING TOKEN #{$row['id']}  $token");
    $DB->query("UPDATE iptv_app_devices SET os_version = '$version', sdkVersion = '$sdkVersion', platform = '$platform', 
                            model = '$model', token = '$token', resolution = '$resolution'
                        WHERE id = '{$row['id']}'");
    $account_id = $row['id'];
}

if($ACCOUNT['expire_in'] < 1 ) $status = 'expired';
else if($ACCOUNT['status'] != 1 ) $status = 'blocked';
else $status = 'active';

$account_info = array(
    'status' => $status,
    'expire_time' => (string)strtotime($ACCOUNT['subcription']),
    'max_connections' =>  $ACCOUNT['limit_connections'],
    'active_connections' => (int)$active_conns,
    'token' => $token,
    'account_id' => $ACCOUNT['id']
);


echo json_encode(array("status" => true, 'account'=> $account_info ));

$DB->query("DELETE FROM iptv_app_access_code WHERE account_id = '{$ACCOUNT['device_id']}' ");