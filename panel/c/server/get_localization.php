<?php

$default_locale = \ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::STB_LOCALE);

$lang = !empty($SETTINGS['locale']) ?
    explode('_', $SETTINGS['locale'])[0]:
    explode('_', $default_locale)[0];


if(is_file("localization/{$lang}.php"))
    include "localization/{$lang}.php";
else
    include "localization/en.php";



echo json_encode($js);
exit;