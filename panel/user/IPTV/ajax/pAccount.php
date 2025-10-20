<?php

if(!$UserID=userIsLogged()) {
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
}

$User = new \ACES2\IPTV\Reseller2($UserID);

$json =[];

use ACES2\IPTV\Account;
use ACES2\IPTV\Settings;

switch($_REQUEST['action']) {

    case 'get_package':
        $pack_id = (int)$_REQUEST['package_id'];
        $Package = new \ACES2\IPTV\BouquetPackage($pack_id);

        $json = array(
            'allow_mag' => $Package->allow_mag,
            'only_mag' => 0
        );
        break;

    case 'new_account':

        $resellers = $User->getResellers();

        $owner = in_array($_REQUEST['owner_id'], $resellers) ? (int)$_REQUEST['owner_id'] : $User->id;

        if(!$package_id = (int)$_REQUEST['package_id']  )
            setAjaxError("Package is required");

        $Package = $User->getBouquetPackage($package_id);
        if(!$Package)
            setAjaxError("Package could not be found");


        $credit_amount = (int)$_REQUEST['trial'] ? $Package->trial_credits : $Package->official_credits ;

        $remaining_credits = $User->getCredits() - $credit_amount;

        if($remaining_credits < 0 )
            setAjaxError("No enough credits");

        $db = new \ACES2\DB;

        $post = array(
            'name' => $db->escString($_REQUEST['name']),
            'owner_id' => (int)$owner,
            'package_id' => 0,
            'reseller_notes' => (string)$_REQUEST['reseller_notes'],
            'mac_address' => (string)$_REQUEST['mac_address'],
        );


        if(!empty($_REQUEST['pin']) ) {
            if (!preg_match('/^[0-9]{4}+$/', $_REQUEST['pin']))
                setAjaxError("Pin must be a numeric value with 4 characters long.");
            $post['pin'] = $_REQUEST['pin'];
        }


        if(!empty($_REQUEST['username']) && $User->isAllowAccountUsername()) {
            if(strlen(trim($_REQUEST['username'])) < 6)
                setAjaxError("Username must be at least 6 characters.");
            $post['username'] = $_REQUEST['username'];
        }

        if( !empty($_REQUEST['password']) && $User->isAllowAccountPassword()) {
            if(strlen(trim($_REQUEST['password'])) < 6)
                setAjaxError("Password must be at least 6 characters.");
            $post['password'] = $_REQUEST['password'];
        }

        $Account =  \ACES2\IPTV\Account::add(
            $post,
        );

        $Account->setAddByUser($User->id);
        $Account->setPackage($package_id);

        $expire = $_POST['trial'] == 1 ?
            $Package->getTrialExpireInDate() :
            $Package->getOfficialExpireInDate();

        $Account->setExpireOn($expire);


        if($_REQUEST['trial'])
            $Account->setTrial(1);

        $Account->save();
        $User->setCredits($remaining_credits);

        \ACES2\IPTV\CreditLog::reseller_add_account(
            $User->id, $Account->id, $package_id, (bool)$_REQUEST['trial'], $credit_amount, $remaining_credits);

        $Account->setStatus(1);
        $Account->save();

        break;

    case 'update_account':

        $Account = new \ACES2\IPTV\Account((int)$_REQUEST['account_id']);

        if(!$User->canManageAccount($Account->id))
            setAjaxError("Account couldn't not be found.");

        if($Account->status == Account::STATUS_BLOCKED)
            setAjaxError("Account cannot be updated");

        $db = new \ACES2\DB();
        $Account->name = $db->escString($_REQUEST['name']);

        $CurrentOwner = new \ACES2\IPTV\Reseller2((int)$Account->owner_id);
        if($CurrentOwner->status != \ACES2\IPTV\Reseller2::STATUS_BLOCKED) {
            $owner = in_array($_REQUEST['owner_id'], $User->getResellers() ) ? (int)$_REQUEST['owner_id'] : $User->id;
            $Account->setOwner($owner);
        } else
            logD("Owner of account #$Account->id is blocked. Owner can't not be change.");


        if( $User->isAllowAccountUsername()) {
            if(strlen(trim($_REQUEST['username'])) < 6)
                setAjaxError("Username must be at least 6 characters.");
            $Account->setUsername( $_REQUEST['username'] );
        }

        if(  $User->isAllowAccountPassword()) {
            if(strlen(trim($_REQUEST['password'])) < 6)
                setAjaxError("Password must be at least 6 characters.");
            $Account->setPassword($_REQUEST['password']);
        }

        if(!empty($_REQUEST['pin']) ) {
            if (!preg_match('/^[0-9]{4}+$/', $_REQUEST['pin']))
                setAjaxError("Pin must be a numeric value with 4 characters long.");
            $Account->setPin( $_REQUEST['pin'] );
        }

        $status = (bool)$_REQUEST['status'] ? $Account::STATUS_ACTIVE : $Account::STATUS_DISABLED;
        $Account->setStatus($status);

        $Package = new \ACES2\IPTV\BouquetPackage($Account->package_id);
        if($Package->allow_mag &&  !empty($_REQUEST['mac_address'])) {
            $Account->setMacAddress($_REQUEST['mac_address']);

            $MagAccount = new \ACES2\IPTV\MagDevice($Account->id);

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

        }


        $Account->setResellerNotes($_REQUEST['reseller_notes']);

        $Account->setAllowAdultContent((bool)$_REQUEST['allow_adult_content']);
        $Account->setAdultsWithPin((bool)$_REQUEST['adults_with_pin']);

        $Account->save();


        break;

    case 'update_account_package':

        $account_id = (int)$_REQUEST['account_id'];
        $package_id = (int)$_REQUEST['package_id'];

        $Account=  new \ACES2\IPTV\Account($account_id);


        $Resellers = $User->getResellers();
        $Resellers[] = $User->id;

        if(!$User->canManageAccount($Account->id))
            setAjaxError("Account not found.", 403);

        if($Account->status == Account::STATUS_BLOCKED)
            setAjaxError("Account cannot be updated");

        $Package = $User->getBouquetPackage($package_id);

        $credit_cost = $Package->official_credits;

        $credits = $User->getCredits() - $credit_cost;
        if($credits < 0 )
            setAjaxError("No enough credits");

        $Account->setPackage($package_id);

        $db =new \ACES2\DB;

        if($Account->expire_on > time())
            $r=$db->query("SELECT UNIX_TIMESTAMP(  subcription + INTERVAL $Package->official_duration $Package->official_duration_in )  as expire_in 
                FROM iptv_devices 
            WHERE id = $Account->id ");
        else //EXPIRED
            $r=$db->query("SELECT UNIX_TIMESTAMP(  NOW() + INTERVAL $Package->official_duration $Package->official_duration_in )  as expire_in
                FROM iptv_devices
            WHERE id = $Account->id ");

        \ACES2\IPTV\CreditLog::reseller_update_account($User->id, $Account->id, $package_id, $credit_cost , $credits );

        $Account->expire_on = $r->fetch_assoc()['expire_in'];

        $Account->setTrial(false);

        $Account->save();
        $User->setCredits($credits);

        break;

    case 'mag_event' :
        if(!$User->canManageAccount($_REQUEST['account_id']))
            setAjaxError("Account not found.");
        $Account = new \ACES2\IPTV\Account((int)$_REQUEST['account_id']);

        if($Account->status == Account::STATUS_BLOCKED)
            setAjaxError("Account is blocked!");

        $Account->sendMagEvent($_REQUEST['event'], $_REQUEST['message'],(bool)$_REQUEST['reboot_on_confirm']);
        break;

    case 'reset_mag':
        if($User->canManageAccount((int)$_REQUEST['id']))
            setAjaxError("Account not found.");

        $Account = new \ACES2\IPTV\Account((int)$_REQUEST['id']);
        if($Account->status == Account::STATUS_BLOCKED)
            setAjaxError("Account is blocked!");

        $db = new \ACES2\DB();
        $id = (int)$_REQUEST['id'];
        $db->query("DELETE FROM iptv_mag_devices WHERE account_id = '$id' ");
        $db->query("DELETE FROM iptv_mag_devices WHERE account_id = '$id' ");
        break;

    case 'mass_account_edit':

        $SubResellers = $User->getResellers();
        $SubResellers[] = $User->id;

        foreach(explode(",",$_REQUEST['ids']) as $id ) {

            $account = new \ACES2\IPTV\Account($id);

            if($User->canManageAccount((int)$id)  && $account->status != Account::STATUS_BLOCKED ) {

                if(!empty($_REQUEST['status'])) {
                    $status = $_REQUEST['status'] == '1' ?
                        \ACES2\IPTV\Account::STATUS_DISABLED :
                        \ACES2\IPTV\Account::STATUS_ACTIVE;

                    $account->setStatus($status);
                }

                if(!empty($_REQUEST['owner'])) {
                    $owner = (int)$_REQUEST['owner'];
                    if(in_array($owner, $SubResellers)) {
                        $account->setOwner($owner);
                    }
                }

                if(!empty($_REQUEST['adult_content'])) {
                    $adult = $_REQUEST['adult_content'] == 1 ? true : false;
                    $account->setAllowAdultContent( $adult );
                }

                if(!empty($_REQUEST['adult_with_pin'])) {
                    $with_pin = $_REQUEST['adult_with_pin'] == 1 ? true : false;
                    $account->setAdultsWithPin( $with_pin );
                }

                $account->save();
            }
        }


        break;

    case 'mass_account_remove':

        $db = new \ACES2\DB();

        $Reseller = new \ACES2\IPTV\Reseller2($User->id);
        $resellers = $Reseller->getResellers();
        $resellers[] = $User->id;
        $reseller_sql = implode(",",$resellers);

        $hours = (int)Settings::get(Settings::RESELLER_REFUND_HOURS);
        if($hours > 0) {
            $exp_time = time() - ($hours * 3600);
        }

        foreach($_REQUEST['ids'] as  $id ) {

            //CHECK IF USER CAN MANAGE THE ACCOUNT. PREVENT FROM HACKING OTHER ACCOUNT HE DONT OWN
            if ($Reseller->canManageAccount((int)$id)) {

                $account = new \ACES2\IPTV\Account($id);
                $owner = new \ACES2\IPTV\Reseller2((int)$account->owner_id);
                //WILL NOT REMOVE ACCOUNT WHERE THE OWNER IS BLOCKED.
                if($owner->status != \ACES2\IPTV\Reseller2::STATUS_BLOCKED) {
                    //IF REFUND IS ENABLED.
                    if($hours > 0) {
                        $r_rc = $db->query("SELECT credits ,from_user_id,package_id  FROM iptv_credit_logs 
                                  WHERE to_account_id = '$id' AND time > $exp_time AND from_user_id in ($reseller_sql) ");

                        while($row = $r_rc->fetch_assoc()) {
                            //LET CHECK IF THE USER WHO CREATE THE ACCOUNT EXIST OTHERWISE LET REFUND CREDITS TO MAIN RESELLER.
                            try {
                                $Owner = new \ACES2\IPTV\Reseller2((int)$row['from_user_id']);
                                $Owner->setCredits($Owner->getCredits() + $row['credits']);
                                \ACES2\IPTV\CreditLog::userRefundAccount(
                                    $Owner->id,
                                    $account->id,
                                    (int)$row['package_id'],
                                    $row['credits'],
                                    $Owner->getCredits()
                                );

                            } catch (Exception $exp) {
                                //RESELLER DO NOT EXIST MAIN RESELLER GET THE CREDITS.
                                $Reseller->setCredits($Reseller->getCredits() + $row['credits']);
                                \ACES2\IPTV\CreditLog::userRefundAccount(
                                    $Reseller->id,
                                    $account->id,
                                    (int)$row['package_id'],
                                    $row['credits'],
                                    $Reseller->getCredits()
                                );
                            }
                        }

                    }

                    $account->remove();
                }

            } else
                logd("Owner of account #$account->id is blocked. Account will not be removed.");
        }

        break;

    default:
        logE("Unknown action");
        setAjaxError("System Error");
        break;
}

setAjaxComplete($json);