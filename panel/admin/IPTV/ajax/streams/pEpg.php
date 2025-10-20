<?php

use ACES2\IPTV\EpgSource;

$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES, 403);
    die;
}


//if(!\ACES2\Armor\Armor::isToken('iptv.epg_source', $_REQUEST['token'])) {
//    setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);
//}

try {
    switch ($_REQUEST['action']) {
        case 'add_epg_source':
            $EpgSource = \ACES2\IPTV\EpgSource::add($_REQUEST['name'] , $_REQUEST['url'], (bool)$_REQUEST['enabled']);
            set_time_limit(-1);
            setAjaxComplete('',false);
            $EpgSource->updateTvgIds();
            break;

        case 'update_epg_source':
            $EpgSource = new EpgSource($_REQUEST['id']);
            $current_status = $EpgSource->enabled;
            $EpgSource->update($_REQUEST['name'] , $_REQUEST['url'] , (bool)$_REQUEST['enabled']);
            if(!$current_status && $EpgSource->enabled ) {
                set_time_limit(-1);
                setAjaxComplete('',false);
                $EpgSource->updateTvgIds();
            }
            break;

        case 'update_tvgids':
            $EpgSource = new EpgSource($_REQUEST['id']);
            set_time_limit(-1);
            setAjaxComplete('',false);
            $EpgSource->updateTvgIds();
            break;

        case 'remove_epg_source':
            $EpgSource = new EpgSource($_REQUEST['id']);
            $EpgSource->remove();
            break;

        case 'build_epg':
            session_write_close();
            if(is_file('/home/aces/run/aces_build_guide.pid'))
                setAjaxError("This process is already running.");

            #exec("/opt/php83/bin/php /home/aces/bin/aces_build_guide.php > /dev/null &");
            exec("php /home/aces/bin/iptv_build_guide.php > /dev/null &");
            sleep(1);

            break;

        case 'get_epg_progress':
            $data['is_running'] = false;
            if(is_file('/home/aces/run/aces_build_guide.pid')) {
                $data['is_running'] = true;
                $data['progress'] = file_get_contents('/home/aces/tmp/epg_progress');
            }
            setAjaxComplete($data);
            break;


        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
    }



} catch (Exception $e) {
    setAjaxError($e->getMessage());
}


setAjaxComplete();
