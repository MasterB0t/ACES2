<?php

$r=$DB->query("SELECT settings FROM iptv_mag_devices WHERE id = $MAG_ID ");
if($row=$r->fetch_assoc()) { $SETTINGS = unserialize($row['settings']); }

$locale = !empty($SETTINGS['locale']) ? $SETTINGS['locale'] : 'en_GB.utf8';

$locales[] = array(
    'label'=>'English',
    'value' => 'en_GB.utf8',
    'selected' => 0,
);

$locales[] = array(
    'label'=>'Russian',
    'value' => 'ru_RU.utf8',
    'selected' => 0,
);

$locales[] = array(
    'label'=>'Espanol',
    'value' => 'es_ES.utf8',
    'selected' => 0,
);

foreach($locales as $i => $l){
    if($l['value']==$locale)
        $locales[$i]['selected'] = 1;
}


echo json_encode(array('js' => $locales));