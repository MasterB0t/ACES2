<?php

$cur_dir = getcwd();
chdir('../template');
$templates_dir = getcwd();
chdir($cur_dir);



$TEMPLATES = [];
foreach( glob($templates_dir . '/*' , GLOB_ONLYDIR) as $dir ) {

    $dir = str_replace($templates_dir.'/' , '' , $dir);

    $name = str_replace("_", ' ', $dir);
    $name = strtoupper($name);

    $TEMPLATES[] = array (
        'id' => $dir,
        'type' => 'classic',
        'alias' => $dir,
        'default' => false,
        'default_launcher' => false,
        'name' => $name,
        'preview' => HOST."c/template/$dir/preview.png",
    );
}



$d = 
  array (
    'parent_password' => '0000',
    'update_url' => '',
    'test_download_url' => '',
    'playback_buffer_size' => 0,
    'screensaver_delay' => '10',
    'plasma_saving' => '0',
    'spdif_mode' => '1',
    'modules' => 
    array (
      0 => 
      array (
        'name' => 'lock',
        'sub' => NULL,
      ),
      1 => 
      array (
        'name' => 'settings_lock',
        'sub' => NULL,
      ),
      2 => 
      array (
        'name' => 'lang',
        'sub' => NULL,
      ),
      3 => 
      array (
        'name' => 'update',
        'sub' => NULL,
      ),
      4 => 
      array (
        'name' => 'net_info',
        'sub' => 
        array (
          0 => 
          array (
            'name' => 'wired',
            'sub' => NULL,
          ),
          1 => 
          array (
            'name' => 'pppoe',
            'sub' => 
            array (
              0 => 
              array (
                'name' => 'dhcp',
                'sub' => NULL,
              ),
              1 => 
              array (
                'name' => 'dhcp_manual',
                'sub' => NULL,
              ),
              2 => 
              array (
                'name' => 'disable',
                'sub' => NULL,
              ),
            ),
          ),
          2 => 
          array (
            'name' => 'wireless',
            'sub' => NULL,
          ),
          3 => 
          array (
            'name' => 'speed',
            'sub' => NULL,
          ),
          4 => 
          array (
            'name' => 'traceroute',
            'sub' => NULL,
          ),
        ),
      ),
      5 => 
      array (
        'name' => 'video',
        'sub' => NULL,
      ),
      6 => 
      array (
        'name' => 'audio',
        'sub' => NULL,
      ),
      7 => 
      array (
        'name' => 'playback',
        'sub' => NULL,
      ),
      8 => 
      array (
        'name' => 'portal',
        'sub' => NULL,
      ),
      9 => 
      array (
        'name' => 'net',
        'sub' => 
        array (
          0 => 
          array (
            'name' => 'ethernet',
            'sub' => 
            array (
              0 => 
              array (
                'name' => 'dhcp',
                'sub' => NULL,
              ),
              1 => 
              array (
                'name' => 'dhcp_manual',
                'sub' => NULL,
              ),
              2 => 
              array (
                'name' => 'manual',
                'sub' => NULL,
              ),
              3 => 
              array (
                'name' => 'no_ip',
                'sub' => NULL,
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'pppoe',
            'sub' => 
            array (
              0 => 
              array (
                'name' => 'dhcp',
                'sub' => NULL,
              ),
              1 => 
              array (
                'name' => 'dhcp_manual',
                'sub' => NULL,
              ),
              2 => 
              array (
                'name' => 'disable',
                'sub' => NULL,
              ),
            ),
          ),
          2 => 
          array (
            'name' => 'wifi',
            'sub' => 
            array (
              0 => 
              array (
                'name' => 'dhcp',
                'sub' => NULL,
              ),
              1 => 
              array (
                'name' => 'dhcp_manual',
                'sub' => NULL,
              ),
              2 => 
              array (
                'name' => 'manual',
                'sub' => NULL,
              ),
            ),
          ),
        ),
      ),
      10 => 
      array (
        'name' => 'remote_control',
        'sub' => NULL,
      ),
      11 => 
      array (
        'name' => 'advanced',
        'sub' => NULL,
      ),
      12 => 
      array (
        'name' => 'time_shift',
        'sub' => NULL,
      ),
      13 => 
      array (
        'name' => 'dvb',
        'sub' => NULL,
      ),
      14 => 
      array (
        'name' => 'servers',
        'sub' => NULL,
      ),
      15 => 
      array (
        'name' => 'dev_info',
        'sub' => NULL,
      ),
      16 => 
      array (
        'name' => 'reload',
        'sub' => NULL,
      ),
      17 => 
      array (
        'name' => 'internal_portal',
        'sub' => NULL,
      ),
      18 => 
      array (
        'name' => 'reboot',
        'sub' => NULL,
      ),
    ),
    'ts_enabled' => '0',
    'ts_enable_icon' => '1',
    'ts_path' => '',
    'ts_max_length' => '3600',
    'ts_buffer_use' => 'cyclic',
    'ts_action_on_exit' => 'no_save',
    'ts_delay' => 'on_pause',
    'hdmi_event_reaction' => 1,
    'pri_audio_lang' => '',
    'sec_audio_lang' => '',
    'pri_subtitle_lang' => '',
    'sec_subtitle_lang' => '',
    'subtitle_size' => 20,
    'subtitle_color' => 16777215,
    'show_after_loading' => 'main_menu',
    'play_in_preview_by_ok' => true,
    'hide_adv_mc_settings' => false,
    'themes' => $TEMPLATES,
    'user_theme' => $THEME,
    'units' => $SETTINGS['units'] ? $SETTINGS['units'] : 'metric',
    'mtr_report_cycles' => '210',
    'mtr_hostnames' => 
    array (
      0 => $_SERVER['HTTP_HOST'],
    ),
  'text' => '',
);


$r=$DB->query("SELECT settings FROM iptv_mag_devices WHERE id = $MAG_ID ");
if(!$row=$r->fetch_assoc()) {  die; }

$SETTINGS = unserialize($row['settings']);
foreach($SETTINGS as $i => $v ) $d[$i] = $v;



echo json_encode(array('js' => $d )); die;




