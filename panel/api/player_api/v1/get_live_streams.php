<?php

include 'main.php';

ob_start();

$sql = '';
if(isset($_GET['category_id'])) { 
    
    if(empty($_GET['category_id']) || !is_numeric($_GET['category_id']) || $_GET['category_id'] < 1 ) echo '';
    else $sql = " AND c.category_id = '{$_GET['category_id']}' ";
    
}

if($ACCOUNT['adults'] == 0 )  $sql .= ' AND cat.adults = 0 ';
    
$r = $DB->query("SELECT c.id, c.name, c.type, c.logo, c.tvg_id, c.category_id,c.catchup_expire_days,c.ordering 
FROM iptv_channels_in_bouquet p 
    INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )
    LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id
    WHERE c.enable = 1 AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') $sql 
    GROUP BY c.id ORDER BY c.ordering ");

$prot = 'http';
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $prot = 'https';


    
$chan = array();
while($row=mysqli_fetch_array($r)) {

    $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
        ? $row['logo']
        : "$prot://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}" ;
    
    $archived = 0;
    $rr2=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '{$row['id']}' AND status = 3 LIMIT 1 ");
    if(mysqli_fetch_array($rr2)) $archived = 1;
    
    
    if($row['type'] == 1) $tvg_id ="aces-channel-{$row['id']}";
    else $tvg_id = $row['tvg_id'];
    
    //mb_convert_encoding( utf8_encode($row['name']), "UTF-8", "auto")
    
    $chan []  = array(  
        "num"=>(int)$row['ordering'], 
        'name'=>$row['name'],
        'stream_type'=>'live', 
        'stream_id'=>$row['id'], 
        'stream_icon'=> $logo,
        'epg_channel_id'=>$tvg_id, 
        'added'=>'0', 
        'category_id'=>$row['category_id'], 
        'custom_sid'=>(int)$row['id'], 
        'tv_archive'=> $archived, 
        'direct_source' => '', 
        'tv_archive_duration' => $row['catchup_expire_days'] );
}



echo json_encode($chan);
if(json_last_error()) AcesLogE("Json ERROR ". json_last_error_msg());

  
ob_end_flush();
