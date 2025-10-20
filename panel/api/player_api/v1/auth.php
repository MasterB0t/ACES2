<?php

//header('Access-Control-Allow-Credentials: true');
//header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
//header('Access-Control-Max-Age: 1000');
//header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");

include 'main.php';

$r=$DB->query("SELECT COUNT(id) as connections FROM iptv_access WHERE limit_time > NOW() AND device_id = {$ACCOUNT['id']}  ");
$rs=$DB->query("SELECT address,port,sport,dns FROM iptv_servers WHERE id = 1 ");
$server = $rs->fetch_assoc();
$server_ip = $server['address'];
$dns = $server['dns'];
$row=mysqli_fetch_array($r);

$json['user_info']['username'] = $ACCOUNT['username'];
$json['user_info']['password'] = $ACCOUNT['token'];
$json['user_info']['message'] = $ACCOUNT['message'];
$json['user_info']['auth'] = 1;


if($ACCOUNT['expire_in'] < 1 )$json['user_info']['status'] = 'Expired';
else if($ACCOUNT['status'] != 1 ) $json['user_info']['status'] = 'Blocked';
else $json['user_info']['status'] = 'Active';


$json['user_info']['exp_date'] = (string)strtotime($ACCOUNT['subcription']);
//$json['user_info']['exp_date'] = null;

if($ACCOUNT['demo'] == 1 ) $json['user_info']['is_trial'] = "1";
else $json['user_info']['is_trial'] = "0";

$json['user_info']['active_cons'] = $row['connections'];

$json['user_info']['created_at'] = (string)strtotime($ACCOUNT['add_date']);

$json['user_info']['max_connections'] = $ACCOUNT['limit_connections'];


$json['user_info']['allowed_output_formats'] = array('m3u8','ts','rtmp');

$protocol = 'http';
//if($server['sport']) $protocol = 'https';
if( isset($_SERVER['HTTPS'] ) )  $protocol  = 'https';

if($dns) $json['server_info']['url'] =  $dns ;
else $json['server_info']['url'] =  $server_ip ;


$json['server_info']['port'] = $server['port'];
$json['server_info']['https_port'] = $server['sport'];
$json['server_info']['server_protocol'] = $protocol;
$json['server_info']['rtmp_port']= '';


if($config['timezone'])  $json['server_info']['timezone'] =$config['timezone'];
else $json['server_info']['timezone'] = date_default_timezone_get();

$json['server_info']['timestamp_now'] = time();
$json['server_info']['time_now'] = date('Y-m-d h:i:s');

//header('Content-Length: '.  mb_strlen(json_encode($json), '8bit')+100 );
echo json_encode($json);
