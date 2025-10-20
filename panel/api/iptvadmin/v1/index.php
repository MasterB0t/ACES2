<?php
include_once 'auth.php';

foreach($_POST as $i => $v ){ if(!empty($_POST[$i])) $_GET[$i] = $v; }

if(isset($_GET['action']) && is_file("{$_GET['action']}.php") && $_GET['action'] != 'index' ) include_once "{$_GET['action']}.php";
else include_once 'auth.php';