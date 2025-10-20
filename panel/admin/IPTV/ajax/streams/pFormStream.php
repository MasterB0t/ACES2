<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}


try {
    switch ($_REQUEST['action']) {

        case 'add_stream':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            $Stream = \ACES2\IPTV\Stream::addStream($_REQUEST,$_FILES['upload_logo']);
            if($_REQUEST['start'])
                $Stream->restart();

            //$ADMIN->addLog("Channel Added #$Stream->id, $Stream->name ");

            break;

        case 'update_stream':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            $Stream = new \ACES2\IPTV\Stream($_REQUEST['stream_id']);
            if(!(int)$_POST['stream_profile_id']) {
                $StreamProfile = new \ACES2\IPTV\StreamProfile($Stream->stream_profile_id);
                $StreamProfile->update($_POST['stream_profile']);
            }
            $Stream->updateStream($_REQUEST, $_FILES['upload_logo']);

            if($_REQUEST['start']) {
                $Stream = new \ACES2\IPTV\Stream($_REQUEST['stream_id']);
                $Stream->restart();
            }


            break;

        case 'add_channel':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            $Channel = \ACES2\IPTV\Channel::addChannel($_REQUEST,$_FILES['upload_logo']);
            if($_REQUEST['start'])
                $Channel->restart();


            break;

        case 'update_channel':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            $Channel = new \ACES2\IPTV\Channel($_REQUEST['stream_id']);
            $Channel->updateChannel($_REQUEST, $_FILES['upload_logo']);
            if($_REQUEST['start']) {
                $Channel = new \ACES2\IPTV\Channel($_REQUEST['stream_id']);
                $Channel->restart();
            }


            break;


    }
} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}
setAjaxComplete();