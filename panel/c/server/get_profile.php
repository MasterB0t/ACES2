<?php

$r=$DB->query("SELECT id,name,username,token,pin,subcription,status FROM iptv_devices WHERE mag = '$MAC' AND allow_mag = 1 ");
if(!$account=$r->fetch_assoc()) {

    logfile("NO MAC FOUND IN DATABASE");

    $js = array (
        'js' =>
            array (
                'status' => 1,
                'msg' => 'Device auto add is disabled',
                'block_msg' => 'Please contact your provider<br>to register this device!',
            ),
        'text' => '',
    );
    echo json_encode($js); die;

}




$serial = $DB->real_escape_string(urldecode($_GET['sn']));
$num_banks = $DB->real_escape_string(urldecode($_GET['num_banks']));
$hd = $DB->real_escape_string(urldecode($_GET['hd']));
$image = $DB->real_escape_string(urldecode($_GET['ver']));
$image_version = $DB->real_escape_string($_GET['image_version']);
$hw_version = $DB->real_escape_string($_GET['hw_version']);
$hw_version2 = $DB->real_escape_string($_GET['hw_version_2']);
$version = $DB->real_escape_string(urldecode($_GET['stb_type']));
$client_type = $DB->real_escape_string(urldecode($_GET['client_type']));
$video_out = $DB->real_escape_string(urldecode($_GET['video_out']));
$metrics =  $DB->real_escape_string(urldecode($_GET['metrics']));

$device_id = $DB->real_escape_string(urldecode($_GET['device_id']));
$device_id2 = $DB->real_escape_string(urldecode($_GET['device_id2']));
$signature = $DB->real_escape_string(urldecode($_GET['signature']));

$checksum = md5("{$MAC}{$serial}{$device_id}{$device_id2}{$signature}");

$last_tv_id = 1;
$locale = \ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::STB_LOCALE); //DEFAULT SETTING



