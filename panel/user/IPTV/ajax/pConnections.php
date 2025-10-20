<?php

if(!$UserID=userIsLogged())
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED,401);

$User = new \ACES2\IPTV\Reseller2($UserID);
$db = new \ACES2\DB;

switch($_REQUEST['action']) {

    case 'stop_connection':
        $connection_id = $_REQUEST['connection_id'];
        $resellers = $User->getResellers();
        $resellers[] = $UserID;
        $u_sql = implode(',', $resellers);
        $db->query("DELETE a FROM iptv_access a
                RIGHT JOIN iptv_devices d ON d.id = a.device_id AND d.user_id IN ($u_sql) 
                WHERE a.id = '$connection_id'");

        break;

    case 'stop_from_device':

        if(!$device_id = (int)$_REQUEST['device_id']) {
            setAjaxComplete();exit;
        }

        if(!$User->canManageAccount($device_id))
            setAjaxError("Account not found.",404);

        $db->query("DELETE FROM iptv_access WHERE device_id = '$device_id'");

        break;

    case 'stop_all_connections':
        $resellers = $User->getResellers();
        $resellers[] = $UserID;
        $u_sql = implode(',', $resellers);
        $db->query("DELETE a FROM iptv_access a 
         RIGHT JOIN iptv_devices d ON d.id = a.device_id AND d.user_id IN ($u_sql)
         ");


    default:
        logD("Unknown action '".$_REQUEST['action']."'");
        break;

}

setAjaxComplete();