<?php

$package_id = (int)$_REQUEST['package_id'];
if(!$package_id)
    setApiError("Package is required");

$Reseller = new ACES2\IPTV\Reseller2($USER_ID);

$Package = $Reseller->getBouquetPackage($package_id);
if($Package == null )
    setApiError("Package not found", 404);


$credit_amount = (int)$_REQUEST['trial'] ? $Package->trial_credits : $Package->official_credits ;

$remaining_credits = $Reseller->getCredits() - $credit_amount;
if($remaining_credits < 1) {
    setAjaxError("No enough credits");
}

$post = array(
    'name' => $db->escString($_REQUEST['account_name']),
    'owner_id' => (int)$Reseller->id,
    'package_id' => 0,
    'reseller_notes' => (string)$_REQUEST['reseller_notes'],
    'mac_address' => (string)$_REQUEST['mac_address'],
);

if( !empty($_REQUEST['account_username']) && $Reseller->isAllowAccountUsername()) {
    if(strlen(trim($_REQUEST['account_username']) < 6))
        setApiError("Username must be at least 6 characters.");
    $post['username'] = $db->escString($_REQUEST['account_username']);

    $r=$db->query("SELECT id FROM iptv_devices WHERE username = '{$post['username']}' ");
    if($r->num_rows>0 )
        setApiError("An account with this username already exists");

}

if( !empty($_REQUEST['account_password']) && $Reseller->isAllowAccountPassword()) {
    if(strlen(trim($_REQUEST['account_password']) < 6))
        setApiError("Password must be at least 6 characters.");
    $post['password'] = $_REQUEST['account_password'];
}

if(!empty($_REQUEST['account_mac'])) {
    if (!\ACES2\IPTV\Account::isValidMacAddress($_REQUEST['account_mac']))
        setApiError("Invalid MAC address");
    $post['mac_address'] = $_REQUEST['account_mac'];
}


$Account =  \ACES2\IPTV\Account::add(
    $post,
);

$Account->setAddByUser($Reseller->id);
$Account->setPackage($package_id);


$expire = (int)$_REQUEST['trial'] ?
    $Package->getTrialExpireInDate():
    $Package->getOfficialExpireInDate();

$Account->setExpireOn($expire);

$Account->save();
$Reseller->setCredits($remaining_credits);

if($_REQUEST['trial'])
    $Account->setTrial(1);

\ACES2\IPTV\CreditLog::reseller_add_account(
    $Reseller->id, $Account->id, $package_id, (bool)$_REQUEST['trial'], $credit_amount, $remaining_credits);

$Account->setAllowAdultContent((bool)$_REQUEST['allow_adult_content']);
//$Account->setAdultsWithPin((bool)$_REQUEST['adults_with_pin']);

$Account->setOwner($Reseller->id);

$Account->setStatus(1);
$Account->save();

$Account =  new \ACES2\IPTV\Account($Account->id );

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
