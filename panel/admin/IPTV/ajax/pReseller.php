<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS_FULL)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

try {
    switch($_REQUEST['action']) {

        case 'add_reseller':
            $Reseller = \ACES2\IPTV\Reseller2::add(
                $_REQUEST['username'],
                $_REQUEST['password'],
                $_REQUEST['email'],
                $_REQUEST['name']);

            $credits=(int)$_REQUEST['credits'];
            if($credits > 0 ) {
                $Reseller->setCredits($credits);
                \ACES2\IPTV\CreditLog::admin_to_reseller(
                    $ADMIN->id,
                    $Reseller->id,
                    $credits
                );
            }

            if((int)$_REQUEST['reseller_of'])
                $Reseller->setOwner($_REQUEST['reseller_of']);

            $Reseller->setCanAddResellers(
                (bool)$_REQUEST['can_add_resellers']
            );

            $Reseller->setCanOverridePackage(
                (bool)$_REQUEST['can_override_package']
            );

            $Reseller->setAllowChannelList(
                (bool)$_REQUEST['allow_channel_list']
            );

            $Reseller->setAllowVodList(
                (bool)$_REQUEST['allow_vod_list']
            );

            $Reseller->setAllowAccountUsername((bool)$_REQUEST['set_account_username']);
            $Reseller->setAllowAccountPassword((bool)$_REQUEST['set_account_password']);
            $Reseller->setAllowRestartStreas((bool)$_REQUEST['allow_restart_streams']);

            $Reseller->setOverridePackage(
                $_REQUEST['official_credits'],
                $_REQUEST['trial_credits'],
                $_REQUEST['enabled']);

            break;

        case 'update_reseller':
            $Reseller = new \ACES2\IPTV\Reseller2((int)$_REQUEST['id']);

            $Reseller->setName($_REQUEST['name']);
            $Reseller->setUsername($_REQUEST['username']);
            $Reseller->setEmail($_REQUEST['email']);
            if(!empty($_REQUEST['password']))
                $Reseller->setPassword($_REQUEST['password']);

            $Reseller->setStatus($_REQUEST['status']);

            $Reseller->setOwner($_REQUEST['reseller_of']);

            $Reseller->setCanAddResellers(
                (bool)$_REQUEST['can_add_resellers']
            );

            $Reseller->setCanOverridePackage(
                (bool)$_REQUEST['can_override_package']
            );

            $Reseller->setAllowChannelList(
                (bool)$_REQUEST['allow_channel_list']
            );

            $Reseller->setAllowVodList(
                (bool)$_REQUEST['allow_vod_list']
            );

            $Reseller->setAllowAccountUsername((bool)$_REQUEST['set_account_username']);
            $Reseller->setAllowAccountPassword((bool)$_REQUEST['set_account_password']);
            $Reseller->setAllowRestartStreas((bool)$_REQUEST['allow_restart_streams']);

            $Reseller->setOverridePackage(
                $_REQUEST['official_credits'],
                $_REQUEST['trial_credits'],
                $_REQUEST['enabled']);

            if($credits=(int)$_REQUEST['credits'] < 0)
                setAjaxError("Invalid credits value.");


            $diff_credits =  (int)$_REQUEST['credits'] - $Reseller->getCredits();
            if($diff_credits != 0 ) {
                $Reseller->setCredits((int)$_REQUEST['credits']);
                \ACES2\IPTV\CreditLog::admin_to_reseller($ADMIN->id, $Reseller->id, $diff_credits);

                if($diff_credits>0)
                    \ACES2\UserNotification::sendNotification($Reseller->id, 'iptv_credits',
                        "$diff_credits credits have been added to your account.");
            }


            break;

//        case 'remove_reseller':
//            $EditReseller = new \ACES2\IPTV\Reseller($_REQUEST['id']);
//            if($EditReseller->remove($_REQUEST['set_accounts_to'], $_REQUEST['set_resellers_to'])) {
//                $ADMIN->addLog("Remove User #$EditReseller->id, $EditReseller->username ");
//                $CreditLog = new \ACES2\IPTV\CreditLog();
//                $CreditLog->admin_remove_reseller($ADMIN->id,$EditReseller->id,$EditReseller->credits);
//            }
//            break;

        case 'mass_remove_resellers':
            $to_delete = explode(",",$_REQUEST['ids']);

            if(in_array($_REQUEST['set_accounts_to'], $to_delete))
                setAjaxError("Cannot set account to a reseller to going to be removed.");

            if(in_array($_REQUEST['set_resellers_to'], $to_delete))
                setAjaxError("Cannot set owner to a reseller to going to be removed.");

            foreach($to_delete as $id ) {
                $reseller = new \ACES2\IPTV\Reseller($id);
                if($reseller->remove($_REQUEST['set_accounts_to'], $_REQUEST['set_resellers_to'])) {
                    $ADMIN->addLog("Remove User #$reseller->id, $reseller->username ");
                    $CreditLog = new \ACES2\IPTV\CreditLog();
                    $CreditLog->admin_remove_reseller($ADMIN->id,$reseller->id,$reseller->credits);
                }
            }
            break;

        case 'mass_update_resellers':
            foreach(explode(",",$_REQUEST['ids']) as $id ) {
                $reseller = new \ACES2\IPTV\Reseller($id);

                $post['username'] = $reseller->username;
                $post['email'] = $reseller->email;
                $post['name'] = $reseller->name;


                $post['can_add_resellers'] = $_REQUEST['can_add_resellers'] !== ''
                    ? (int)$_REQUEST['can_add_resellers']
                    : $reseller->can_add_resellers;


                $post['credits'] = isset($_REQUEST['credits']) ?
                    (int)$_REQUEST['credits'] : $reseller->credits;


                $post['reseller_of'] = $_REQUEST['reseller_of'] !== ''
                    ? (int)$_REQUEST['reseller_of']
                    : $reseller->reseller_of;

                $post['enabled'] = $_REQUEST['enabled'] != '' ?
                    (int)$_REQUEST['enabled']
                    : $reseller->status;

                $reseller->update($post);
                if($_REQUEST['override_package'])
                    $reseller->setOverridePackage($_REQUEST['official_credits'], $_REQUEST['trial_credits']);
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