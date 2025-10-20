<?php

use ACES2\IPTV\AdminPermissions;
use ACES2\IPTV\Process;

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$db = new \ACES2\DB;
$data = [];

$Process = explode(",",$_REQUEST['process_name']);

switch(strtoupper($_REQUEST['action'])){

    case 'GET_PROCESS':
        foreach($Process as $process ){
            switch(strtoupper($process)){

                case Process::TYPE_UPDATE_PROVIDER_CONTENT:
                case Process::TYPE_CHECK_VIDEOS:
                case Process::TYPE_REMOVE_VIDEO_REPORTS:
                    if(!$ADMIN->hasPermission(AdminPermissions::IPTV_VOD))
                        setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
                    break;

                default:
                    setAjaxError("Unknown Process Type");

            }

        }

        $r=$db->query("SELECT * FROM process WHERE type = '$process' ");
        $data[$process] = $r->fetch_all(MYSQLI_ASSOC);
        break;

    case 'KILL_PROCESS':

        //CHECKING PERMISSIONS
        switch(strtoupper($_REQUEST['process_name'])){

            case Process::TYPE_UPDATE_PROVIDER_CONTENT:
            case Process::TYPE_CHECK_VIDEOS:
            case Process::TYPE_REMOVE_VIDEO_REPORTS:
                if(!$ADMIN->hasPermission(AdminPermissions::IPTV_VOD_FULL))
                    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);

                break;

            default:
                setAjaxError("Unknown Process Type");

        }

        $process_id = (int)$_REQUEST['process_id'];
        $process = $db->escString($_REQUEST['process_name']);
        $db->query("DELETE FROM process WHERE type = '$process' AND id = '$process_id' ");
        break;

}







setAjaxComplete($data);
