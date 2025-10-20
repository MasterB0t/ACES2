<?php

//header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST,PUT");

chdir('/home/aces/panel/api/player_api/v1/');

foreach($_POST as $i => $v ){ if(!empty($_POST[$i])) $_GET[$i] = $v; }

//define("NO_SESSION",true);
//include_once "/home/aces/panel/includes/init.php";


if(isset($_GET['action'])) include "/home/aces/panel/api/player_api/v1/{$_GET['action']}.php";
else include "/home/aces/panel/api/player_api/v1/auth.php";