<?php 


if($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {  http_response_code(404); die; } 

include_once 'config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ error_log("RTMP AUTH: Could not connect to database."); http_response_code(402); die(); }

$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.rtmp_auth_key' ");
if(!$KEY=mysqli_fetch_array($r)['value']) { http_response_code(404); die(); }
if($KEY == '' ) { error_log("RTMP AUTH: Key have not been set."); http_response_code(404); die(); }

if($KEY != $_GET['key'] ) { http_response_code(404); die(); }

http_response_code(201);
