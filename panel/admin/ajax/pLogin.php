<?php
$USER = new \ACES\USER;
setAjaxError("No Login here");

if(!defined('NO_CAPTCHA')) {
    if(empty($_SESSION['captcha']) || strtoupper($_POST['captcha']) !== $_SESSION['captcha']) {
        $_SESSION['captcha']='';
        setAjaxError("Error resolving captcha. Try again.");
    } }

if(!\ACES2\Armor\Armor::isToken('login', $_POST['token']))
    setAjaxError("Invalid or expired session");

try {
    $USER->login($_POST['username_or_email'],$_POST['password']);
} catch (\Exception $exp) {
    \ACES2\Armor\Armor::log_ban('login');
    setAjaxError($exp->getMessage());
}

echo json_encode(array('status'=>1));