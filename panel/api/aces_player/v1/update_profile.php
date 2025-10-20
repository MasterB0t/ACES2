<?php

require 'main.php';

$profile_id = (int)$_GET['profile_id'];
$name = $DB->escape_string($_GET['profile_name']);
$avatar = $DB->escape_string($_GET['profile_avatar']);

if($profile_id) {
    $r=$DB->query("SELECT id FROM iptv_app_profiles WHERE id = $profile_id AND account_id = '{$ACCOUNT['id']}'");
    if(!$r->fetch_assoc()) set_error("Cannot update profile. Profile do not exist.");
    else $DB->query("UPDATE iptv_app_profiles SET name = '$name', avatar = '$avatar' WHERE id = $profile_id");
} else {
    $DB->query("INSERT INTO iptv_app_profiles (name,avatar,account_id) VALUES('$name','$avatar','{$ACCOUNT['id']}')");
}

set_completed();