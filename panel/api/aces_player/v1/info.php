<?php

include 'main.php';

$r=$DB->query("SELECT COUNT(id) as connections FROM iptv_access WHERE limit_time > NOW() AND device_id = {$ACCOUNT['id']}  ");
$active_conns = $r->fetch_assoc()['connections'];

//IF THERE ARE NO PROFILE ID CREATE A DEFAULT ONE..
if(!$ACCOUNT['profile_id']) {
    $r = $DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}' LIMIT 1 ");
    if (!$ACCOUNT['profile_id'] = $r->fetch_assoc()['id']) {
        $DB->query("INSERT INTO iptv_app_profiles (account_id,name) VALUES('{$ACCOUNT['id']}','Default')");
        $ACCOUNT['profile_id'] = $r->num_rows;
    }
    $DB->query("UPDATE iptv_app_devices SET last_profile_id = '{$ACCOUNT['profile_id']}' WHERE id = {$ACCOUNT['device_id']}");
}


if($ACCOUNT['expire_in'] < 1 ) $status = 'expired';
else if($ACCOUNT['status'] != 1 ) $status = 'blocked';
else $status = 'active';

$account_info = array(
    'status' => $status,
    'expire_time' => (string)strtotime($ACCOUNT['subcription']),
    'max_connections' =>  $ACCOUNT['limit_connections'],
    'active_connections' => $active_conns,
    'profile_id' => $ACCOUNT['profile_id'],
);

echo json_encode(array("auth"=>1, "status" => 1, 'account_info'=> $account_info ));
