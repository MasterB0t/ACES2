<?php

error_log("UNWATCHED");

include_once 'main.php';


if(!$vod_id = (int)$_GET['vod_id'])
    set_error();


error_log($vod_id);

$DB->query("DELETE FROM iptv_app_watching WHERE vod_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");


echo json_encode(array('status'=> 1));