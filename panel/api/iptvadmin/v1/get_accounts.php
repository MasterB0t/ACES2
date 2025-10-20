<?php

set_time_limit ( 0 );

include_once 'auth.php';


$r=$ACES->query("SELECT p.name as pack_name,d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,date_format(d.subcription,'%M %d, %Y, %l:%i %p') as expire_on FROM iptv_devices d INNER JOIN iptv_bouquet_packages p ON p.id = d.package_id ");

$data=array();
while($row=mysqli_fetch_array($r)) { 
    
    $d=null;
    $d['account_id'] = $row['id'];
    $d['account_name'] = $row['name'];
    $d['account_token'] = $row['token'];
    if(!empty($row['mag'])) $d['account_mac'] = $row['mag'];
    $d['package_id'] = $row['package_id'];
    $d['package'] = $row['pack_name'];
    $d['connections'] = $row['limit_connections'];
    $d['expire_on'] = $row['expire_on'];
    $d['expiration_date'] = $row['subcription'];
    
    $data[] = $d;
    
}

set_complete($data);