<?php

include 'main.php';

$vod_id = (int)$_GET['vod_id'];

if(!$vod_id)
    set_error("NO VALID VOD ID OR MISSING");

$r=$DB->query("SELECT id FROM iptv_ondemand WHERE id = '$vod_id' ");
if(!$r->fetch_assoc())
    set_error("VOD DO NOT EXIT.");


$favorited = 0;
$r=$DB->query("SELECT vod_id FROM iptv_app_favorites WHERE vod_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}'");
if(!$r->fetch_assoc()) {
    $DB->query("INSERT INTO iptv_app_favorites (vod_id,profile_id,add_time) VALUES('$vod_id','{$ACCOUNT['profile_id']}',UNIX_TIMESTAMP())");
    $favorited =1;
} else {
    $DB->query("DELETE FROM iptv_app_favorites WHERE profile_id = '{$ACCOUNT['profile_id']}' AND vod_id = '$vod_id'");
}

echo json_encode( array(
   'favorite' => $favorited,
   'vod_id'  => $vod_id
));
