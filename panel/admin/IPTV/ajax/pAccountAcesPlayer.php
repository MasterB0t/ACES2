<?php

if(!$AdminID=adminIsLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$ADMIN = new \ACES2\Admin();

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

try {

    switch ($_REQUEST['action']) {

        case 'connect_aces_player':

            $db = new \ACES2\DB;

            $Account = new \ACES2\Iptv\Account((int)$_POST['account_id']);
            if($Account->expire_on < 1 )
                setAjaxError("Account is expired.");

            $code = $db->escString($_REQUEST['access_code']);

            $r=$db->query("SELECT uuid FROM iptv_app_access_code WHERE code = '$code'");
            if(!$r->fetch_assoc()) {
                echo json_encode(array('error' => "This access code does not exist."));
                exit;
            } else {
                $db->query("UPDATE iptv_app_access_code  SET account_id = '$Account->id' WHERE code = '$code'");
                echo json_encode(array('status' => 1));
                exit;
            }

            break;

        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }

} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();