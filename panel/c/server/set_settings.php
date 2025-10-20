<?php


$r=$DB->query("SELECT settings FROM iptv_mag_devices WHERE id = $MAG_ID ");
if(!$row=$r->fetch_assoc()) {
    echo '{"js":true}';
    exit;
}

$SETTINGS = unserialize($row['settings']);

switch($_REQUEST['action']) {

    case 'set_portal_prefs':
        $SETTINGS['show_after_loading'] = $_GET['show_after_loading'] == 'last_channel' || $_GET['show_after_loading'] == 'main_menu' ?
            $_GET['show_after_loading'] :
            'main_menu';

        $SETTINGS['play_in_preview_by_ok'] = (bool)$_GET['play_in_preview_by_ok'];

        if(!empty($_GET['play_in_preview_by_ok'])) $SETTINGS['play_in_preview_by_ok'] = true;
        else $SETTINGS['play_in_preview_by_ok'] = false;

        $SETTINGS['theme'] = $DB->escape_string($_GET['theme']);

        break;

    case 'set_parent_password':
        if(empty($_GET['parent_password']) || empty($_GET['pass'])) { echo '{"js":false}'; die; }
        else if(!is_numeric($_GET['pass']) || strlen($_GET['pass']) != 4 )  { echo '{"js":false}'; die; }
        //else if($_GET['parent_password'] != $SETTINGS['parent_password']) { AcesLogD("{$_GET['parent_password']} != {$SETTINGS['parent_password']}"); echo '{"js":false3}'; die; }
        else if($_GET['pass'] !== $_GET['repeat_pass']) { echo '{"js":false}'; die; }
        else {

            $SETTINGS['parent_password'] = $_GET['pass'];
            $DB->query("UPDATE iptv_devices SET `pin` = '{$_GET['pass']}' WHERE id = '$MAG_ACCOUNT_ID' "  );
        }

        break;

    case 'set_locale':

        logfile(print_r($_REQUEST,true));

        $default_locale = \ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::STB_LOCALE);

        $allowed_locales = array('en_GB.utf8','es_ES.utf8', 'ru_RU.utf8');
        if(in_array($_REQUEST['locale'], $allowed_locales))
            $SETTINGS['locale'] = $_REQUEST['locale'];

        else $SETTINGS['locale'] = $default_locale;

        $SETTINGS['units'] = $_REQUEST['units'] == 'metric' || $_REQUEST['units'] == 'imperial'
            ? $_REQUEST['units']
            : 'metric';

        break;

}






$SETTINGS = serialize($SETTINGS);
$DB->query("UPDATE iptv_mag_devices SET settings = '$SETTINGS' WHERE id = $MAG_ID ");

echo '{"js":true}';
die;