$r=$DB->query("SELECT * FROM iptv_mag_devices WHERE account_id = {$account['id']} ");
if(!$mag_device=$r->fetch_assoc()) {
    $DB->query("INSERT INTO iptv_mag_devices (account_id,mac,serial,model,image,token,checksum,ip,add_date)
        VALUES('{$account['id']}','$MAC','$serial','$MODEL','$image','$AUTH_TOKEN','$checksum','{$_SERVER['REMOTE_ADDR']}',NOW()) ");

    $SETTINGS = array();
    $FAVS = array();

} else {

    $DB->query("UPDATE iptv_mag_devices SET token = '$AUTH_TOKEN', ip = '{$_SERVER['REMOTE_ADDR']}', serial = '$serial',
                            model = '$MODEL', `checksum` = '$checksum' , image = '$image'
                        WHERE id = '{$mag_device['id']}'  ");

    $SETTINGS = unserialize($mag_device['settings']);

    $last_tv_id = (int)$mag_device['itv_last_id'] ? $mag_device['itv_last_id'] : 1;
    if(!empty($SETTINGS['locale']))
    $locale =  $SETTINGS['locale'];


}

$js = [
    "js" => [
        "id" => $account['id'],
        "name" => "",
        "sname" => "",
        "pass" => $account['pin'],
        "parent_password" => $account['pin'],
        "bright" => "200",
        "contrast" => "127",
        "saturation" => "127",
        "aspect" => "16",
        "video_out" => "hdmi",
        "volume" => 100,
        "playback_buffer_bytes" => "0",
        "playback_buffer_size" => "0",
        "audio_out" => "1",
        "mac" => $MAC,
        "ip" => $_SERVER['REMOTE_ADDR'],
        "ls" => "",
        "version" => $image,
        "lang" => "",
        "locale" => $locale,
        "city_id" => "0",
        "status" => $account['status'] == 1 ? 0 : 1,   // 1 BLOCKED | 2 ASK LOGIN
        "hd" => "1",
        "main_notify" => "1",
        "fav_itv_on" => "0",
        "now_playing_start" => "0000-00-00 00:00:00",
        "now_playing_type" => "0",
        "now_playing_content" => "",
        "additional_services_on" => "1",
        "time_last_play_tv" => null,
        "time_last_play_video" => null,
        "operator_id" => "0",
        "storage_name" => "",
        "hd_content" => "",
        "image_version" => $image_version,
        "last_change_status" => null,
        "last_start" => "0000-00-00 00:00:00",
        "last_active" => "0000-00-00 00:00:00",
        "keep_alive" => "0000-00-00 00:00:00",
        "playback_limit" => "0",
        "screensaver_delay" => "10",
        "phone" => "",
        "tv_quality" => "high",
        "fname" => "",
        "login" => $account['id'],
        "password" => $account['token'],
        "stb_type" => $version,
        "num_banks" => $num_banks,
        "tariff_plan_id" => "0",
        "comment" => null,
        "now_playing_link_id" => "0",
        "now_playing_streamer_id" => "0",
        "just_started" => "0",
        "last_watchdog" => "0000-00-00 00:00:00",
        "created" => null,
        "country" => "",
        "plasma_saving" => "0",
        "ts_enabled" => "0",
        "ts_enable_icon" => "1",
        "ts_path" => "",
        "ts_max_length" => "3600",
        "ts_buffer_use" => "cyclic",
        "ts_action_on_exit" => "no_save",
        "ts_delay" => "on_pause",
        "video_clock" => "Off",
        "verified" => "0",
        "hdmi_event_reaction" => "1",
        "pri_audio_lang" => "",
        "sec_audio_lang" => "",
        "pri_subtitle_lang" => "",
        "sec_subtitle_lang" => "",
        "subtitle_color" => "16777215",
        "subtitle_size" => 20,
        "show_after_loading" => "main_menu", //TODO
        "play_in_preview_by_ok" => "0",  //SEEMS THIS DOESN'T WORK. USE  play_in_preview_only_by_ok
        "hw_version" => $hw_version,
        "openweathermap_city_id" => "0",
        "theme" => $THEME,
        "settings_password" => $account['pin'],
        "expire_billing_date" => $account['subcription'], //$account['subcription']
        "reseller_id" => null,
        "account_balance" => "",
        "client_type" => "STB",
        "hw_version_2" => $hw_version2,
        "blocked" => "0",
        "units" => $SETTINGS['units'] ? $SETTINGS['units'] : 'metric',
        "tariff_expired_date" => $account['subcription'],
        "tariff_id_instead_expired" => null,
        "activation_code_auto_issue" => "1",
        "clock_format" => null,
        "storages" => [
        ],
        "last_itv_id" => $last_tv_id,
        "updated" => null,
        "rtsp_type" => "4",
        "rtsp_flags" => "0",
        "stb_lang" => "en",
        "display_menu_after_loading" => true,  //TODO
        "record_max_length" => 180,
        "web_proxy_host" => "",
        "web_proxy_port" => "",
        "web_proxy_user" => "",
        "web_proxy_pass" => "",
        "web_proxy_exclude_list" => "",
        "update_url" => "",
        "demo_video_url" => "",
        "tv_quality_filter" => "",
        "test_download_url" => "",
        "is_moderator" => false,
        "watchdog_timeout" => "120",
        "timeslot_ratio" => 1,
        "timeslot" => 120,
        "kinopoisk_rating" => 1,
        "enable_tariff_plans" => "",
        "enable_buffering_indication" => "",
        "default_timezone" => "GMT",
        "default_locale" => "en_GB.utf8",
        "allowed_stb_types_for_local_recording" => [
            "mag245",
            "mag245d",
            "mag250",
            "mag254",
            "mag255",
            "mag256",
            "mag257",
            "mag270",
            "mag260",
            "mag275",
            "mag322",
            "mag323",
            "mag324",
            "mag324c",
            "mag325",
            "mag349",
            "mag350",
            "mag351",
            "mag352",
            "mag424",
            "mag424\u0410",
            "mag425",
            "aurahd",
            "wr320",
            "ip_stb_hd",
            "im2100",
            "im2100v",
            "im2101",
            "im2101v",
            "im2102",
            "im4410",
            "mag420",
            "mag420w",
            "mag420w1",
            "mag520",
            "mag522",
            "mag522w1",
            "mag540",
            "mag522",
            "mag522w1",
            "mag540",
            "MAG522w1",
            "MAG522",
            "mag524",
            "mag524w3",
            "mag524",
            "MAG524W3"
        ],
        "strict_stb_type_check" => "",
        "cas_type" => 0,
        "cas_params" => null,
        "cas_web_params" => null,
        "cas_additional_params" => [
        ],
        "cas_hw_descrambling" => 0,
        "cas_ini_file" => "",
        "logarithm_volume_control" => "",
        "allow_subscription_from_stb" => "1",
        "deny_720p_gmode_on_mag200" => "1",
        "enable_arrow_keys_setpos" => "",
        "show_purchased_filter" => "",
        "timezone_diff" => 21600,
        "enable_connection_problem_indication" => "1",
        "invert_channel_switch_direction" => "",
        "play_in_preview_only_by_ok" =>  (bool)$SETTINGS['play_in_preview_by_ok'] == true,
        "enable_stream_error_logging" => "",
        "always_enabled_subtitles" => "",
        "enable_service_button" => "",
        "enable_setting_access_by_pass" => "",
        "show_tv_channel_logo" => 1, //TODO
        "tv_archive_continued" => "",
        "plasma_saving_timeout" => "600",
        "show_tv_only_hd_filter_option" => "",
        "tv_playback_retry_limit" => "0",
        "fading_tv_retry_timeout" => "1",
        "epg_update_time_range" => 0.2,
        "store_auth_data_on_stb" => false,
        "account_page_by_password" => "",
        "tester" => false,
        "show_channel_logo_in_preview" => 1,  //TODO
        "enable_stream_losses_logging" => "",
        "external_payment_page_url" => "",
        "max_local_recordings" => "0",
        "tv_channel_default_aspect" => "fit",
        "default_led_level" => "10",
        "standby_led_level" => "90",
        "show_version_in_main_menu" => "1",
        "check_ssl_certificate" => 0,
        "disable_youtube_for_mag200" => "1",
        "hls_fast_start" => "1",
        "auth_access" => false,
        "epg_data_block_period_for_stb" => "5",
        "standby_on_hdmi_off" => "1",
        "force_ch_link_check" => "",
        "stb_ntp_server" => "pool.ntp.org",
        "overwrite_stb_ntp_server" => "",
        "hide_tv_genres_in_fullscreen" => null,
        "advert" => null
    ]
];


echo json_encode($js);

exit;