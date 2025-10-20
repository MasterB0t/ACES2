<?php

header('Content-Type: application/json');

function setApiError($msg, $http_code = 400 ) {
    http_response_code($http_code);
    echo json_encode(array('error_msg' => $msg  ));
    die;
}


//require_once $_SERVER['DOCUMENT_ROOT']."/includes/config.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Reseller2.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/init.php";

$db = new \ACES2\DB;

$USERNAME = $db->escString($_REQUEST['username']);
$PASSWORD = md5($db->escString($_REQUEST['password']));

$r=$db->query("SELECT id,username FROM users WHERE username='$USERNAME' AND password='$PASSWORD' AND status = 1 ");
if(!$USER_ID=$r->fetch_assoc()['id'])
    setApiError("Wrong username or password", 401);

if(isset($_REQUEST['action']) && is_file("/home/aces/panel/api/aces_billing/{$_REQUEST['action']}.php")) {
    include "/home/aces/panel/api/aces_billing/{$_REQUEST['action']}.php";
    exit;

} else {

    $reseller2 = new ACES2\IPTV\Reseller2($USER_ID);

    echo json_encode(array(
        'id' => $USER_ID,
        'username' => $USERNAME,
        'credits' => $reseller2->getCredits(),
        'allow_set_account_username' => $reseller2->isAllowAccountUsername(),
        'allow_set_account_password' => $reseller2->isAllowAccountPassword(),
        'can_add_resellers' => $reseller2->can_add_resellers,
    ));

    exit;

}