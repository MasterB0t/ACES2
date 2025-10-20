<?php

use ACES2\IPTV\BouquetPackage;

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_BOUQUETS)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

try {
    switch($_REQUEST['action']) {

        case 'add_bouquet':
            $Bouquet = \ACES2\IPTV\Bouquet::add($_REQUEST['name']);
            if($_REQUEST['ignore_in_bouquet'] == 0 ) {
                $Bouquet->addStreams($_REQUEST['streams']);
                $Bouquet->addVods($_REQUEST['vods']);
            }

            break;

        case 'remove_bouquet':
            $Bouquet = new \ACES2\IPTV\Bouquet($_REQUEST['bouquet_id']);
            $Bouquet->remove();
            break;

        case 'update_bouquet':
            $Bouquet = new \ACES2\IPTV\Bouquet($_REQUEST['bouquet_id']);
            $Bouquet->update($_REQUEST['name']);
            if($_REQUEST['ignore_in_bouquet'] == 0 ) {
                $Bouquet->addStreams($_REQUEST['streams']);
                $Bouquet->addVods($_REQUEST['vods']);
            }
            break;

        case 'add_package':
            BouquetPackage::add($_REQUEST);
            break;

        case 'update_package':
            $Package = new \ACES2\IPTV\BouquetPackage($_REQUEST['package_id']);
            $Package->update($_REQUEST);
            break;

        case 'remove_package':
            $Package = new \ACES2\IPTV\BouquetPackage($_REQUEST['package_id']);
            $Package->remove();
            break;

        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();