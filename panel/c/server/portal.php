<?php

//error_reporting(E_ERROR);
ini_set('memory_limit', '-1');

if(is_file('config.php'))
    include_once 'config.php';

function logfile($msg) {
    GLOBAL $MAC,$CONFIG;
    //if($CONFIG['debug']) {
        if(!is_file("/home/aces/logs/mag_portal.log")) file_put_contents("/home/aces/logs/mag_portal.log","\n");
        file_put_contents("/home/aces/logs/mag_portal.log", "{$_SERVER['REMOTE_ADDR']} $MAC : $msg \n", FILE_APPEND);
    //}
}

function get_string_between($string, $start, $end=null){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    if(!is_null($end)) $len = strpos($string, $end, $ini) - $ini;
    else $len = strlen($string);
    return substr($string, $ini, $len);
}

function Output($js) {

    echo json_encode(array('js' => $js));
    exit;

}

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}



// no cache
//header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
//header("Last-Modified: Thu, 01 Jan 1970 00:00:00 GMT");
header("Content-Type: text/javascript;charset=UTF-8");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate");
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Methods: GET, POST,PUT");

define('ACES_ROOT', '/home/aces/');
define('DOC_ROOT', '/home/aces/panel/');

include '/home/aces/stream/config.php';
include '/home/aces/panel/functions/logs.php';
include '/home/aces/panel/class/db.php';
include '/home/aces/panel/ACES2/DB.php';
include '/home/aces/panel/ACES2/Settings.php';
include '/home/aces/panel/ACES2/IPTV/Settings.php';
//include '/home/aces/panel/ACES2/init.php';

//logfile($_SERVER["SCRIPT_FILENAME"]);
//logfile($_SERVER["REQUEST_URI"]);
//logfile(print_r($_REQUEST,1));



if(!defined('HOST')) {
    $host = isset($_SERVER['HTTPS']) ? "https://".$_SERVER['HTTP_HOST'] : "http://".$_SERVER['HTTP_HOST'];
    define('HOST', $host);
}


use ACES2\IPTV\Settings;

if(!defined('HOST')) {
    $host = isset($_SERVER['HTTPS']) ? "https://".$_SERVER['HTTP_HOST'] : "http://".$_SERVER['HTTP_HOST'];
    define("HOST", $host);
}

$DB = new \DB($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die; }

$TIMEZONE=null;
$HEADERS = getallheaders();
$MAC = urldecode(get_string_between($HEADERS['Cookie'], 'mac=', ';'));

