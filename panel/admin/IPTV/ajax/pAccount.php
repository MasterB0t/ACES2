<?php

use ACES2\IPTV\Account;
use ACES2\IPTV\AdminPermissions;
use ACES2\IPTV\MagDevice;

if(!$AdminID=adminIsLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$ADMIN = new \ACES2\Admin();


try {
    switch($_REQUEST['action']) {

        case 'add_account':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $Account = \ACES2\IPTV\Account::add($_REQUEST);
            $Account->setAddByAdmin($ADMIN->id);
            break;

        case 'remove_account':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            if(!is_array($_REQUEST['ids']))
                $ids[] = $_REQUEST['ids'];
            else
                $ids = $_REQUEST['ids'];

            foreach($ids as $id) {
                if((int)$id ){
                    $Account = new Account($id);
                    $Account->remove();
                    $ADMIN->addLog("IPTV Account removed #$Account->id:$Account->username, $Account->name");
                }
            }
            break;

        case 'update_account':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $Account = new Account($_REQUEST['id']);
            $Account->update($_REQUEST);
            $ADMIN->addLog("IPTV Account Updated #$Account->id:$Account->username, $Account->name");

            if($Account->mac_address) {
                $MagAccount = new \ACES2\IPTV\MagDevice($Account->id);

                $stream_format = $_REQUEST['stream_format'] == 'm3u8' ? 'm3u8' : 'ts';
                $MagAccount->setStreamFormat($stream_format);
                if(isset($_REQUEST['theme']))
                    $MagAccount->setTheme($_REQUEST['theme']);
                $MagAccount->setPlayInPreviewByOk((bool)$_REQUEST['play_in_preview_by_ok']);

                if(is_array($_REQUEST['favorites_videos']))
                    $MagAccount->setFavoritesVideos($_REQUEST['favorites_videos']);

                if(is_array($_REQUEST['favorites_streams']))
                    $MagAccount->setFavoritesStreams($_REQUEST['favorites_streams']);

                $MagAccount->save();

                if(!empty($_REQUEST['reload_portal']))
                    $MagAccount->sendEvent('reload_portal');
            }

            break;

        case 'mag_event':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_ACCOUNT)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $Account = new Account((int)$_REQUEST['account_id']);
            $Account->sendMagEvent($_REQUEST['event'], $_REQUEST['message'],(bool)$_REQUEST['reboot_on_confirm']);
            break;

        case 'update_mag':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $MagAccount = new \ACES2\IPTV\MagDevice((int)$_REQUEST['account_id']);
            $MagAccount->setStreamFormat($_REQUEST['stream_format']);
            if(isset($_REQUEST['theme']))
                $MagAccount->setTheme($_REQUEST['theme']);
            $MagAccount->setPlayInPreviewByOk((bool)$_REQUEST['play_in_preview_by_ok']);

            if(is_array($_REQUEST['favorites_videos']))
                $MagAccount->setFavoritesVideos($_REQUEST['favorites_videos']);

            if(is_array($_REQUEST['favorites_streams']))
                $MagAccount->setFavoritesStreams($_REQUEST['favorites_streams']);

            $MagAccount->save();

            if(!empty($_REQUEST['reload_portal']))
                $MagAccount->sendEvent('reload_portal');

            break;

        case 'reset_mag':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_ACCOUNT)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $db = new \ACES2\DB();
            $id = (int)$_REQUEST['id'];
            $db->query("DELETE FROM iptv_mag_devices WHERE account_id = '$id' ");
            $db->query("DELETE FROM iptv_mag_devices WHERE account_id = '$id' ");
            break;

        case 'send_fingerprint':

            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_ACCOUNT)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $Account = new Account($_REQUEST['id']);
            break;

        case 'mass_update_account':
            if(!$ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
            }

            $ids = explode(",",$_REQUEST['ids']);
            foreach( $ids as $id) {
                $account = new Account($id);
                try {
                    if(!empty($_REQUEST['status']))
                        $account->setStatus($_REQUEST['status']);
                    if(!empty($_REQUEST['set_pin']))
                        $account->setPin($_REQUEST['pin']);
                    if((int) $_REQUEST['owner_id'])
                        $account->setOwner($_REQUEST['owner_id']);
                    if(!empty($_REQUEST['set_limit_connections']))
                        $account->setLimitConnections($_REQUEST['limit_connections']);
                    if($_REQUEST['bouquet_package'] == '0')
                        $account->setBouquets($_REQUEST['bouquets']);
                    else if((int) $_REQUEST['bouquet_package'])
                        $account->setPackage($_REQUEST['bouquet_package']);
                    $account->save();
                } catch(\Exception $e) {
                    $ignore = true;
                }

            }

            break;

        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    error_log($e->getMessage());
    setAjaxError($e->getMessage());
}

setAjaxComplete();