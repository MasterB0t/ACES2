<?php

include_once 'index.php';

if(!$vod_id = (int)$_GET['vod_id'])
    set_error();


$DB->query("DELETE FROM iptv_app_watching WHERE vod_id = '$vod_id' AND profile_id = '{$ACCOUNT['profile_id']}' ");

set_completed();