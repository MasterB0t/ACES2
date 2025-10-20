<?php

header('Content-Type: application/json');
header('Connection: close');

$r = $DB->query("SELECT * FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=mysqli_fetch_array($r);

$p = 0;
if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ) { 
    if($_GET['p'] > 1 ) $p = (($_GET['p']-1) * 14 );
    else $p = 0;  
} else $_GET['p'] = 1;

$r=$DB->query("SELECT c.*, cat.name as category_name, cat.adults  FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
                LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
                WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') 
                GROUP BY c.id
                ORDER BY c.id ");
                

$r2=$DB->query("SELECT c.*, cat.name as category_name, cat.adults  FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
                LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
                WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') 
                GROUP BY c.id
                ORDER BY c.id LIMIT $p,14");
                
$data = array();
while($row=mysqli_fetch_array($r2)) { 
    
    $d=array (
        'id' => $row['id'],
        'name' => $row['name'],
        'number' => $row['id'],
        'censored' => '',
        'cmd' => "ffmpeg http://{$_SERVER['HTTP_HOST']}/load/{$device['token']}/{$row['id']}.mpegts",
        'cost' => '0',
        'count' => '0',
        'status' => 1,
        'hd' => '0',
        'tv_genre_id' => '4',
        'base_ch' => '1',
        'xmltv_id' => '3e',
        'service_id' => '',
        'bonus_ch' => '0',
        'volume_correction' => '0',
        'mc_cmd' => '',
        'enable_tv_archive' => 0,
        'wowza_tmp_link' => '0',
        'wowza_dvr' => '0',
        'use_http_tmp_link' => '1',
        'monitoring_status' => '1',
        'enable_monitoring' => '0',
        'enable_wowza_load_balancing' => '0',
        'cmd_1' => '',
        'cmd_2' => '',
        'cmd_3' => '',
        'logo' => "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}",
        'correct_time' => '0',
        'nimble_dvr' => '0',
        'allow_pvr' => 1,
        'allow_local_pvr' => 1,
        'allow_remote_pvr' => 0,
        'modified' => '',
        'allow_local_timeshift' => '1',
        'nginx_secure_link' => '1',
        'tv_archive_duration' => 0,
        'locked' => 0,
        'lock' => 0,
        'fav' => 1,
        'archive' => 0,
        'genres_str' => '',
        'cur_playing' => '[No channel info]',
        'epg' => 
        array (
        ),
        'open' => 1,
        'cmds' => 
        array (
          0 => 
          array (
            'id' => $row['id'],
            'ch_id' => $row['id'],
            'priority' => '0',
            'url' => "ffmpeg http://{$_SERVER['HTTP_HOST']}/load/{$device['token']}/{$row['id']}.mpegts",
            'status' => '1',
            'use_http_tmp_link' => '1',
            'wowza_tmp_link' => '0',
            'user_agent_filter' => '',
            'use_load_balancing' => '0',
            'changed' => '',
            'enable_monitoring' => '0',
            'enable_balancer_monitoring' => '0',
            'nginx_secure_link' => '1',
            'flussonic_tmp_link' => '0',
          ),
        ),
        'use_load_balancing' => 0,
        'pvr' => 1,
      );
    
    $data[] = $d;
    
}                

$js = array (
  'js' => 
  array (
    'total_items' => 2,
    'max_page_items' => 14,
    'selected_item' => 1,
    'cur_page' => 0,
    'data' => $data
  ),
);

echo json_encode($js);
die;