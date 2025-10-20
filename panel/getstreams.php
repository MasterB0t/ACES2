<?php 
//DEPRECATED.
die;
ob_start();
ini_set('memory_limit','500M');

error_reporting(0);


include 'includes/init.php';

$ADDR = $_SERVER['HTTP_HOST'];

$ACES = new IPTV();
$ARMOR = new \ACES\Armor();

$PRTC = 'http:';
if( isset($_SERVER['HTTPS'] ) )  $PRTC = 'https:';


if(!empty($_GET['type']) && $_GET['type'] == 'm3u8') $TYPE = 'm3u8';
else if(!empty($_GET['type']) && $_GET['type'] == 'hls') $TYPE = 'm3u8';
else if(!empty($_GET['type']) && $_GET['type'] == 'ini') $TYPE = 'ini';
else if(!empty($_GET['type']) && $_GET['type'] == 'simple') $TYPE = 'simple';
else $TYPE='mpegts';


if(empty($_GET['token']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['token']) ||  $ARMOR->isBlock('iptv-account') || empty($_GET['username']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['username']) ) {
	
    
	ob_clean();
	header('Content-type: application/force-download');
	header('Content-Disposition: attachment; filename=playlist.m3u8');
	header("Content-type: application/vnd.apple.mpegurl");
	
	echo "#EXTM3U\n";
	echo "#EXTINF:0,NOT FOUND\n";
	echo "$PRTC//$ADDR/notfound.php";
	
	die;

} else { 
    $token = $_GET['token'];
    $username = $_GET['username'];
    $sql = " token = '{$_GET['token']}' AND username = '{$_GET['username']}' ";

}


$r = $ACES->query("SELECT id,name,user_id,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expiration,bouquets,adults,hide_vods_from_playlist,no_m3u_playlist FROM iptv_devices WHERE $sql ");
if(!$device=mysqli_fetch_array($r)) {

        //$ACES->tries('iptv_streams');
        $ARMOR->action('iptv-account');
        sleep(2);die;
        die;

        ob_clean();
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename=playlist.m3u8');
        header("Content-type: application/vnd.apple.mpegurl");

        echo "#EXTM3U\r\n";
        echo "#EXTINF:0,NOT FOUND\r\n";
        echo "{$PRTC}//localhost/";

        die;


} else if($device['expiration'] < 1 ) {

        ob_clean();
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename=playlist.m3u8');
        header("Content-type: application/vnd.apple.mpegurl");

        echo "#EXTM3U\r\n";
        echo "#EXTINF:0,SUBSCRIPTION ENDED\r\n";
        echo "{$PRTC}//localhost";

        die;


}else if( $device['no_m3u_playlist'] == 1 ) { 

        ob_clean();
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename=playlist.m3u8');
        header("Content-type: application/vnd.apple.mpegurl");

        echo "#EXTM3U\r\n\r\n";		
        die;

} else {

        //LOGGIN

        //if(($set=$ACES->getSetting('iptv.DeviceLog','int')) > -1 ) { 
        //	$ACES->query("INSERT INTO iptv_logs (device_id,type,log_date,ip_address,user_agent) VALUES('{$device['id']}','List Loaded',NOW(),'{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_USER_AGENT']}') ");

                //THERE IS A LIMIT ON DEVICE LOG LET DELETE IF WE EXCEDE IT.
        //	if($set>0) { 
        //		$r=$ACES->query("SELECT i FROM iptv_logs WHERE device_id = {$device['id']} ");
        //		if(  ($del = (mysqli_num_rows($r) - $set)) > 0    ) 
        //				//LET DELETE OLDS LOGS.
        //				$ACES->query("DELETE FROM iptv_logs WHERE device_id = {$device['id']}  ORDER BY i ASC LIMIT $del ");

        //	}

        //}

        $ACES->query("UPDATE iptv_devices SET  ip_address = '{$_SERVER['REMOTE_ADDR']}'  WHERE id='{$device['id']}' ");

}


$channels = '';
if($TYPE == 'ini' ) $channels = "[plugin.video.aces_iptv]\n\n";
else $channels = "#EXTM3U\r\n";

$device['bouquets'] = join("','", unserialize($device['bouquets']) );


$r = $ACES->query("SELECT c.*, cat.name as category_name, cat.adults, opt.adaptive_opt  FROM $ACES->_tb_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
        LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 
        LEFT JOIN iptv_stream_options opt ON opt.id = c.stream_profile 
        WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}')
        GROUP BY c.id
        ORDER BY p.i

");

