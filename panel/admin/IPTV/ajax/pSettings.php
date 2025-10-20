<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission()) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

$db = new \ACES2\DB();
use ACES2\IPTV\Settings;

try {

    foreach($_REQUEST['settings'] as $k => $v){

        $type = $_REQUEST['types'][$k] ? $_REQUEST['types'][$k] : 'string';

        Settings::set($k, $v, $type);
    }



} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();