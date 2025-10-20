<?php

require 'main.php';

$profile_id = (int)$_GET['profile_id'];
if(!$profile_id)
    set_error("Profile do not exist.");

$r=$DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['account_id']}' AND id = $profile_id ");
if(!$r->fetch_assoc())
    set_error("Profile do not exist.");

$DB->query("UPDATE iptv_app_devices set last_profile_id = $profile_id WHERE id = '{$ACCOUNT['device_id']}' ");

set_completed();