while($row=mysqli_fetch_array($r)) {

    if($row['adults'] && $device['adults'] || !$row['adults'])
            if($TYPE == 'ini' ) {

                    $channels .= "{$row['name']}={$PRTC}//$ADDR/load/$username/$token/{$row['id']}.$TYPE\n" ;

            } else if( $TYPE == 'simple' )  {

                    $channels .= "#EXTINF:-1,{$row['name']}\r\n" ;
                    $channels .= "{$PRTC}//$ADDR/load/$username/$token/{$row['id']}\r\n";
                    
                                                            
                    if(!empty($row['adaptive_opt'])) 
                        foreach(json_decode($row['adaptive_opt'],1) as $i => $v ) { 
                            $channels .= "#EXTINF:-1 $logo $tvg_id group-title=\"{$row['category_name']}\",{$row['name']} {$v['profile_name']}\r\n" ;
                            $channels .= "{$PRTC}//$ADDR/load/$username/$token/{$row['id']}_$i.$TYPE\r\n";
                        }

            } else { 

                    if(!empty($row['tvg_id'])) $tvg_id = "tvg-id=\"{$row['tvg_id']}\"";
                    else $tvg_id = '';


                    $logo = '';
                    if(is_file("logos/{$row['logo']}"))
                            $logo=  "tvg-logo=\"{$PRTC}//$ADDR/logos/{$row['logo']}\"";

                    $channels .= "#EXTINF:-1 $logo $tvg_id group-title=\"{$row['category_name']}\",{$row['name']}\r\n" ;
                    $channels .= "{$PRTC}//$ADDR/load/$username/$token/{$row['id']}.$TYPE\r\n";
                                        
                    if(!empty($row['adaptive_opt'])) 
                        foreach(json_decode($row['adaptive_opt'],1) as $i => $v ) { 
                            $channels .= "#EXTINF:-1 $logo $tvg_id group-title=\"{$row['category_name']}\",{$row['name']} {$v['profile_name']}\r\n" ;
                            $channels .= "{$PRTC}//$ADDR/load/$username/$token/{$row['id']}_$i.$TYPE\r\n";
                        }


            }

}

//LISTING VODS
if($device['hide_vods_from_playlist'] == 0 ) {  

    $r=$ACES->query("SELECT o.*,cat.name as category_name, cat.adults FROM iptv_ondemand_in_bouquet p INNER JOIN iptv_ondemand o ON ( o.id = p.video_id )
                    LEFT JOIN iptv_stream_categories cat ON o.category_id = cat.id 
                    WHERE o.enable = 1  AND o.status = 1 AND p.bouquet_id IN ('{$device['bouquets']}') GROUP BY o.id ORDER BY p.i ");

    while($row=mysqli_fetch_array($r)) {

                    if($row['adults'] && $device['adults'] || !$row['adults'])
                            if($TYPE == 'ini' ) {
                                    $channels .= "{$row['name']}={$PRTC}//$ADDR/load/vod/$username/$token/{$row['id']}.mp4\r\n";

                            } else if( $TYPE == 'simple' )  {

//                                    $channels .= "#EXTINF:-1,{$row['name']}\r\n" ;
//                                    $channels .= "{$PRTC}//$ADDR/movie/$username/$token/{$row['id']}.mp4\r\n";

                                if($row['type'] == 'series') {

                                    $r_episode = $ACES->query(" SELECT e.*,s.number as season_number  FROM iptv_series_season_episodes e 
                                                    INNER JOIN iptv_series_seasons s on ( s.id = e.season_id ) 
                                                    WHERE s.series_id = {$row['id']} ORDER BY s.number,e.number ");

                                    while($row_episode=mysqli_fetch_array($r_episode)) {

                                        $channels .= "#EXTINF:-1,{$row['name']} S{$row_episode['season_number']} E{$row_episode['number']}\r\n" ;
                                        $channels .= "{$PRTC}//$ADDR/series/$username/$token/{$row_episode['id']}.mp4\r\n";

                                    }


                                } else {

                                    $channels .= "#EXTINF:-1,{$row['name']}\r\n" ;
                                    $channels .= "{$PRTC}//$ADDR/movie/$username/$token/{$row['id']}.mp4\r\n";

                                }

                            } else {
                                    $logo = null;
                                    if($row['logo']) $logo = "tvg-logo=\"{$PRTC}//$ADDR/logos/{$row['logo']}\"";

                                    if($row['type'] == 'series') { 

                                        $r_episode = $ACES->query(" SELECT e.*,s.number as season_number  FROM iptv_series_season_episodes e 
                                                    INNER JOIN iptv_series_seasons s on ( s.id = e.season_id ) 
                                                    WHERE s.series_id = {$row['id']} ORDER BY s.number,e.number ");

                                        while($row_episode=mysqli_fetch_array($r_episode)) { 

                                            $channels .= "#EXTINF:-1 group-title=\"{$row['category_name']}\" tvg-id=\"aces-movie-id-{$row['id']}\" $logo,{$row['name']} S{$row_episode['season_number']} E{$row_episode['number']}  {$row_episode['title']}\r\n" ;
                                            $channels .= "{$PRTC}//$ADDR/series/$username/$token/{$row_episode['id']}.mp4\r\n";

                                        }


                                    } else { 

                                        $channels .= "#EXTINF:-1 group-title=\"{$row['category_name']}\" tvg-id=\"aces-movie-id-{$row['id']}\" $logo,{$row['name']}\r\n" ;
                                        $channels .= "{$PRTC}//$ADDR/movie/$username/$token/{$row['id']}.mp4\r\n";

                                    }

                            }
    }
}
	
	





ob_clean();
header('Content-type: application/force-download');
if($TYPE == 'ini' ) {
	 header("Content-Disposition: attachment; filename=addons.ini");
	 header("Content-type: text/plain");
} else { 
	header("Content-Disposition: attachment; filename=playlist.m3u8");
	header("Content-type: text/plain");
	//header("Content-type: application/vnd.apple.mpegurl");
}

echo $channels;

die;