if( strpos($HEADERS['Cookie'], 'timezone=;') !== false ) {
    //NOT TIMEZONE SET ON MAG. GET IT BY IP;
    $r_ip = $DB->query("SELECT timezone FROM iptv_ip_info WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
    if($row=mysqli_fetch_array($r_ip)) $TIMEZONE= $row['timezone'];

} else {
    $TIMEZONE = urldecode(get_string_between($HEADERS['Cookie'], 'timezone=', ';'));
    if(!$TIMEZONE) $TIMEZONE = trim(urldecode(get_string_between($HEADERS['Cookie'], 'timezone=')));
}

$AUTH_TOKEN = get_string_between(@$HEADERS['Authorization'], 'Bearer ', ';');
$MODEL = trim(get_string_between($HEADERS['X-User-Agent'], 'Model:', ';'));
$LINK =trim(get_string_between($HEADERS['X-User-Agent'], 'Link:', ';'));

$THEME = Settings::get( Settings::STB_THEME );
if(!$THEME || !is_dir("../template/$THEME"))
    $THEME = 'default';


$SERVER_TIMEZONE = date_default_timezone_get();
if($TIMEZONE) date_default_timezone_set($TIMEZONE);

//DEFAULT VALUES
$SETTINGS = [];
$FAVS = [];
$MAG_ID = 0;
$MAG_ACCOUNT_ID = 0;
@$MAG_THEME = '';
$MAG_TOKEN = '';
$MAG_USERNAME = '';
$MAG_ADULT_PIN = 1;
$MAG_ADULTS = 0;


if($_REQUEST['action'] == 'handshake') {

    $token = md5(microtime(true)).";";
    echo json_encode(array("js" => array("token"=>$token)));
    die;
} else if($_REQUEST['action'] == 'get_profile') {
    include 'get_profile.php';
    exit;
}

$r=$DB->query("SELECT m.*,d.token as account_token,d.username as account_username,d.adults_with_pin,d.adults,d.stream_format,
       date_format(d.subcription,'%M %d, %Y, %l:%i %p') as full_exp_date,d.extra_opts FROM iptv_mag_devices m "
    . " RIGHT JOIN iptv_devices d ON d.id = m.account_id "
    . " WHERE m.token = '$AUTH_TOKEN' AND m.ip = '{$_SERVER['REMOTE_ADDR']}' AND d.allow_mag = 1 ");

if(!$row=$r->fetch_assoc()) {
    logfile("ACCESS DENIED");

    if($MAC) $DB->query("UPDATE iptv_mag_devices set token = '' WHERE mac = '$MAC' ");
    //echo '{"error":"access_denied","error_description":"Wrong account id or token."}';
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


$SETTINGS = unserialize($row['settings']);
$FAVS = unserialize($row['favs']);

$MAG_ID = $row['id'];
$MAG_ACCOUNT_ID = $row['account_id'];
$MAG_STREAM_FORMAT = !empty($SETTINGS['stream_format']) ? $SETTINGS['stream_format'] : 'ts';


@$MAG_THEME = $SETTINGS['theme'];
if(!Settings::get(Settings::STB_FORCE_THEME)
    && !empty($SETTINGS['theme'])
    && is_dir("../template/{$SETTINGS['theme']}")
) {
    $THEME = $SETTINGS['theme'];
}

$MAG_TOKEN = $row['account_token'];
$MAG_USERNAME = $row['account_username'];
$MAG_ADULT_PIN = $row['adults_with_pin'];
$MAG_ADULTS = $row['adults'];
$MAG_EXTRA_OPTIONS = unserialize($row['extra_opts']);



switch(strtolower($_REQUEST['action'])) {

    case 'get_main_info':
        echo json_encode(
            array (
            'js' =>
                array (
                    'mac' => $MAC,
                    'phone' => $row['full_exp_date'],
                    'message' => "",
                ),
            )
        );
        break;


    case 'set_last_id':
        if($id = (int)$_REQUEST['id']) {
            $DB->query("UPDATE iptv_mag_devices SET itv_last_id = '{$id}' WHERE id = $MAG_ID ");
        }
        echo '{"js":[true],"text":""}';
        break;

    case 'set_fav':
    case 'del_fav':
        include 'manage_fav.php';
        break;

    case 'get_ordered_list':
        if($_REQUEST['type'] == 'vod' || $_REQUEST['type'] == 'series')
            include 'get_ordered_list_vod.php';
        if($_REQUEST['type'] == 'radio') {
            //WE DONT HAVE RADIO YET..
            echo '{"js":[]}';
        } else
            //TV
            include 'get_ordered_list.php';

        break;


    case 'get_week':
        include 'get_week_epg.php';
        break;

    case 'get_simple_data_table':
        include 'get_simple_epg_data.php';
        break;

    case 'create_link':
        if($_REQUEST['type'] == 'vod')
            include 'create_vod_link.php';
        else if($_REQUEST['type'] == 'tv_archive')
            include 'create_tv_archive_link.php';
        break;


    case 'get_genres_by_category_alias':
        echo '{"js":[{"id":"*","title":"*"}]}';
        break;

    case 'get_all_fav_channels':
    case 'get_fav_ids':
        include 'get_all_fav_channels.php';
        break;

    case 'set_locale':
    case 'set_portal_prefs':
    case 'set_parent_password':
        include 'set_settings.php';
        break;

    case 'pre_load':
        echo '{"js":{"data":{"msgs":0,"additional_services_on":1}}}';
        break;


    case 'get_preload_images':
        //logfile("THEME ".$THEME);
        echo '{"js":["template\/'.$THEME.'\/i_720\/mb_table05.png","template\/'.$THEME.'\/i_720\/mm_ico_account.png","template\/'.$THEME.'\/i_720\/osd_rec.png","template\/'.$THEME.'\/i_720\/arr_right.png","template\/'.$THEME.'\/i_720\/mb_table04.png","template\/'.$THEME.'\/i_720\/input_episode.png","template\/'.$THEME.'\/i_720\/mb_table_act01.png","template\/'.$THEME.'\/i_720\/v_menu_2a.png","template\/'.$THEME.'\/i_720\/mb_pass_input.png","template\/'.$THEME.'\/i_720\/v_menu_1a.png","template\/'.$THEME.'\/i_720\/osd_line_pos.png","template\/'.$THEME.'\/i_720\/footer_menu_act.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_4_b.png","template\/'.$THEME.'\/i_720\/v_menu_4.png","template\/'.$THEME.'\/i_720\/v_menu_2b.png","template\/'.$THEME.'\/i_720\/ico_issue.png","template\/'.$THEME.'\/i_720\/osd_btn.png","template\/'.$THEME.'\/i_720\/aspect_bg.png","template\/'.$THEME.'\/i_720\/osd_line.png","template\/'.$THEME.'\/i_720\/footer_bg.png","template\/'.$THEME.'\/i_720\/2.png","template\/'.$THEME.'\/i_720\/mm_ico_apps.png","template\/'.$THEME.'\/i_720\/mm_vert_cell.png","template\/'.$THEME.'\/i_720\/mb_table_act02.png","template\/'.$THEME.'\/i_720\/volume_off.png","template\/'.$THEME.'\/i_720\/tv_table_arrows.png","template\/'.$THEME.'\/i_720\/mb_context_bg.png","template\/'.$THEME.'\/i_720\/input.png","template\/'.$THEME.'\/i_720\/_0_sun.png","template\/'.$THEME.'\/i_720\/tv_table_separator.png","template\/'.$THEME.'\/i_720\/ears.png","template\/'.$THEME.'\/i_720\/tv_table.png","template\/'.$THEME.'\/i_720\/1x1.gif","template\/'.$THEME.'\/i_720\/ico_error26.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_10_b.png","template\/'.$THEME.'\/i_720\/3.png","template\/'.$THEME.'\/i_720\/skip.png","template\/'.$THEME.'\/i_720\/deg.png","template\/'.$THEME.'\/i_720\/vol_1.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_1_a.png","template\/'.$THEME.'\/i_720\/input_act.png","template\/'.$THEME.'\/i_720\/mm_hor_left.png","template\/'.$THEME.'\/i_720\/4.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_11_a.png","template\/'.$THEME.'\/i_720\/25alfa_20.png","template\/'.$THEME.'\/i_720\/_2_cloudy.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_2_b.png","template\/'.$THEME.'\/i_720\/btn2.png","template\/'.$THEME.'\/i_720\/input_channel.png","template\/'.$THEME.'\/i_720\/footer_sidepanel_r.png","template\/'.$THEME.'\/i_720\/footer_sidepanel_line.png","template\/'.$THEME.'\/i_720\/mb_chan.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_12_a.png","template\/'.$THEME.'\/i_720\/hr_filminfo.png","template\/'.$THEME.'\/i_720\/footer_sidepanel_l.png","template\/'.$THEME.'\/i_720\/mm_ico_mb.png","template\/'.$THEME.'\/i_720\/epg_red_mark.png","template\/'.$THEME.'\/i_720\/mm_ico_video.png","template\/'.$THEME.'\/i_720\/v_menu_1b.png","template\/'.$THEME.'\/i_720\/mm_ico_internet.png","template\/'.$THEME.'\/i_720\/mm_hor_right.png","template\/'.$THEME.'\/i_720\/mb_icon_scrambled.png","template\/'.$THEME.'\/i_720\/mm_ico_magiccast.png","template\/'.$THEME.'\/i_720\/_9_snow.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_3_a.png","template\/'.$THEME.'\/i_720\/5.png","template\/'.$THEME.'\/i_720\/minus.png","template\/'.$THEME.'\/i_720\/item_bg.png","template\/'.$THEME.'\/i_720\/footer_search_act2.png","template\/'.$THEME.'\/i_720\/_1_sun_cl.png","template\/'.$THEME.'\/i_720\/osd_time.png","template\/'.$THEME.'\/i_720\/1.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_10_a.png","template\/'.$THEME.'\/i_720\/_8_rain_swon.png","template\/'.$THEME.'\/i_720\/epg_green_mark.png","template\/'.$THEME.'\/i_720\/arr_left.png","template\/'.$THEME.'\/i_720\/input_channel_bg.png","template\/'.$THEME.'\/i_720\/mm_ico_info.png","template\/'.$THEME.'\/i_720\/_255_NA.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_6_b.png","template\/'.$THEME.'\/i_720\/ears_arrow_r.png","template\/'.$THEME.'\/i_720\/footer_btn.png","template\/'.$THEME.'\/i_720\/mm_ico_setting.png","template\/'.$THEME.'\/i_720\/mm_ico_default.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_2_a.png","template\/'.$THEME.'\/i_720\/plus.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_7_a.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_7_b.png","template\/'.$THEME.'\/i_720\/volume_bar.png","template\/'.$THEME.'\/i_720\/mm_ico_usb.png","template\/'.$THEME.'\/i_720\/ears_arrow_l.png","template\/'.$THEME.'\/i_720\/6.png","template\/'.$THEME.'\/i_720\/mb_table02.png","template\/'.$THEME.'\/i_720\/tv_table_focus.png","template\/'.$THEME.'\/i_720\/loading.png","template\/'.$THEME.'\/i_720\/ico_confirm.png","template\/'.$THEME.'\/i_720\/bg.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_8_b.png","template\/'.$THEME.'\/i_720\/ico_info.png","template\/'.$THEME.'\/i_720\/mm_ico_audio.png","template\/'.$THEME.'\/i_720\/mb_table_act03.png","template\/'.$THEME.'\/i_720\/black85.png","template\/'.$THEME.'\/i_720\/_3_pasmurno.png","template\/'.$THEME.'\/i_720\/footer_bg2.png","template\/'.$THEME.'\/i_720\/mm_hor_bg1.png","template\/'.$THEME.'\/i_720\/footer_menu.png","template\/'.$THEME.'\/i_720\/mb_player.png","template\/'.$THEME.'\/i_720\/input_episode_bg.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_5_b.png","template\/'.$THEME.'\/i_720\/footer_search.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_12_b.png","template\/'.$THEME.'\/i_720\/_6_lightning.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_1_b.png","template\/'.$THEME.'\/i_720\/mb_table_act05.png","template\/'.$THEME.'\/i_720\/8.png","template\/'.$THEME.'\/i_720\/bg2.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_11_b.png","template\/'.$THEME.'\/i_720\/mb_filminfo_trans.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_3_b.png","template\/'.$THEME.'\/i_720\/modal_bg.png","template\/'.$THEME.'\/i_720\/low_q.png","template\/'.$THEME.'\/i_720\/mm_hor_bg3.png","template\/'.$THEME.'\/i_720\/mb_table03.png","template\/'.$THEME.'\/i_720\/mb_scroll_bg.png","template\/'.$THEME.'\/i_720\/mm_ico_tv.png","template\/'.$THEME.'\/i_720\/mb_table06.png","template\/'.$THEME.'\/i_720\/epg_orange_mark.png","template\/'.$THEME.'\/i_720\/_4_short_rain.png","template\/'.$THEME.'\/i_720\/mb_table07.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_5_a.png","template\/'.$THEME.'\/i_720\/footer_sidepanel_arr.png","template\/'.$THEME.'\/i_720\/mb_table01.png","template\/'.$THEME.'\/i_720\/mm_ico_rec.png","template\/'.$THEME.'\/i_720\/7.png","template\/'.$THEME.'\/i_720\/mm_hor_bg2.png","template\/'.$THEME.'\/i_720\/mb_table_act06.png","template\/'.$THEME.'\/i_720\/mb_icons.png","template\/'.$THEME.'\/i_720\/_1_moon_cl.png","template\/'.$THEME.'\/i_720\/dots.png","template\/'.$THEME.'\/i_720\/v_menu_3.png","template\/'.$THEME.'\/i_720\/mm_ico_karaoke.png","template\/'.$THEME.'\/i_720\/pause_btn.png","template\/'.$THEME.'\/i_720\/mm_ico_ex.png","template\/'.$THEME.'\/i_720\/mb_context_borders.png","template\/'.$THEME.'\/i_720\/footer_search_act.png","template\/'.$THEME.'\/i_720\/mb_icon_rec.png","template\/'.$THEME.'\/i_720\/footer_sidepanel.png","template\/'.$THEME.'\/i_720\/footer_sidepanel_act.png","template\/'.$THEME.'\/i_720\/loading_bg.gif","template\/'.$THEME.'\/i_720\/mm_ico_android.png","template\/'.$THEME.'\/i_720\/_0_moon.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_4_a.png","template\/'.$THEME.'\/i_720\/mm_ico_radio.png","template\/'.$THEME.'\/i_720\/mb_scroll.png","template\/'.$THEME.'\/i_720\/ico_alert.png","template\/'.$THEME.'\/i_720\/mb_pass_bg.png","template\/'.$THEME.'\/i_720\/9.png","template\/'.$THEME.'\/i_720\/mb_table_act04.png","template\/'.$THEME.'\/i_720\/_5_rain.png","template\/'.$THEME.'\/i_720\/v_menu_5.png","template\/'.$THEME.'\/i_720\/volume_bg.png","template\/'.$THEME.'\/i_720\/_10_heavy_snow.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_9_b.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_6_a.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_9_a.png","template\/'.$THEME.'\/i_720\/mm_ico_dm.png","template\/'.$THEME.'\/i_720\/_7_hail.png","template\/'.$THEME.'\/i_720\/tv_prev_bg.png","template\/'.$THEME.'\/i_720\/mb_prev_bg.png","template\/'.$THEME.'\/i_720\/osd_bg.png","template\/'.$THEME.'\/i_720\/0.png","template\/'.$THEME.'\/i_720\/mb_quality.png","template\/'.$THEME.'\/i_720\/horoscope_menu_button_1_8_a.png"],"text":"generated in: 0.012s; query counter: 6; cache hits: 0; cache miss: 0; php errors: 0; sql errors: 0;"}';
        break;

    case 'get_events':
        //logfile("TYPE : {$_REQUEST['type']}");
        include 'watchdog.php';
        //echo '{"js":[]}';
        break;

    case 'confirm_event':
        $event_id = (int)$_REQUEST['event_active_id'];
        $DB->query("DELETE FROM iptv_mag_event WHERE id = '$event_id' AND account_id = '$MAG_ACCOUNT_ID' ");
        echo '{"js":[]}';
        break;


    case 'get_epg_info':
    case 'get_active_recordings':
        echo '{"js":[],"text":""}';
        break;

    default:

        $file = strtolower($_REQUEST['action']);
        try {
            require $file . ".php";
            exit;
        } catch(\Error $exp ) {
            echo '{"js":[]}';
        }

        break;


}
