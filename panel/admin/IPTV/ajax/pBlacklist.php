<?php
$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission('')) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}

switch ($_REQUEST['action']) {

    case 'blacklist_ip':
        if(\ACES2\IPTV\Blacklist::addIpBlacklist($_REQUEST['ip_address']))
            $ADMIN->addLog("Blacklisted IP-Address '{$_REQUEST['ip_address']}' ");

        break;

    case 'blacklist_useragent':
        if(\ACES2\IPTV\Blacklist::addUserAgentBlacklist($_REQUEST['user_agent']))
            $ADMIN->addLog("Blacklisted IP-Address '{$_REQUEST['user_agent']}' ");

        break;



}