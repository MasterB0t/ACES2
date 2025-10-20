<?php



$r = $DB->query("SELECT * FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=mysqli_fetch_array($r);

$device['bouquets'] = join("','", unserialize($device['bouquets']) );

$SQL_GENRE = 'AND cat.adults = 0';
if(isset($_GET['genre']) && is_numeric($_GET['genre']) && $_GET['genre'] > 0 ) $SQL_GENRE = "AND category_id = {$_GET['genre']}";

if(isset($_GET['sortby']) && $_GET['sortby'] == 'name') $sort_by = 'c.name';
else $sort_by = 'c.ordering';

$p = 0;
if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ) {
    if($_GET['p'] > 1 ) $p = (($_GET['p']-1) * 14 );
    else $p = 0;
} else $_GET['p'] = 1;

$r2=$DB->query("SELECT c.*, cat.name as category_name, cat.adults  FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
                LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
                WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $SQL_GENRE
                GROUP BY c.id
                ORDER BY $sort_by ");



$data = array();$i = 50;
if($r2->num_rows == 0 ) {

    //SOME BOX LIKE XTVPro WILL NOT LOAD IF THERE ARE NO CHANNELS SIMPLE HACK OF A DUMMY CHANNEL

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

    if(!@$_GET['genre'] && !$row['adults']) {
        $d = array();
        $d['id'] = $row['ordering']; //THE ID TO BE USE FOR GET_SHORT_EPG.
        $d['name'] = $row['name'];
        $d['number'] = $row['number']; //THE ID FOR GO TO CHANNEL WITH REMOTE BUTTONS.
        $d['censored'] = 0;
        //if($row['adults'] == 1 ) $d['censored'] = 1;
        $d['cmd'] = "ffmpeg http://{$_SERVER['HTTP_HOST']}/load/$MAG_USERNAME/{$device['token']}/{$row['id']}.$MAG_STREAM_FORMAT";
        $d['cost'] = "0";
        $d['count'] = "0";
        $d['status'] =  "1";
        $d['tv_genre_id'] = $row['category_id'];
        $d['base_ch'] = "0";
        $d['hd'] = "0";
        $d['xmltv_id'] = "";
        $d['service_id'] = "";
        $d['bonus_ch']  = "0";
        $d['volume_correction'] = "0";
        $d['use_http_tmp_link'] = 0;
        $d['mc_cmd'] = "";
        $d['enable_tv_archive'] = 0;
        $d['wowza_tmp_link'] = 0;
        $d['wowza_dvr'] = 0;
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
        $d['allow_local_timeshift'] = "0";
        $d['nginx_secure_link'] = "0";
        $d['tv_archive_duration'] = "168";
        $d['flussonic_dvr'] = 0;
        $d['locked'] = "0";
        $d['added'] = "2019-03-16 10:34:49";
        $d['nimble_dvr'] = "0";
        $d['languages'] = "";
        $d['tv_archive_type'] = "";
        $d['lock'] = 0; //ADD A LOCK TO CHANNEL AND ASK FOR PIN.

        //if($row['adults'] == 1 ) $d['lock'] = 1 ;

        $d['fav'] = 0;
        $d['archive'] = 0;
        $d['genres_str'] = "";
        $d['epg']= array();
        $d['open'] = 1; // IF FALSE SHOW THE CHANNEL AS DISABLE IT WILL NOT BE ABLE TO PLAY.
        $d['pvr'] = 0;
        $d['cur_playing'] = "[No channel info]";
        $d['cmds'][] = array(
            "id" => $row['id'],
            "ch_id" => $row['id'],
            "priority" => "0",
            "url" => "ffmpeg http://{$_SERVER['HTTP_HOST']}/load/$MAG_USERNAME/{$device['token']}/{$row['id']}.$MAG_STREAM_FORMAT",
            "status" => "1",
            "use_http_tmp_link" => "1",
            "wowza_tmp_link" => "0",
            "user_agent_filter" => "",
            "use_load_balancing" => "0",
            "changed" => "",
            "enable_monitoring" => "0",
            "enable_balancer_monitoring" => "0",
            "nginx_secure_link" => "1",
            "flussonic_tmp_link" => "0"
        );
        //$d['cmds'][] = array('id'=>"{$row['ordering']}",'ch_id'=>"{$row['ordering']}", 'priority'=>"0", 'url'=>"ffmpeg http://{$_SERVER['HTTP_HOST']}/load/$MAG_USERNAME/{$device['token']}/{$row['id']}.mpegts", "status"=>"1", "use_http_tmp_link"=>"0", "wowza_tmp_link"=>"0", "user_agent_filter"=>"", "use_load_balancing"=>"0", "changed"=>"2019-03-16 10:34:49", "enable_monitoring"=>"0", "enable_balancer_monitoring"=>"0", "nginx_secure_link"=>"0", "flussonic_tmp_link"=>"0", "xtream_codes_support"=>"0", "edgecast_auth_support"=>"0", "nimble_auth_support"=>"0", "akamai_auth_support"=>"0", "wowza_securetoken"=>"0" );
        $d['use_load_balancing'] = 0;
        $data[] = $d;
    }
}

$json = array();
$json['js']['total_items'] = mysqli_num_rows($r2);
$json['js']['max_page_items'] = 14;
$json['js']['selected_item'] = 1;
$json['js']['cur_page'] = 0;
$json['js']['data'] = $data;



echo json_encode($json);
die;