<?php


$ERR[301] = "You don't have enough credits.";
$ERR[302] = "Please enter a valid mac.";
$ERR[303] = "Enter a valid adult pin"; 
$ERR[304] = "Enter a name for the account.";
$ERR[305] = "Invalid or no package have been selected for the new account.";
$ERR[306] = "This package is not allowed for trial accounts.";
$ERR[307] = 'This package have no official duration time.';
$ERR[308] = 'This mac is already in used.';

include_once 'auth.php';

//ACCOUNT NAME
$name='';
if(!empty($_GET['name'])) $name = $ACES->escString($_GET['name']);

if(empty($_GET['package']) || !is_numeric($_GET['package']) || $_GET['package'] < 1 ) set_error(305);
else {

    if(!$rp=$ACES->query("SELECT name,bouquets,trial_duration,trial_duration_in,official_duration,official_duration_in,max_connections FROM iptv_bouquet_packages where id = '{$_GET['package']}' ")) set_error(101);
    if(!$pack_info=mysqli_fetch_array($rp)) set_error(305);
    
    $TRIAL = 0;
    if(isset($_GET['is_trial'])) { 
        if($pack_info['trial_duration'] < 1 ) set_error(306); 
        $TRIAL = 1;
    } else if($pack_info['official_duration'] < 1 ) set_error(307); 
        
}

$MAC = '';
if(isset($_GET['mac'])) { 
    
    if (strlen($_GET['mac']) != 17)  set_error(302);
    else if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $_GET['mac'] )) set_error(302);
    else {
        $MAC = strtoupper($_GET['mac']);
        if(!$rm=$ACES->query("SELECT id FROM iptv_devices WHERE mag = '$MAC' ")) set_error(100);
        if(mysqli_fetch_array($rm)) set_error(308); 
    }
        
}

$PIN = '0000';
if(isset($_GET['adult_pin'])) {
    if(!is_numeric($_GET['adult_pin'])) set_error(202);
    else if(strlen($_GET['adult_pin']) != 4 ) set_error(202);
    $PIN = $_GET['adult_pin'];
}

$user_id=0;

$token = substr(md5(rand(100, 999999) . time() . rand(100, 999999)), 0, 12);

if(isset($_GET['is_trial'])) 
    $INTERV = "INTERVAL {$pack_info['trial_duration']} {$pack_info['trial_duration_in']} ";
else 
    $INTERV = "INTERVAL {$pack_info['official_duration']} {$pack_info['official_duration_in']} ";
    
if(!$ACES->query("INSERT INTO iptv_devices (name,status,package_id,bouquets,demo,user_id,limit_connections,adults,adults_with_pin,pin,hide_vods_from_playlist,token,mag,lock_ip_address,lock_user_agent,add_date,subcription) VALUES('$name',1,{$_GET['package']},'{$pack_info['bouquets']}',$TRIAL,$user_id,'{$pack_info['max_connections']}','1','1','$PIN','0','$token','$MAC','','',NOW(),NOW() + $INTERV) ")) set_error(100);

$account_id = mysqli_insert_id($ACES->db_conn);   

if(empty($name))
    $ACES->query("UPDATE iptv_devices SET name = 'ACCOUNT #$account_id' WHERE id = $account_id ");

$r=$ACES->query("SELECT p.name as pack_name,d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,date_format(d.subcription,'%M %d, %Y, %l:%i %p') as expire_on FROM iptv_devices d INNER JOIN iptv_bouquet_packages p ON p.id = d.package_id WHERE d.id = '$account_id' ");

$row=mysqli_fetch_array($r);
    
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