<?php

$Admin = new \ACES2\Admin();

if(!$Admin->isLogged(false)) {
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
}

if(!$Admin->hasPermission('')) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

if(!\ACES2\Armor\Armor::isToken('iptv.admin', $_REQUEST['token']))
    setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

try {
    switch($_REQUEST['action']) {
        case 'add_admin':
            \ACES2\Admin::create($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['email'],
                $_REQUEST['name'], (int)$_REQUEST['group_id'] );
            break;

        case 'update_admin':
            $admin = new \ACES2\Admin((int)$_REQUEST['id']);
            $admin->update($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['email'],
                $_REQUEST['name'], (int)$_REQUEST['group_id']);
            break;

        case 'remove_admin':
            $admin = new \ACES2\Admin((int)$_REQUEST['id']);
            $admin->remove();
            break;

        case 'add_admin_group':
            ACES2\AdminGroup::add($_REQUEST['name'], $_REQUEST['permission']);
            break;

        case 'update_admin_group':
            $Group = new \ACES2\AdminGroup((int)$_REQUEST['id']);
            $Group->update($_REQUEST['name'], $_REQUEST['permission']);
            break;

        case 'remove_admin_group':
            $Group = new \ACES2\AdminGroup((int)$_REQUEST['id']);
            $Group->remove();
            break;


        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);

    }
} catch(Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();
