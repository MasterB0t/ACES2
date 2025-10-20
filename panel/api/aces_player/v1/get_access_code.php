<?php

include_once '/home/aces/panel/functions/logs.php';
include_once '/home/aces/stream/config.php';
include_once '/home/aces/panel/includes/init.php';

header('Content-Type: application/json');

function set_error($msg='') {

    GLOBAL $AUTH;
    echo json_encode( array('status'=>0, 'error_message' => $msg )  ); die;

}


$DB = new \ACES\DB;
if($DB->connect_errno > 0) set_error("SYSTEM ERROR.");

$uuid = $DB->escString($_GET['uuid']);

function print_code($code) {

    echo json_encode( array(
        'status' => 1,
        'code' => $code
    ));
    exit;

}

$r=$DB->query("SELECT code,account_id FROM iptv_app_access_code WHERE uuid = '$uuid' AND exp_time > unix_timestamp() ");
if($row=$r->fetch_assoc()) {

    if($row['account_id']) {

        $r = $DB->query("SELECT username,token as password FROM iptv_devices WHERE id = '{$row['account_id']}' ");
        if ($device = $r->fetch_assoc()) {

            echo json_encode(array(
                'status' => 1,
                'username' => $device['username'],
                'password' => $device['password']
            ));
            exit;
        }

    }

    print_code($row['code']);

}

$md5 = md5(rand(1,9999999).time());
$code = strtoupper(substr($md5,0,8));
$time = time();


$DB->query("DELETE FROM iptv_app_access_code WHERE exp_time < unix_timestamp() ");

$r=$DB->query("INSERT INTO iptv_app_access_code (code,uuid,create_time,exp_time) VALUES('$code','$uuid',unix_timestamp(),unix_timestamp() + (60*60) )");

print_code($code);


