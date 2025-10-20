<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST");

header('Content-Type: application/json');
header('Connection: close');

include_once 'err.php';
include_once '/home/aces/panel/includes/init.php';

define("NO_SESSION",1);

$ACES = new IPTV();
$JSON = array();

if($ACES->isBlock('admin_login')) set_error(201);

if(empty($_GET['username']) || empty($_GET['password'])) set_error(201);
else if(!preg_match('/^[a-zA-Z0-9-]+$/',$_GET['username'])) set_error(201);

$u=$ACES->escString($_GET['username']);
$p=md5($_GET['password']);
if(!$r=$ACES->query("SELECT id FROM admins WHERE username = '$u' AND password = '$p' AND full_admin = 1 ")) set_error(100);
if(!$ADMIN_ID=mysqli_fetch_array($r)['id']) { 
    $ACES->action('admin_login');
    set_error(201);
}