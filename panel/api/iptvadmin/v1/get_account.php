<?php 

$ERR[401] = "No account found.";

include 'auth.php';

if(!empty($_GET['account_id'])) { 
    
    if(!is_numeric($_GET['account_id']) || $_GET['account_id'] < 1 ) set_error(202);
    $r=$ACES->query("SELECT p.name as pack_name,d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,date_format(d.subcription,'%M %d, %Y, %l:%i %p') as expire_on FROM iptv_devices d INNER JOIN iptv_bouquet_packages p ON p.id = d.package_id WHERE d.id = '{$_GET['account_id']}' ");
    
} else if(!empty($_GET['account_token'])) { 
    
    $token = $ACES->escString($_GET['account_token']);
    $r=$ACES->query("SELECT p.name as pack_name,d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,date_format(d.subcription,'%M %d, %Y, %l:%i %p') as expire_on FROM iptv_devices d INNER JOIN iptv_bouquet_packages p ON p.id = d.package_id WHERE d.token = '$token' ");
    
} else if(!empty($_GET['account_mac'])) { 
    
   if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $_GET['account_mac'] )) set_error(202);
   $mac = strtoupper($_GET['account_mac']);
           
    $r=$ACES->query("SELECT p.name as pack_name,d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,date_format(d.subcription,'%M %d, %Y, %l:%i %p') as expire_on FROM iptv_devices d INNER JOIN iptv_bouquet_packages p ON p.id = d.package_id WHERE d.mag = '$mac' ");
    
} else set_error(202);


if(!$row=mysqli_fetch_array($r)) set_error(401);

$data['account_id'] = $row['id'];
$data['account_name'] = $row['name'];
$data['account_token'] = $row['token'];
if(!empty($row['mag'])) $data['account_mac'] = $row['mag'];
$data['package_id'] = $row['package_id'];
$data['package'] = $row['pack_name'];
$data['connections'] = $row['limit_connections'];
$data['expire_on'] = $row['expire_on'];
$data['expiration_date'] = $row['subcription'];



set_complete($data);