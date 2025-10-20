<?php

$reseller2 = new ACES2\IPTV\Reseller2($USER_ID);

$account_id = (int)$_REQUEST['account_id'];
$package_id = (int)$_REQUEST['package_id'];


try {
    if($account_id ) {
        $Account=  new \ACES2\IPTV\Account($account_id);
    } else {

        $account_username = $_REQUEST['account_username'];
        $account_password = $_REQUEST['account_password'];

        $r=$db->query("SELECT id FROM iptv_devices WHERE username = '$account_username' AND token = '$account_password' ");
        $Account=  new \ACES2\IPTV\Account($r->fetch_assoc()['id']);
    }


} catch (Exception $e) {
    setApiError("Account not found.", 404);
}


if(!$reseller2->canManageAccount($Account->id))
    setApiError("Account not found.", 404);

$Package = $reseller2->getBouquetPackage($package_id);
if(!$Package)
    setApiError("Package not found.", 404);


$credit_cost = !isset($OP['official_credits']) || $OP['official_credits'] == '' ?
    $Package->official_credits : $OP['official_credits'];

$credits = $reseller2->getCredits() - $credit_cost;
if($credits < 1 )
    setApiError("No enough credits");


$Account->setPackage($package_id);


if($Account->expire_on > time())
    $r=$db->query("SELECT UNIX_TIMESTAMP(  subcription + INTERVAL $Package->official_duration $Package->official_duration_in )  as expire_in 
                FROM iptv_devices 
            WHERE id = $Account->id ");
else //EXPIRED
    $r=$db->query("SELECT UNIX_TIMESTAMP(  NOW() + INTERVAL $Package->official_duration $Package->official_duration_in )  as expire_in
                FROM iptv_devices
            WHERE id = $Account->id ");

\ACES2\IPTV\CreditLog::reseller_update_account($reseller2->id, $Account->id, $package_id, $credit_cost , $credits );

$Account->expire_on = $r->fetch_assoc()['expire_in'];


$Account->setTrial(false);

$Account->save();
$reseller2->setCredits($credits);

\ACES2\IPTV\CreditLog::reseller_update_account($reseller2->id, $Account->id, $package_id, $credit_cost , $credits );


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