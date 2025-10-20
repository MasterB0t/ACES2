<?php

include 'main.php';

$profiles = [];
$r = $DB->query("SELECT id,name,avatar FROM iptv_app_profiles WHERE account_id = {$ACCOUNT['id']}");
while($row=$r->fetch_assoc()) $profiles[] = $row;



echo json_encode($profiles);