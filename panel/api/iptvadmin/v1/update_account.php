<?php

$ERR[501] = "No account found.";

$ERR[502] = "Please enter a valid mac.";
$ERR[503] = "Enter a valid adult pin"; 
$ERR[504] = "Enter a name for the account.";
$ERR[505] = "Invalid or no package have been selected for the new account.";
$ERR[506] = "This package is not allowed for trial accounts.";
$ERR[507] = 'This package have no official duration time.';
$ERR[508] = 'This mac is already in used.';

include_once 'auth.php';

if(empty($_GET['account_id']) || !is_numeric($_GET['account_id']) || $_GET['account_id'] < 1 ) set_error(202);

$r=$ACES->query("SELECT d.id,d.name,d.token,d.limit_connections,d.pin,d.status,d.package_id,d.mag,d.subcription,bouquets FROM iptv_devices d  WHERE d.id = '{$_GET['account_id']}' ");
if(!$ACCOUNT=mysqli_fetch_array($r)) set_error(501);

$update = [];

if(!empty($_GET['name']))
    $update['name'] = $ACES->escString($_GET['name']);

if(!empty($_GET['set'])) { 

    if($_GET['set'] == 'expired') $update['subcription'] = " NOW() + INTERVAL 1 SECOND ";
    else if($_GET['set'] == 'block' ) $update['status'] = 3;
    else if($_GET['set'] == 'disable') $update['status'] = 2; 
    else if($_GET['set'] == 'enable') $update['status'] = 1;
    else set_error(202);
    
}


if(!empty($_GET['package']) && $_GET['set'] != 'expired') { 
    if( !is_numeric($_GET['package']) || $_GET['package'] < 1 ) set_error(505);
    
    if(!$rp=$ACES->query("SELECT name,bouquets,trial_duration,trial_duration_in,official_duration,official_duration_in,max_connections FROM iptv_bouquet_packages where id = '{$_GET['package']}' ")) set_error(101);
    if(!$pack_info=mysqli_fetch_array($rp)) set_error(505);
    
    $TRIAL = 0;
    if(isset($_GET['is_trial'])) { 
        if($pack_info['trial_duration'] < 1 ) set_error(506); 
        $TRIAL = 1;
        $update['demo'] = 1;
    } else if($pack_info['official_duration'] < 1 ) set_error(507); 
    else $update['demo'] = 0;
    
    $update['package_id'] = $_GET['package'];
    $update['bouquets'] = $pack_info['bouquets'];
    $update['limit_connections'] = $pack_info['max_connections'];
    
    $c_b = unserialize($pack_info['bouquets']);
    $d_b = unserialize($ACCOUNT['bouquets']);

    asort($c_b);
    asort($d_b);
    
    $no_reset = 0;
    if($c_b == $d_b && $ACCOUNT['limit_connections'] == $pack_info['max_connections']) $no_reset=1;
    
}

if(isset($_GET['mac'])) { 
    
    if (strlen($_GET['mac']) != 17)  set_error(502);
    else if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $_GET['mac'] )) set_error(502);
    else {
        $MAC = strtoupper($_GET['mac']);
        if(!$rm=$ACES->query("SELECT id FROM iptv_devices WHERE mag = '$MAC' AND id != '{$_GET['account_id']}' ")) set_error(100);
        if(mysqli_fetch_array($rm)) set_error(508); 
        $update['mag'] = strtoupper($_GET['mac']);
    }
        
}
    
if(isset($_GET['adult_pin'])) {
    if(!is_numeric($_GET['adult_pin'])) set_error(202);
    else if(strlen($_GET['adult_pin']) != 4 ) set_error(202);
    $update['pin'] = $_GET['adult_pin'];
}

foreach ($update as $field => $data)
    $update_sqls[] = "$field = '" . $data . "' ";
$sql = implode(', ', $update_sqls);

if($sql) {
    if(!$ACES->query("UPDATE iptv_devices SET $sql WHERE id = '{$_GET['account_id']}' ")) set_error(100);
    if(!empty($_GET['mac'])) $ACES->query("DELETE FROM iptv_mag_devices WHERE account_id = '{$_GET['account_id']}' ");
    
    
    if(!empty($_GET['package']) && $_GET['set'] != 'expired' ) {
        
        if($TRIAL) $slq_sub = "subcription = NOW() + INTERVAL {$pack_info['trial_duration']} {$pack_info['trial_duration_in']}";

        else if($ACCOUNT['demo']) {

            $slq_sub = "subcription = NOW() + INTERVAL {$pack_info['official_duration']} {$pack_info['official_duration_in']}";

        } else if (strtotime($ACCOUNT['subcription']) > time() && $no_reset == 1 ) {

           $slq_sub = "subcription = DATE_ADD(subcription, INTERVAL {$pack_info['official_duration']} {$pack_info['official_duration_in']}) ";

        } else {
            $slq_sub = "subcription = NOW() + INTERVAL {$pack_info['official_duration']} {$pack_info['official_duration_in']} ";
        }
        
        if(!$ACES->query("UPDATE iptv_devices SET $slq_sub WHERE id = '{$_GET['account_id']}' ")) set_error(100);
        
    }

}
    
set_complete();