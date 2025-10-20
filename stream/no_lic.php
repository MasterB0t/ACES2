<?php

include 'config.php';

$data = json_decode($_GET['data'],1);
if($data['api_token'] != $API_TOKEN ) die;

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ error_log('COULD NOT CONNECT TO DATABASE');  die(); }

header('ACES_NO_LIC: 1');

$resp['ACES_NO_LIC'] = 1;
$resp['errors'] = 1;
$resp['error_msg'] = "No license found on this server or it's expired.";

echo json_encode($resp);