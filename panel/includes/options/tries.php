<?php


$OPTIONS['tries']['login']['tries'] = 10;
$OPTIONS['tries']['login']['limit_time'] = 60 * 60;
$OPTIONS['tries']['login']['ban_time'] = 60 * 60 * 1;

$OPTIONS['tries']['register']['tries'] = 5;
$OPTIONS['tries']['register']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['register']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['contact']['tries'] = 5;
$OPTIONS['tries']['contact']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['contact']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['message']['tries'] = 5;
$OPTIONS['tries']['message']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['message']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['admin_login']['tries'] = 10;
$OPTIONS['tries']['admin_login']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['admin_login']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['reset_password']['tries'] = 10;
$OPTIONS['tries']['reset_password']['limit_time'] = 60 * 60 * 5;
$OPTIONS['tries']['reset_password']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['resend_validation']['tries'] = 3;
$OPTIONS['tries']['resend_validation']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['resend_validation']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['iptv_api_status']['tries'] = 20;
$OPTIONS['tries']['iptv_api_status']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['iptv_api_status']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['iptv_streams']['tries'] = 25;
$OPTIONS['tries']['iptv_streams']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['iptv_streams']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['iptv_panel']['tries'] = 20;
$OPTIONS['tries']['iptv_panel']['limit_time'] = 60 * 60 * 24;
$OPTIONS['tries']['iptv_panel']['ban_time'] = 60 * 60 * 24;

$OPTIONS['tries']['iptv_swap']['tries'] = 100;
$OPTIONS['tries']['iptv_swap']['limit_time'] = 45;
$OPTIONS['tries']['iptv_swap']['ban_time'] = 60 * 60 * 1;

