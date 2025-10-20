<?php

header('Content-Type: application/json');
header('Connection: close');




$js['js']['all_modules'][] = 'media_browser';

$js['js']['all_modules'][] = 'tv';
$js['js']['all_modules'][] = 'vclub';
$js['js']['all_modules'][] = 'sclub';
$js['js']['all_modules'][] = 'radio';
$js['js']['all_modules'][] = 'apps';
$js['js']['all_modules'][] = 'youtube';
$js['js']['all_modules'][] = 'dvb';
$js['js']['all_modules'][] = 'tv_archive';
$js['js']['all_modules'][] = 'time_shift';
$js['js']['all_modules'][] = 'time_shift_local';
$js['js']['all_modules'][] = 'epg.reminder';
$js['js']['all_modules'][] = 'epg.recorder';
$js['js']['all_modules'][] = 'epg';
$js['js']['all_modules'][] = 'epg.simple';
$js['js']['all_modules'][] = 'audioclub';
$js['js']['all_modules'][] = 'download_dialog';
$js['js']['all_modules'][] = 'downloads';
$js['js']['all_modules'][] = 'karaoke';
$js['js']['all_modules'][] = 'weather.current';
$js['js']['all_modules'][] = 'widget.audio';
$js['js']['all_modules'][] = 'widget.radio';
$js['js']['all_modules'][] = 'records';
$js['js']['all_modules'][] = 'remotepvr';
$js['js']['all_modules'][] = 'pvr_local';
$js['js']['all_modules'][] = 'settings.parent';
$js['js']['all_modules'][] = 'settings.localization';
$js['js']['all_modules'][] = 'settings.update';
$js['js']['all_modules'][] = 'settings.playback';
$js['js']['all_modules'][] = 'settings.common';
$js['js']['all_modules'][] = 'settings.network_status';
$js['js']['all_modules'][] = 'settings';
$js['js']['all_modules'][] = 'course.nbu';
$js['js']['all_modules'][] = 'weather.day';
$js['js']['all_modules'][] = 'cityinfo';
$js['js']['all_modules'][] = 'horoscope';
$js['js']['all_modules'][] = 'anecdote';
$js['js']['all_modules'][] = 'game.mastermind';
$js['js']['all_modules'][] = 'account';
$js['js']['all_modules'][] = 'demo';
$js['js']['all_modules'][] = 'infopartal';
$js['js']['all_modules'][] = 'internet';
$js['js']['all_modules'][] = 'service_management';
$js['js']['all_modules'][] = 'logout';
$js['js']['all_modules'][] = 'account_menu';

$js['js']['switchable_modules'][] = 'vlub';
$js['js']['switchable_modules'][] = 'sclub';
$js['js']['switchable_modules'][] = 'karaoke';
$js['js']['switchable_modules'][] = 'cityinfo';
$js['js']['switchable_modules'][] = 'horoscope';
$js['js']['switchable_modules'][] = 'anecdote';
$js['js']['switchable_modules'][] = 'game.mastermind';

$js['js']['disabled_modules'][] = 'weather.current';
$js['js']['disabled_modules'][] = 'weather.day';
$js['js']['disabled_modules'][] = 'cityinfo';
$js['js']['disabled_modules'][] = 'karaoke';
$js['js']['disabled_modules'][] = 'game.mastermind';
$js['js']['disabled_modules'][] = 'records';
$js['js']['disabled_modules'][] = 'downloads';
$js['js']['disabled_modules'][] = 'remotepvr';
$js['js']['disabled_modules'][] = 'service_management';
$js['js']['disabled_modules'][] = 'settings.common';
$js['js']['disabled_modules'][] = 'settings.update';
$js['js']['disabled_modules'][] = 'audioclub';
$js['js']['disabled_modules'][] = 'course.nbu';
$js['js']['disabled_modules'][] = 'infoportal';
$js['js']['disabled_modules'][] = 'demo';
$js['js']['disabled_modules'][] = 'widget.audio';
$js['js']['disabled_modules'][] = 'widget.radio';
$js['js']['disabled_modules'][] = 'radio';


$js['js']['restricted_modules'] = array() ; 


$js['js']['template'] = $THEME;
$js['js']['launcher_url'] = '';
$js['js']['launcher_profile_url'] = '';




echo json_encode($js);
