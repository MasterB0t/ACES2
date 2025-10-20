<?php

$ADMIN = new \ACES2\ADMIN();

if (!$ADMIN->isLogged()) {
    setAjaxError("",403);
    exit;
} else if (!$ADMIN->hasPermission('')) {
    setAjaxError('',403);
    exit;
}

$db = new \ACES2\DB;

$password = $db->escString($_POST['password']);
$r=$db->query("SELECT id FROM admins WHERE password = md5('$password')");
if(!$r->fetch_assoc()) {
    setAjaxError("Wrong Password");
}

session_write_close();
echo json_encode( array( 'complete' => 1, 'status' => 1));
fastcgi_finish_request();
set_time_limit(60*10);

function clearStreams() {
    global $db;
    $db->query("TRUNCATE iptv_channels");
    $db->query("TRUNCATE iptv_channels_in_bouquet");
    $db->query("TRUNCATE iptv_channels_in_lb");
    $db->query("TRUNCATE iptv_channels_sources");
    $db->query("TRUNCATE iptv_channel_files");
    $db->query("TRUNCATE iptv_stream_options");
    $db->query("TRUNCATE iptv_package_channels");

    $db->query("INSERT INTO iptv_stream_options (name,video_codec,audio_codec,segment_time,segment_list_files,segment_wrap) 
            VALUES ('Stream','copy','copy','10','6','6')");

}

function clearVods() {
    global $db;

    $db->query("TRUNCATE iptv_ondemand");
    $db->query("TRUNCATE iptv_video_files");
    $db->query("TRUNCATE iptv_series_season_episodes");
    $db->query("TRUNCATE iptv_series_seasons");
    $db->query("TRUNCATE iptv_video_play_count");
    $db->query("TRUNCATE iptv_in_category");
    $db->query("TRUNCATE iptv_ondemand_in_bouquet");

    $db->query("TRUNCATE iptv_app_favorites");
    $db->query("TRUNCATE iptv_app_last_episode");
    $db->query("TRUNCATE iptv_app_new_episodes");
    $db->query("TRUNCATE iptv_app_video_position");
    $db->query("TRUNCATE iptv_app_watched");
    $db->query("TRUNCATE iptv_app_watching ");

//    exec('cd /home/aces/panel/logos/ &&  find . -name "v*.jpg" -delete');
//    exec('cd /home/aces/panel/logos/ &&  find . -name "vb*.jpg" -delete');

}

function clearLogs($older_than) {
    global $db ;

    if($older_than == 'all' ) {
        $sql_time = "  UNIX_TIMESTAMP(NOW()) ";
        $sql_date = "  NOW() ";
    } else if( $intv = (int)$older_than ) {
        $sql_time = "  UNIX_TIMESTAMP(NOW() - INTERVAL $intv MONTH ) ";
        $sql_date = "  NOW() - INTERVAL $intv MONTH  ";
    } else {

        exit;
    }

    $db->query("DELETE FROM admin_logs WHERE time < $sql_date ");
    $db->query("DELETE FROM admin_logins WHERE login_time < $sql_time ");
    $db->query("DELETE FROM iptv_account_logs WHERE log_date < $sql_time ");
    $db->query("DELETE FROM iptv_credit_logs WHERE time < $sql_time ");
    $db->query("DELETE FROM user_logs WHERE time < $sql_date ");

}

switch(strtoupper($_REQUEST['action'])) {
    case 'CLEAR_ALL':
        clearStreams();
        clearLogs('all');
        clearVods();
        $db->query("TRUNCATE iptv_access ");
        $db->query("TRUNCATE iptv_account_locks");
        $db->query("TRUNCATE iptv_app_access_code");
        $db->query("TRUNCATE iptv_app_devices");
        $db->query("TRUNCATE iptv_app_profiles");
        $db->query("TRUNCATE iptv_blocks");
        $db->query("TRUNCATE iptv_bouquets");
        $db->query("TRUNCATE iptv_bouquet_packages");
        $db->query("TRUNCATE iptv_epg");
        $db->query("TRUNCATE iptv_epg_sources");
        $db->query("TRUNCATE iptv_folder_watch");
        $db->query("TRUNCATE iptv_in_category");
        $db->query("TRUNCATE iptv_ip_info");
        $db->query("TRUNCATE iptv_packages");
        $db->query("TRUNCATE iptv_plex_accounts");
        $db->query("TRUNCATE iptv_proccess");
        $db->query("TRUNCATE iptv_streaming");
        $db->query("TRUNCATE iptv_recording");
        $db->query("TRUNCATE iptv_stream_categories");
        $db->query("TRUNCATE iptv_user_credits");
        $db->query("TRUNCATE iptv_user_info");
        $db->query("TRUNCATE iptv_xc_videos_imp");
        $db->query("TRUNCATE users");

        $db->query("TRUNCATE iptv_devices");
        $db->query("TRUNCATE iptv_mag_devices");
        $db->query("TRUNCATE iptv_mag_event");

        break;

    case 'CLEAR_VODS':
        clearVods();
        break;

    case 'CLEAR_STREAMS':
        clearStreams();
        break;

    case 'CLEAR_LOGS':
        clearLogs($_POST['older_than']);
        break;

    case 'CLEAR_SYSTEM_LOGS':
        $action = \ACES\IPTV\Server::ACTION_CLEAR_SYSTEM_LOGS;
        $r2=$db->query("SELECT id FROM iptv_servers ");
        while($server = $r2->fetch_assoc()) {
            $Server = new \ACES2\IPTV\Server($server['id']);
            try {
                $Server->send_action($action);
            } catch(Exception $e) {
                ;;
            }

        }
        break;

    case 'CHECK_VODS':
        $db->query("DELETE FROM iptv_video_reports WHERE type = 0 ");
        $r=$db->query("SELECT id FROM iptv_servers ");
        while($server_id = $r->fetch_assoc()['id']) {
            $Server = new \ACES2\IPTV\Server($server_id);
            try {
                $Server->send_action(\ACES2\IPTV\Server::ACTION_CHECK_VODS);
            }catch(Exception $e) {
                $ignoreee=0;
            }
        }
        break;
}