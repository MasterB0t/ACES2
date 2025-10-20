<?php

use ACES2\IPTV\StreamProfile;

$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}

if(!\ACES2\Armor\Armor::isToken("iptv.stream_profile", $_REQUEST['token']))
    setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

try {

    switch ($_REQUEST['action']) {

        case 'add_stream_profile':
            \ACES2\IPTV\StreamProfile::create($_REQUEST);
            break;

        case 'update_stream_profile':
            $StreamProfile = new \ACES2\IPTV\StreamProfile($_REQUEST['id']);
            $StreamProfile->update($_REQUEST);
            break;
        case 'remove_stream_profile':
            $StreamProfile = new \ACES2\IPTV\StreamProfile($_REQUEST['id']);
            $StreamProfile->remove();
            break;

        default :
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
    }

}catch(\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();