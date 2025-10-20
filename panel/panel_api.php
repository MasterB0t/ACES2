<?php

include '/home/aces/panel/api/player_api/v1/main.php';

$r=$DB->query("SELECT COUNT(id) as connections FROM iptv_access WHERE limit_time > NOW() AND device_id = {$ACCOUNT['id']}  ");
$rs=$DB->query("SELECT address FROM iptv_servers WHERE id = 1 ");
$server_ip = mysqli_fetch_array($rs)['address'];
$row=mysqli_fetch_array($r);

$json['user_info']['username'] = $ACCOUNT['username'];
$json['user_info']['password'] = $ACCOUNT['token'];
$json['user_info']['message'] = '';
$json['user_info']['auth'] = 1;


if($ACCOUNT['expire_in'] < 1 )$json['user_info']['status'] = 'Expired';
else if($ACCOUNT['status'] != 1 ) $json['user_info']['status'] = 'Blocked';
else $json['user_info']['status'] = 'Active';


$json['user_info']['exp_date'] = (string)strtotime($ACCOUNT['subcription']);
//$json['user_info']['exp_date'] = null;

if($ACCOUNT['demo'] == 1 ) $json['user_info']['is_trial'] = "1";
else $json['user_info']['is_trial'] = "0";

$json['user_info']['active_cons'] = $row['connections'];

$json['user_info']['created_at'] = (string)strtotime($ACCOUNT['add_date']);

$json['user_info']['max_connections'] = $ACCOUNT['limit_connections'];


$json['user_info']['allowed_output_formats'] = array('m3u8','ts','rtmp');

$protocol = 'http';
if( isset($_SERVER['HTTPS'] ) )  $protocol  = 'https';

$json['server_info']['url'] =  $server_ip ; //$_SERVER['SERVER_ADDR'];
$json['server_info']['port'] = $_SERVER['SERVER_PORT'];
$json['server_info']['https_port'] = $_SERVER['SERVER_PORT'];
$json['server_info']['server_protocol'] = $protocol;
$json['server_info']['rtmp_port']= '';
$json['server_info']['timezone'] = date_default_timezone_get();
$json['server_info']['timestamp_now'] = time();
$json['server_info']['time_now'] = date('Y-m-d h:i:s');


$SQL_ADULTS = '';
if($ACCOUNT['adults'] == 0 ) $SQL_ADULTS = ' AND cat.adults = 0 ';

$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_channels_in_bouquet p 
        LEFT JOIN iptv_channels chan on chan.id = p.chan_id
        RIGHT JOIN iptv_stream_categories cat on cat.id = chan.category_id
        WHERE p.bouquet_id IN ('{$ACCOUNT['bouquets']}') AND chan.enable = 1 $SQL_ADULTS
        GROUP BY cat.id ORDER BY cat.ordering,cat.name
");

while($row=mysqli_fetch_array($r)) { $json['categories']['live'][] = array('category_id' => $row['id'], 'category_name' => mb_convert_encoding( utf8_encode($row['name']), "UTF-8", "auto"), 'parent_id' => 0 ) ; }



$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_stream_categories cat on cat.id = o.category_id
        WHERE o.status = 1 AND o.type != 'series' $SQL_ADULTS
        GROUP BY cat.id ORDER BY cat.ordering
");

while($row=mysqli_fetch_array($r)) { $json['categories']['movies'][] = array('category_id' => $row['id'], 'category_name' => $row['name'], 'parent_id' => 0 ) ; }


$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_stream_categories cat on cat.id = o.category_id
        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS
        GROUP BY cat.id ORDER BY cat.ordering
");

while($row=mysqli_fetch_array($r)) { $json['categories']['series'][] = array('category_id' => $row['id'], 'category_name' => $row['name'], 'parent_id' => 0 ) ; }




if($ACCOUNT['adults'] == 0 )  $sql .= ' AND cat.adults = 0 ';
$r = $DB->query("SELECT c.id, c.name, c.logo, c.tvg_id, c.category_id,cat.name as category_name,c.catchup_expire_days,c.ordering FROM iptv_channels_in_bouquet p 
    INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )
    LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id
    WHERE c.enable = 1 AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') $sql 
    GROUP BY c.id ORDER BY c.ordering ");

$prot = 'http';
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $prot = 'https';

while($row=mysqli_fetch_array($r)) {
    
    $archived = 0;
    $rr2=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '{$row['id']}' AND status = 3 LIMIT 1 ");
    if(mysqli_fetch_array($rr2)) $archived = 1;
    
    $json['available_channels'][$row['id']]  = array(  
        "num"=>(int)$row['ordering'], 
        'name'=>mb_convert_encoding( utf8_encode($row['name']), "UTF-8", "auto"), 
        'stream_type'=>'live', 
        'type_name'=>'Live',
        'stream_id'=>$row['id'], 
        'stream_icon'=>"$prot://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}", 
        'epg_channel_id'=>$row['tvg_id'], 
        'added'=>'0', 
        'category_name'=>$row['category_name'],
        'category_id'=>$row['category_id'], 
        'series_no' => null,
        'live' => 1,
        'container_extension' => 'mpegts',
        'custom_sid'=>(int)$row['id'], 
        'tv_archive'=> $archived, 
        'direct_source' => '', 
        'tv_archive_duration' => $row['catchup_expire_days'] );
}



$r=$DB->query("SELECT o.*,cat.name as category_name FROM iptv_ondemand_in_bouquet p INNER JOIN iptv_ondemand o ON ( o.id = p.video_id $sql_cat ) RIGHT JOIN iptv_stream_categories cat on cat.id = o.category_id WHERE o.status = 1 AND o.type != 'series' $sql GROUP BY o.id ORDER BY p.i  ");
while($row=mysqli_fetch_array($r)) { 
    
    if( strtotime($row['add_date']) < 1 ) $add_date = 0;
    else $add_date = strtotime($row['add_date']);
    
    $json['available_channels'][$row['id']] = array( 
        'num' => (int)$row['id'],
        'name' => mb_convert_encoding( utf8_encode($row['name']), "UTF-8", "auto"),
        'stream_type' => 'movies',
        'type_name'=>'Movies',
        'stream_id' => (int)$row['id'],
        'stream_icon' => "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}",
        //'rating' => (int)$row['rating'],
        //'rating_5based' => round( ($row['rating']*.5), 1 ),
        'epg_channel_id'=>null, 
        'added' => "$add_date", 
        'category_name'=>$row['category_name'],        
        'category_id' => $row['category_id'],
        'series_no' => null,
        'live'=>0,
        'container_extension' => 'mp4',
        'custom_sid' => (int)$row['id'],
        'tv_archive'=> 0, 
        'direct_source' => '', 
        'tv_archive_duration' => 0 );
}


echo json_encode($json);