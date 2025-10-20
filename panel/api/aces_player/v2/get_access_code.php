<?php


header('Content-Type: application/json');

include_once $_SERVER['DOCUMENT_ROOT'].'/functions/logs.php';
include_once '/home/aces/stream/config.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";


function set_error($msg='') {
    //TODO RETROFIT DOES NOT EXPORT JSON IF RESPONSE CODE ISN'T 400. QUICK FIX
    //http_response_code(400);
    echo json_encode( array( 'status' => false, 'error_message' => $msg )  );
    exit;
}


$DB = new \ACES2\DB;
if($DB->connect_errno > 0)
    set_error("SYSTEM ERROR.");

$uuid = $DB->escString($_GET['uuid']);

function print_code($code) {

    echo json_encode( array(
        'username' => '',
        'password' => '',
        'is_code' => true,
        'status' => true,
        'is_logged' => false,
        'code' => $code,
        'error_message' => ''
    ));
    exit;

}


if(preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid)  !== 1 ) {
    setAjaxError("UUID ERROR.");
}


$r=$DB->query("SELECT a.code,a.account_id,d.subcription,TIMESTAMPDIFF(SECOND, NOW(), d.subcription) as expire_in
    FROM iptv_app_access_code a 
    LEFT JOIN iptv_devices d ON d.id = a.account_id 
                           WHERE a.uuid = '$uuid' AND a.exp_time > unix_timestamp() LIMIT 1 ");
if($row=$r->fetch_assoc()) {

    if($row['account_id']) {

        if($row['expire_in'] < 1) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'code' => "",
                'is_code' => true,
                'username' => "",
                'password' => "",
                'is_logged' => true,
                'error_message' => ''
            ));

            exit;

        }

        $r = $DB->query("SELECT username,token as password FROM iptv_devices WHERE id = '{$row['account_id']}' ");
        if ($device = $r->fetch_assoc()) {

            echo json_encode(array(
                'status' => true,
                'code' => "",
                'is_code' => true,
                'username' => $device['username'],
                'password' => $device['password'],
                'is_logged' => true,
                'error_message' => ''
            ));
            exit;
        }

    } else {
        print_code($row['code']);
    }

}


$md5 = md5(rand(1,9999999).time());
$code = strtoupper(substr($md5,0,8));
$time = time();


$DB->query("DELETE FROM iptv_app_access_code WHERE exp_time < unix_timestamp() ");

$r=$DB->query("INSERT INTO iptv_app_access_code (code,uuid,create_time,exp_time) 
        VALUES('$code','$uuid',unix_timestamp(),unix_timestamp() + (60*10) )");

print_code($code);