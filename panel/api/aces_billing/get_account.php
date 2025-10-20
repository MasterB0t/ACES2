<?php

$RESELLER = new ACES2\IPTV\Reseller2($USER_ID);

$account_id = (int)$_REQUEST['account_id'];

try {
    $Account=  new \ACES2\IPTV\Account($account_id);
} catch (Exception $e) {
    setAjaxError("Account not found.", 404);
}

$Resellers = $RESELLER->getResellers();
$Resellers[] = $RESELLER->id;

if(!in_array($Account->owner_id, $Resellers))
    setAjaxError("Account not found.", 404);


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