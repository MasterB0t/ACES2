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


include_once '/home/aces/panel/functions/logs.php';
include_once '/home/aces/stream/config.php';
include_once '/home/aces/panel/includes/init.php';

function set_error($msg='') {

    GLOBAL $AUTH;
    echo json_encode( array('status'=>0, 'error_message' => $msg )  ); die;

}


$DB = new \ACES\DB;
if($DB->connect_errno > 0)
    set_error("SYSTEM ERROR.");

if(empty($_GET['password']) || empty($_GET['username']) )  {
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die;
}

$ARMOR = new \ACES\Armor();
if( $ARMOR->isBlock('iptv-player_api') )
{ echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }

$PASSWORD = $DB->escape_string($_GET['password']);
$USERNAME = $DB->escape_string($_GET['username']);

$sql = " WHERE username = '$USERNAME' AND token = '$PASSWORD'  ";

$r=$DB->query("SELECT id,token,username,limit_connections,subcription,demo,status,add_date,bouquets,adults,only_mag, TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM iptv_devices $sql  ");
if(!$ACCOUNT=mysqli_fetch_array($r) ) {
    $ARMOR->action('iptv-player_api');
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die; }

if($ACCOUNT['expire_in'] < 1 )
    set_error("ACCOUNT EXPIRED");


if($ACCOUNT['status'] != 1 || $ACCOUNT['only_mag'] != 0 ) {
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die;
}

$device = json_decode(base64_decode($_GET['device']),true);

$platform = $DB->escape_string($device['platform']);
$version = $DB->escape_string($device['version']);
$uuid = $DB->escape_string($device['uuid']);
$cordova_version = $DB->escape_string($device['cordova']);
$model = $DB->escape_string($device['model']);
$manufacture = $DB->escape_string($device['manufacturer']);
$is_virtual = $DB->escape_string($device['is_virtual']);
$sdkVersion = $DB->escape_string($device['sdkVersion']);

$resolution = (int)$device['screen']['width']."X".(int)$device['screen']['height'];
$diameter = (float)$device['screen']['diameter'];
$dpi = (int)$device['screen']['xdpi']."X".(int)$device['screen']['ydpi'];
$densityValue = (int)$device['screen']['densityValue'];
$densityBucket = $DB->escape_string($device['screen']['densityBucket']);



$token = md5(time());


$r=$DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}' LIMIT 1 ");
if(!$profile_id=$r->fetch_assoc()['id']) {
    $DB->query("INSERT INTO iptv_app_profiles (account_id,name) VALUES('{$ACCOUNT['id']}','Default')");
    $profile_id = $r->num_rows;
}


$r=$DB->query("SELECT id FROM  iptv_app_devices WHERE account_id = '{$ACCOUNT['id']}' AND uuid = '$uuid' ");
if(!$row=$r->fetch_assoc()) {
    $DB->query("INSERT INTO iptv_app_devices (account_id,uuid,os_version,sdkVersion,platform,model,is_virtual,resolution,dpi,diameter,density_value,density_bucket,token) 
            VALUES('{$ACCOUNT['id']}','$uuid','$version','$sdkVersion','$platform','$model','$is_virtual','$resolution','$dpi','$diameter','$densityValue','$densityBucket','$token') ");
    $account_id = $DB->insert_id;
} else {
    $DB->query("UPDATE iptv_app_devices SET os_version = '$version', sdkVersion = '$sdkVersion', platform = '$platform', model = '$model', is_virtual = '$is_virtual', token = '$token', resolution = '$resolution',
                            dpi = '$dpi', diameter = '$diameter', density_value = '$densityValue', density_bucket = '$densityBucket'
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
    'active_connections' => $active_conns,
    'token' => $token,
    'account_id' => $ACCOUNT['id']
);


echo json_encode(array("status" => 1, 'account_info'=> $account_info ));

$DB->query("DELETE FROM iptv_app_access_code WHERE account_id = '{$ACCOUNT['device_id']}' ");
