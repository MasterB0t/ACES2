<?php

unset($_GET['action']);
require_once ("index.php");

$profiles = [];
$r = $DB->query("SELECT id,name,avatar FROM iptv_app_profiles WHERE account_id = {$ACCOUNT['id']}");
while($row=$r->fetch_assoc())  {
    $row['avatar'] = $row['avatar'] ?: 'profile1';
    $profiles[] = $row;
}


echo json_encode(array('status'=> true,'profiles'=>$profiles));
