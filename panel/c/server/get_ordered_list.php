<?php

header('Content-Type: application/json');
header('Connection: close');

if($_REQUEST['type'] == 'radio') {
    echo '{"js":{"total_items":0,"max_page_items":14,"selected_item":0,"cur_page":0,"data":[]}}';
    exit;
}

$r = $DB->query("SELECT bouquets,token,username FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=$r->fetch_assoc();

$r = $DB->query("SELECT itv_last_id,itv_last_genre,itv_last_page FROM iptv_mag_devices WHERE id = $MAG_ID ");
$mag_last = mysqli_fetch_array($r);

$SQL_GENRE = 'AND cat.adults = 0 ';
if(is_numeric($_GET['genre']) && $_GET['genre'] > 0 ) { $SQL_GENRE = "AND c.category_id = {$_GET['genre']}"; $genre=$_GET['genre']; }
else $_GET['genre'] = 0;

if($_GET['sortby'] == 'name') $sort_by = 'c.name';
else $sort_by = 'c.ordering';

if(!is_numeric($_GET['p'])) $_GET['p'] = 0;

$SQL_FAV = '';
if($_GET['fav'] == 1 ) {
    $f = implode(',',$FAVS['itv']);
    $SQL_FAV = " AND c.ordering IN ( $f ) ";
}

 
$device['bouquets'] = join("','", unserialize($device['bouquets']) );

$SQL_ADULTS = '';
if( $MAG_ADULTS == 0 ) $SQL_ADULTS = " AND cat.adults = 0 ";

//TO GET TOTAL CHANNELS.
$r=$DB->query("SELECT c.id FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
        LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
        WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS $SQL_GENRE $SQL_FAV
        GROUP BY c.id
        ORDER BY $sort_by ");

$total_items =  $r->num_rows;



$poss=0; 
if($_REQUEST['p'] == 0 && $mag_last['itv_last_id'] != 0 && $mag_last['itv_last_genre'] == $_GET['genre']) { 

   $rp = $DB->query("SELECT name FROM iptv_channels WHERE ordering = {$mag_last['itv_last_id']} ");
   if($_GET['genre'] != 0 ) { $q1 = " WHERE c.category_id = '{$_GET['genre']}' "; $q2 = "AND x.category_id = '{$_GET['genre']}' "; } 
   else $q1 = " WHERE cat.adults = 0 ";
   if($cname = $rp->fetch_assoc()['name']) {
       $rp2=$DB->query("SELECT  x.position
                       FROM (SELECT c.id,
                                    c.name,
                                    c.category_id,
                                    @rownum := @rownum + 1 AS position
                               FROM iptv_channels c
                               JOIN (SELECT @rownum := 0) r
                               LEFT JOIN iptv_stream_categories cat ON cat.id = c.category_id
                               $q1
                           ORDER BY $sort_by) x
                      WHERE x.name = '$cname' $q2 ");
       $poss = mysqli_fetch_array($rp2)['position'];
       
   }
    
}


$p = 0;

if(  ceil(($total_items/14)) < $_GET['p'] ) {
    //echo '{"js":[],"text":""}'; exit;
    //$_GET['p']  = (int)(($total_items/14)-1);
}
              
if($poss) {
    
    $fig = (int) str_pad('1', 0, '0');
    $_GET['p'] = (ceil( ($poss/14) * $fig) / $fig);
    $p = (($_GET['p']-1) * 14 );
    
} else if(is_numeric($_GET['p']) && $_GET['p'] > 0 ) { 
    if($_GET['p'] > 1 ) $p = (($_GET['p']-1) * 14 );
    else $p = 0;  
} else $_GET['p'] = 1;


$r2=$DB->query("SELECT c.*, cat.name as category_name, cat.adults as adults  FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
                LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
                WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS $SQL_GENRE $SQL_FAV
                GROUP BY c.id
                ORDER BY $sort_by 
                LIMIT $p,14");

$data = array();  $selected = 0; $s=0; $i=0;
if($r2->num_rows ==0 ) {

    echo '{"js":{"total_items":1,"max_page_items":14,"selected_item":1,"cur_page":"1","data":[{"id":"1","name":"247","number":"1","censored":0,"cmd":"ffmpeg http://198.245.106.7:9091/load/8oopyy261mij/hwt8p3kkj1/2.mpegts","cost":"0","count":"0","status":"1","tv_genre_id":"51","base_ch":"0","hd":"0","xmltv_id":"","service_id":"","bonus_ch":"0","volume_correction":"0","use_http_tmp_link":0,"mc_cmd":"","enable_tv_archive":1,"wowza_tmp_link":0,"wowza_dvr":1,"monitoring_status":"0","monitoring_status_updated":null,"enable_monitoring":"0","enable_wowza_load_balancing":"0","cmd_1":"","cmd_2":"","cmd_3":"","logo":"http://198.245.106.7:9091/logos/s2-1718483186.png","correct_time":"0","allow_pvr":0,"allow_local_pvr":0,"modified":null,"allow_local_timeshift":0,"nginx_secure_link":"0","tv_archive_duration":0,"flussonic_dvr":0,"locked":0,"added":"2019-03-16 10:34:49","nimble_dvr":0,"languages":"","tv_archive_type":"m3u8","lock":0,"fav":0,"archive":0,"genres_str":"","epg":[],"open":1,"pvr":0,"cur_playing":"[No channel info]","cmds":[{"id":"2","ch_id":"2","priority":"0","url":"ffmeg http://198.245.106.7:9091/load/8oopyy261mij/hwt8p3kkj1/2.mpegts","status":"1","use_http_tmp_link":"0","wowza_tmp_link":"0","user_agent_filter":"","use_load_balancing":"0","changed":"2019-03-16 10:34:49","enable_monitoring":"0","enable_balancer_monitoring":"0","nginx_secure_link":"0","flussonic_tmp_link":"0","xtream_codes_support":"0","edgecast_auth_support":"0","nimble_auth_support":"0","akamai_auth_support":"0","wowza_securetoken":"0"}],"use_load_balancing":0}]}}';
    exit;

//    echo json_encode( array (
//        'js' =>
//            array (
//                'total_items' => 0,
//                'max_page_items' => 14,
//                'selected_item' => 0,
//                'cur_page' =>1,
//                'data' => []
//
//            ),
//    ));
//    exit;
}
while($row=$r2->fetch_assoc()) { $i++;
    
    if(!$_GET['genre'] && $row['adults'] == 0 || $_GET['genre'] ) { 
    
        $archived=0;
        $rr=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '{$row['id']}' AND status = 3 LIMIT 1 ");
        if($rr->fetch_assoc()){ $archived=1; }

        $fav=0;
        if( is_array($FAVS['itv']) && @in_array($row['ordering'],$FAVS['itv'])) $fav=1;

        if($_REQUEST['p'] == 0 && $s == 0 ) $selected++;
        if(!empty($mag_last['itv_last_id']) && $row['ordering'] == $mag_last['itv_last_id'] ) $s=1;


        $d = array();
        $d['id'] = $row['ordering']; //THE ID TO BE USE FOR GET_SHORT_EPG.
        $d['name'] = $row['name'];
        $d['number'] = $row['number']; //THE DISPLAY NUMBER ON LIST.

        $d['censored'] = 0;
        if($_GET['genre'] == 0 && $row['adults'] == 1 && $MAG_ADULT_PIN)  $d['censored'] = 1;

        $d['cmd'] = "ffmpeg http://{$_SERVER['HTTP_HOST']}/load/{$device['username']}/{$device['token']}/{$row['id']}.{$MAG_STREAM_FORMAT}";
        $d['cost'] = "0";
        $d['count'] = "0";
        $d['status'] =  "1";
        $d['tv_genre_id'] = "{$row['category_id']}";
        $d['base_ch'] = "0";
        $d['hd'] = "0";
        $d['xmltv_id'] = "";
        $d['service_id'] = "";
        $d['bonus_ch']  = "0";
        $d['volume_correction'] = "0";
        $d['use_http_tmp_link'] = 0;
        $d['mc_cmd'] = "";
        $d['enable_tv_archive'] = 1;
        $d['wowza_tmp_link'] = 0;
        $d['wowza_dvr'] = 1;
        $d['monitoring_status'] = "0";
        $d['monitoring_status_updated'] = null;
        $d['enable_monitoring'] = "0";
        $d['enable_wowza_load_balancing'] = "0";
        $d['cmd_1'] = "";
        $d['cmd_2'] = "";
        $d['cmd_3'] = "";
        if($row['logo']) $d['logo'] = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}";
        else $d['logo'] = '';
        $d['correct_time'] = "0";
        $d['allow_pvr'] = 0;
        $d['allow_local_pvr'] = 0;
        $d['modified'] = null;
        $d['allow_local_timeshift'] = 0;
        $d['nginx_secure_link'] = "0";
        $d['tv_archive_duration'] = 0;
        $d['flussonic_dvr'] =0;
        $d['locked'] = 0;
        $d['added'] = "2019-03-16 10:34:49";
        $d['nimble_dvr'] = 0;
        $d['languages'] = "";
        $d['tv_archive_type'] = "m3u8";

        $d['lock'] = 0; //ADD A LOCK TO CHANNEL AND ASK FOR PIN.
        if($_GET['genre'] == 0 && $row['adults'] == 1 && $MAG_ADULT_PIN) $d['lock'] = 1 ; // ONLY SET A LOCK IF IS ON ALL CHANNELS OTHERWISE THE CATEGORY WILL BE LOCK INSTEAD.

        $d['fav'] = $fav;
        $d['archive'] = $archived;
        $d['genres_str'] = "";
        $d['epg']= array();
        $d['open'] = 1; // IF FALSE SHOW THE CHANNEL AS DISABLE IT WILL NOT BE ABLE TO PLAY.
        $d['pvr'] = 0;
        $d['cur_playing'] = "[No channel info]";
        $d['cmds'][] = array('id'=>"{$row['id']}",'ch_id'=>"{$row['id']}", 'priority'=>"0", 'url'=>"ffmpeg http://{$_SERVER['HTTP_HOST']}/load/{$device['username']}/{$device['token']}/{$row['id']}.$MAG_STREAM_FORMAT", "status"=>"1", "use_http_tmp_link"=>"0", "wowza_tmp_link"=>"0", "user_agent_filter"=>"", "use_load_balancing"=>"0", "changed"=>"2019-03-16 10:34:49", "enable_monitoring"=>"0", "enable_balancer_monitoring"=>"0", "nginx_secure_link"=>"0", "flussonic_tmp_link"=>"0", "xtream_codes_support"=>"0", "edgecast_auth_support"=>"0", "nimble_auth_support"=>"0", "akamai_auth_support"=>"0", "wowza_securetoken"=>"0" );
        $d['use_load_balancing'] = 0;
        $data[] = $d;
    }
}        

$json['js']['total_items'] = $total_items;
$json['js']['max_page_items'] = 14;

if($selected != 0 && $s == 1) $json['js']['selected_item'] = $selected;
else if( $_REQUEST['p'] != 0 && $mag_last['itv_last_page'] > $_GET['p'] ) $json['js']['selected_item'] = 14;
else $json['js']['selected_item'] = 1;

$json['js']['cur_page'] = $_GET['p'];
$json['js']['data'] = $data;



$DB->query("UPDATE iptv_mag_devices SET itv_last_genre = '{$_GET['genre']}', itv_last_page = '{$_GET['p']}' WHERE id = $MAG_ID ");

echo json_encode($json);


die;