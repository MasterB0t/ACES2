<?php

if(!$user_id=userIsLogged()) {
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
}

$User = new \ACES2\IPTV\Reseller2($user_id);

switch($_REQUEST['action']) {

    case 'add_reseller':
        if(!$User->can_add_resellers)
            setAjaxError("Unable to perform this action");

        $CreditToApply = (int)$_REQUEST['credits'];
        //if($CreditToApply < 0)
        //    setAjaxError("Invalid credit value.");

        $RemainingCredits = $User->getCredits() - $CreditToApply;
        if($RemainingCredits < 0)
            setAjaxError("No enough credits.");

        $OwnerID = (int)$_REQUEST['reseller_of'];
        if($OwnerID) {
            if(!in_array($OwnerID, $User->getResellers()))
                setAjaxError("Main reseller could be not found.");
        } else {
            $OwnerID = $User->id;
        }

        try {

            $reseller = \ACES2\IPTV\Reseller2::add($_REQUEST['username'],
                $_REQUEST['password'], $_REQUEST['email'], $_REQUEST['name']);

        } catch(\Exception $e) {
            setAjaxError($e->getMessage());
        }


        $reseller->setCredits($CreditToApply);
        \ACES2\IPTV\CreditLog::reseller_add_reseller($User->id, $reseller->id, $CreditToApply, $RemainingCredits );
        $User->setCredits($RemainingCredits);

        $reseller->setOwner($OwnerID);

        $reseller->setStatus( $_REQUEST['enabled'] == 1 ? 1 : 0 );

        $reseller->setCanAddResellers(
            $User->can_add_resellers ? (bool)$_REQUEST['can_add_resellers'] : false );

        $reseller->setCanOverridePackage(
            $User->can_override_package ? (bool)$_REQUEST['can_override_package'] : false
        );

        $reseller->setAllowChannelList(
            $User->allow_channel_list ? (bool)$_REQUEST['allow_channel_list'] : false
        );

        $reseller->setAllowVodList(
            $User->allow_vod_list ? (bool)$_REQUEST['allow_vod_list'] : false
        );

        $reseller->setAllowAccountUsername(
            $User->isAllowAccountUsername() ?
                (bool)$_REQUEST['set_account_username'] : false
        );

        $reseller->setAllowAccountPassword(
            $User->isAllowAccountPassword() ?
                (bool)$_REQUEST['set_account_password'] : false

        );

        $reseller->status = 1;
        $reseller->save();

        break;

    case 'update_reseller':

        if(!$User->can_add_resellers)
            setAjaxError("Unable to perform this action");

        try {

            $db = new \ACES2\DB();
            $reseller = new \ACES2\IPTV\Reseller2((int)$_REQUEST['reseller_id']);
            if($reseller->status == \ACES2\IPTV\Reseller2::STATUS_BLOCKED)
                setAjaxError("Unable to perform this action");
            $reseller->setUsername($_REQUEST['username']);
            $reseller->name = $db->escString($_REQUEST['name']);
            $reseller->email = $db->escString($_REQUEST['email']);
            if(!empty($_REQUEST['password']))
                $reseller->setPassword($_REQUEST['password']);



        } catch(\Exception $e) {
            setAjaxError($e->getMessage());
        }

        $reseller->setOwner($User->id);

        $User->save();

        //NOT IN FORM.. SHALL THIS SHOULD BE ALLOWED BY USERS???
        //$reseller->setStatus( $_REQUEST['enabled'] == 1 ? 1 : 0 );

        $reseller->setCanAddResellers(
            $User->can_add_resellers ? (bool)$_REQUEST['can_add_resellers'] : false );

        $reseller->setCanOverridePackage(
            $User->can_override_package ? (bool)$_REQUEST['can_override_package'] : false
        );

        $reseller->setAllowChannelList(
            $User->allow_channel_list ? (bool)$_REQUEST['allow_channel_list'] : false
        );

        $reseller->setAllowVodList(
            $User->allow_vod_list ? (bool)$_REQUEST['allow_vod_list'] : false
        );

        $reseller->setAllowAccountUsername(
            $User->isAllowAccountUsername() ?
                (bool)$_REQUEST['set_account_username'] : false
        );

        $reseller->setAllowAccountPassword(
            $User->isAllowAccountPassword() ?
                (bool)$_REQUEST['set_account_password'] : false

        );

        $reseller->save();

        $ResellerPack = [];
        if($User->can_override_package) {

            try {

                $reseller->override_packages = $User->validateOverridePackageForReseller(
                    $_REQUEST['official_credits'], $_REQUEST['trial_credits']);

                $reseller->save();

            } catch(\Exception $e) {
                $ignore=1;
            }


        }


        break;

    case 'set_credits':
        $Reseller = new \ACES2\IPTV\Reseller2((int)$_REQUEST['reseller_id']);
        if($Reseller->status == \ACES2\IPTV\Reseller2::STATUS_BLOCKED)
            setAjaxError("Unable to perform this action");
        $ResellerCredits = $Reseller->getCredits();
        $Credits = (int)$_REQUEST['credits'];
        if($Credits < 0)
            setAjaxError("Invalid credit value.");
        $CreditToApply = ($ResellerCredits - $Credits) * -1 ;

        if( $ResellerCredits == $Credits )
            break; //NOTHING TO DO.

        if( ($ResellerCredits - $Credits)  < 0 ) {
            //ADDING CREDITS MAKE SURE IT DONT ADD MORE THAN HE USER HAVE.

            if( ($User->getCredits() - $CreditToApply )  < 0)
                setAjaxError("No Enough Credits");


        }

        $Reseller->setCredits(
            $ResellerCredits + $CreditToApply
        );

        $User->setCredits(
            $User->getCredits() - $CreditToApply
        );

        \ACES2\IPTV\CreditLog::reseller_to_reseller(
            $User->id,
            $Reseller->id,
            $CreditToApply,
            $User->getCredits()
        );

        break;

    case 'remove_resellers':

        $_REQUEST['reseller_ids'] = explode(",", $_REQUEST['reseller_ids']);

        $UserResellers = $User->getResellers();

        if((int)$_REQUEST['move_reseller_to_user'] != 0 ) {
            $MoveResellerTo = new \ACES2\IPTV\Reseller2((int)$_REQUEST['move_reseller_to_user']);
            if(!in_array($MoveResellerTo->id, $UserResellers))
                setAjaxError("Could not found reseller.");
        } else
            $MoveResellerTo = $User;

        if((int)$_REQUEST['move_account_to_user'] != 0) {
            $MoveAccountTo = new \ACES2\IPTV\Reseller2((int)$_REQUEST['move_account_to_user']);
            if(!in_array($MoveAccountTo->id, $UserResellers))
                setAjaxError("Could not found reseller.");
        } else
            $MoveAccountTo = $User;


        foreach($_REQUEST['reseller_ids'] as $rid) {
            $reseller_to_remove = new \ACES2\IPTV\Reseller2((int)$rid) ;
            if($reseller_to_remove->status != \ACES2\IPTV\Reseller2::STATUS_BLOCKED) {
                $credits_amount = $reseller_to_remove->getCredits();
                $reseller_to_remove->remove($MoveResellerTo->id, $MoveAccountTo->id);
                $credits_left  = $User->getCredits() + $credits_amount;
                $User->setCredits($credits_left);

                \ACES2\IPTV\CreditLog::reseller_remove_reseller(
                    $User->id,
                    $reseller_to_remove->id,
                    $credits_amount,
                    $credits_left
                );
            }else
                logD("Reseller #$reseller_to_remove->id will not be removed is blocked");

        }

        break;


    default :
        logD("Unknown action");
        setAjaxError("System Error");

}

setAjaxComplete();