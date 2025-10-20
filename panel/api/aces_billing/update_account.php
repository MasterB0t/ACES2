<?php

$Reseller = new ACES2\IPTV\Reseller2($USER_ID);

use \ACES2\IPTV\Account;

$account_id = (int)$_REQUEST['account_id'];

try {
    $Account = new Account($account_id);
} catch(Exception $e) {
    logD($e->getMessage());
    setApiError("Account not found", 404);
}

if(!$Reseller->canManageAccount($account_id))
    setApiError("Account not found", 404);

if($Account->status == Account::STATUS_BLOCKED)
    setApiError("Account blocked. cannot be updated.");

try {
    if(!empty($_REQUEST['account_username'])) {
        if(!$Reseller->isAllowAccountUsername())
            setApiError("Unable to update account username");
        else
            $Account->setUsername($_REQUEST['account_username']);
    }

    if(!empty($_REQUEST['account_name']))
        $Account->setName( $_REQUEST['account_name']);

    if(!empty($_REQUEST['account_password'])) {
        if(!$Reseller->isAllowAccountPassword())
            setApiError("Unable to update account password");
        else
            $Account->setPassword($_REQUEST['account_password']);
    }

    if(isset($_REQUEST['account_status'])) {
        $set_status = (bool)$_REQUEST['account_status'] ? Account::STATUS_ACTIVE : Account::STATUS_DISABLED;
        $Account->setStatus($set_status);
    }

    if(!empty($_REQUEST['account_mac'])) {
        $Account->setMacAddress($_REQUEST['account_mac']);
    }

} catch(Exception $e) {
    setApiError($e->getMessage());
}

$Account->save();


$Account =  new \ACES2\IPTV\Account($account_id);

echo json_encode(array(
    'id' => $Account->id,
    'name' => $Account->name,
    'username' => $Account->username,
    'password' => $Account->password,
    'limit_connections' => $Account->limit_connections,
    'expiration_date' => $Account->getExpirationDate(),
    'allowed_mag_devices' => $Account->allow_mag,
    'allowed_xc_apps' => $Account->allow_xc_apps,
));