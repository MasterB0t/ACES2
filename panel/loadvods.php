<?php

if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) { exit(); }


error_reporting(0);


$ADDR = $_SERVER['HTTP_HOST'];


if(isset($_GET['mac'])) {
    if( !preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $_GET['mac'])) { sleep(5); die; } 
} else { 
    if(empty($_GET['token']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['token']) || empty($_GET['username']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['username']) ){  sleep(5); die; }
}

$types = array('movie', 'episode', 'record', 'timeshift');
$vid_type = $_GET['type'];
if(!in_array($vid_type, $types))
    $vid_type = 'movie';


if($_GET['type'] == 'movie') $vid_type = 'movie';
else if($_GET['type'] == 'episode' ) $vid_type = 'episode';
else if($_GET['type'] == 'record') $vid_type = 'record';
else if($_GET['type'] == 'timeshift') $vid_type = 'timeshift';
else  $vid_type = 'movie';


if(empty($_GET['vods'])) die;

ignore_user_abort(true);
//ob_start();

$S= explode('.',$_GET['vods']);
if(!$vod_id = (int)$S[0])
    die;
$type = $S[1];



include "/home/aces/stream/config.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPInfo.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Server.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Armor/Armor.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Firewall.php";

$ACES = new \ACES2\DB($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($ACES->connect_errno > 0) die;

@define('DB_NAME', $DATABASE );
@define('DB_USER', $DBUSER );
@define('DB_PASS', $DBPASS );
@define('DB_HOST', $DBHOST );


$username = $ACES->escape_string($_GET['username']);
$password = $ACES->escape_string($_GET['token']);
$UserAgent = $ACES->escString($_SERVER['HTTP_USER_AGENT']);

//if(isset($_GET['mac'])) $r = $ACES->query("SELECT id,limit_connections,ignore_block_rules,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,lock_ip_address,user_agent,auto_ip_lock,tmp_block,bouquets,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in  FROM iptv_devices WHERE mag = '{$_GET['mac']}'  ");
//else
$r = $ACES->query("SELECT id,limit_connections,ignore_block_rules,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,
   lock_ip_address,user_agent,auto_ip_lock,tmp_block,bouquets,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in 
    FROM iptv_devices  WHERE token = '$password' AND username =  '$username' ");


if(!$DEVICE = $r->fetch_assoc()) {

    $Armor = new \ACES2\ARMOR\Armor();
    $Armor->log_ban("iptv-account");
    if($Armor->isBan('iptv-account')) {
        $fw = ACES2\Firewall::add(
            'INPUT',
            'DROP',
            $_SERVER['REMOTE_ADDR'],
            '',
            '',
            '',
            'AUTO BLOCK'
        );
        $fw->appendRule(1);
    }

    sleep(5); die;
}

//ACCOUNT IS EXPIRED OR NOT ENABLED.
if($DEVICE['expire_in'] < 1 || $DEVICE['status'] > 1 ) {
    sleep(5);die;
}


$DEVICE_ID  = $DEVICE['id'];
//$STREAM_SERVER = $DEVICE['stream_server'];
$LIMIT_CONNECTION = (int)$DEVICE ['limit_connections'];
if(empty($LIMIT_CONNECTION)) $LIMIT_CONNECTION = 1;


if($DEVICE['lock_ip_address']) {
    $r_iplock=$ACES->query("SELECT id FROM iptv_account_locks WHERE type = 1 AND account_id = '$DEVICE_ID' 
                                AND value = '{$_SERVER['REMOTE_ADDR']}' ");
    if($r_iplock->num_rows < 1 ) { sleep(5); die; }
}

if($DEVICE['lock_user_agent']) {
    $r_iplock=$ACES->query("SELECT id FROM iptv_account_locks WHERE type = 2 
                                AND account_id = '$DEVICE_ID' AND value = lower('$UserAgent') ");
    if($r_iplock->num_rows < 1 ) { sleep(5); die; }
}


if(!$DEVICE['ignore_block_rules']) {

    //include_once $_SERVER['DOCUMENT_ROOT']."/ACES2/init.php";

    //NO NEEDED ? FIREWALL WILL BLOCK IT INSTEAD.
//            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND type = 1  "); 
//            if(mysqli_fetch_array($rb)) { 
//                sleep(5); die;
//            }

    try {
        $IPInfo = new \ACES2\IPInfo($_SERVER['REMOTE_ADDR']);

        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$IPInfo->org')) AND type = 3  ");
        if($rb->num_rows>0) {  sleep(5); die; }

        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$UserAgent') 
                                LIKE lower(concat('%',value,'%')) AND type = 2 ");

        if($rb->num_rows>0) { sleep(5); die; }

    }
    catch(\Exception $e  ) { }
    catch (\Error $e) {
        error_log("{$_SERVER['REMOTE_ADDR']}: $UserAgent");
        error_log($e->getMessage());
    }


//        if( is_array($ip_info) )
//            $ACES->query("UPDATE iptv_devices set ip_org = '{$ip_info['org']}', ip_geo = '{$ip_info['country_code']}'
//
//                    WHERE id = {$DEVICE['id']} ");


}



$DEVICE['bouquets'] = join("','", unserialize($DEVICE['bouquets']) );
if($vid_type == 'movie') {  $f_sql = "f.movie_id = $vod_id ";

    $r = $ACES->query("SELECT o.id,o.name as movie_name, f.server_id,cat.adults,f.id as vid_file,f.transcoding,f.source_file, o.status as status, f.container 
            FROM iptv_ondemand_in_bouquet p 
            INNER JOIN iptv_ondemand o ON ( o.id = p.video_id )
            INNER JOIN iptv_stream_categories cat ON ( cat.id = o.category_id )
            INNER JOIN iptv_video_files f ON ( $f_sql ) 
            WHERE o.id = $vod_id AND o.status = 1 and o.enable = 1 AND p.bouquet_id IN ('{$DEVICE['bouquets']}') " );

} else if($vid_type == 'episode') {

    $r = $ACES->query("SELECT f.id as vid_file, f.server_id,f.transcoding,f.source_file, cat.adults, e.status as status , f.container 
        FROM iptv_ondemand_in_bouquet p INNER JOIN iptv_series_season_episodes e ON e.id = $vod_id
        INNER JOIN iptv_series_seasons s ON ( s.id = e.season_id )
        INNER JOIN iptv_ondemand o ON ( s.series_id = o.id AND o.id = p.video_id ) 
        INNER JOIN iptv_stream_categories cat ON ( cat.id = o.category_id )
        INNER JOIN iptv_video_files f ON ( f.episode_id = $vod_id )
        WHERE e.id = $vod_id AND p.bouquet_id IN ('{$DEVICE['bouquets']}') ");
    
} else if($vid_type == 'record') {

    $vid_id = (int)$vod_id;

    $r = $ACES->query("SELECT id as vid_file,server_id,  1 as status, container FROM iptv_recording WHERE id = $vod_id AND status = 3 ");
    
} else if($vid_type == 'timeshift') {

    $start=explode(":",$_GET['start']);
    $start[1]=str_replace('-',':',$start[1]);
    $start = "{$start[0]} {$start[1]}:00";
    if(!$start_time=strtotime($start)) { sleep(5); die; }

    $StartDate = date("Y-m-d H:i:s",$start_time);
    $r = $ACES->query("SELECT id as vid_file,server_id, 1 as status, container FROM iptv_recording WHERE chan_id  = '$vod_id' AND status = 3 
                                          AND start_time = '$StartDate'  ");

}

//VIDEO NOT FOUND IN DB
if(!$row=$r->fetch_assoc()) {
    sleep(3); die;
}

//MOVIE/EPISODE ISN'T READY
if($row['status'] != 1 ) { sleep(3); die; }


$vid_file = $row['vid_file'];
$format = $row['container'];

$SERVER_ID = $row['server_id'];


if($row['adults'] && !$DEVICE['adults'] ) { sleep(1); die;}
//if($row['adults'] && $DEVICE['adults_with_pin'] && $DEVICE['pin'] != $_GET['pin'] ) { sleep(1); die; }
if($DEVICE['auto_ip_lock'] > 0 ) { 
    
    $r_autolock=$ACES->query("SELECT id FROM iptv_access WHERE limit_time > NOW() AND device_id = $DEVICE_ID  AND ip_address != '{$_SERVER['REMOTE_ADDR']}' ");  
    if(mysqli_fetch_array($r_autolock)) { sleep(3); die; } 
}

//LIMIT NEW CONNECTION FOR STREAM VODS.
if($row['transcoding'] == 'stream') {

    $r=$ACES->query("SELECT id FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW()  ");
    if($r->num_rows >= $LIMIT_CONNECTION) {
        sleep(2);
        exit;
    }
}



if($row['transcoding'] == 'redirect') {

    header("HTTP/1.1 302 Moved Permanently");
    header("Location: ".$row['source_file'] );
    header("Connection: keep-alive");
    exit;

} else {

    $S_SERVER=null;
    $r3=$ACES->query("SELECT id,port,sport,address,ip_address,api_token FROM iptv_servers WHERE id = '$SERVER_ID'  LIMIT 1");
    if(!$S_SERVER = $r3->fetch_assoc())
        die;

    $token = md5(rand(1,99999).time().rand(1,999999999));

    $ACES->query("INSERT INTO iptv_access (device_id,vod_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
        VALUES('$DEVICE_ID','$vid_file','{$S_SERVER['id']}','{$S_SERVER['address']}','$token','{$_SERVER['REMOTE_ADDR']}','$UserAgent','$format',NOW() + interval 30 SECOND, NOW() + interval 30 MINUTE,NOW())  ");

    $aid = $ACES->insert_id;

    $PRTC = 'http:'; $PORT = $S_SERVER['port'];
    if( isset($_SERVER['HTTPS']) && $S_SERVER['sport'] ) { $PRTC = 'https:'; $PORT = $S_SERVER['sport']; }
    //if( isset($_SERVER['HTTPS'] )  && $S_SERVER['sport'] != 0 ) { $PRTC = 'https:'; $PORT = $S_SERVER['sport']; }

    //$channel = "http://{$S_SERVER['address']}:{$S_SERVER['port']}/stream/vod/$aid:$token/$vod_id-.mpegts";
    //if($type == 'm3u8')$channel = "http://{$S_SERVER['address']}:{$S_SERVER['port']}/stream/vod/$aid:$token/$vid_file-.m3u8";
    //else $channel = "http://{$S_SERVER['address']}:{$S_SERVER['port']}/stream/vod/$aid:$token/$vid_file-.mpegts";

    $PORT = $PORT == "443" || $PORT == "80" ?  "" : ":$PORT";

    if($vid_type == 'record' || $vid_type == 'timeshift') $channel = $PRTC."//{$S_SERVER['address']}{$PORT}/stream/record/$aid:$token/$vid_file.ts";
    else if($row['transcoding'] == 'stream')  $channel = $PRTC."//{$S_SERVER['address']}{$PORT}/stream/svod/$aid:$token/$vid_file.mp4";
    else $channel = $PRTC."//{$S_SERVER['address']}{$PORT}/stream/vod/$aid:$token/$vid_file.mp4";

}


header("HTTP/1.1 302 Moved Permanently");
header("Location: ".$channel );
header("Connection: keep-alive");
#header('Access-Control-Allow-Origin: *');

fastcgi_finish_request();


$ACES->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = $DEVICE_ID ");

///THIS HELP FOR MULTI CONNECTION TO PREVENT CUTTING OF ON ONE PLAYER WHILE THE OTHER IS SWAPING.
/// SINCE WE GAVE 30 SECONDS TO end_time ON CONNECTION LET WAIT 30 SECONDS TO DECIDE WHICH CONNECTION TO KILL.
if($LIMIT_CONNECTION>1)
    sleep(30);

//LIMIT CONNECTION
$r0=$ACES->query("SELECT id FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW()  ");


if( $r0->num_rows >= $LIMIT_CONNECTION ) {

    $del = $r0->num_rows - $LIMIT_CONNECTION;
    
    if($del) {
        //WE ALWAYS CUT OLDEST CONNECTION IF LIMIT IS 1. OR PLAYER WILL NOT BE ABLE TO SWAP FASTER.
        if($LIMIT_CONNECTION == 1 ) {
            $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID
                            ORDER BY id LIMIT $del");
        } else {
            //HERE IS WHERE THE MAGIC HAPPENS FOR MULTI
            $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID
                          AND limit_time > NOW() ORDER BY limit_time ASC LIMIT $del");
        }

    }

    //BECAUSE LIMIT CONNECTION WERE EXCEED THE MOVIE THAT BEEN PAUSED FOR A WHILE WILL BE REMOVED.
    $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID 
                          AND limit_time < NOW() AND end_time > NOW() ");
	
}

//DELETING OLDS ACCESS
//$ACES->query("DELETE FROM iptv_access WHERE limit_time < NOW() ");

//COUNTING CLIENTS.
//$r=$ACES->query("SELECT id FROM iptv_access WHERE limit_time > NOW() AND server_id = '{$S_SERVER['id']}' ") ;
//$ACES->query("UPDATE iptv_servers SET clients = '$r->num_rows' WHERE id = '{$S_SERVER['id']}' ") ;



if($DEVICE['ip_address'] != $_SERVER['REMOTE_ADDR'] || $DEVICE['user_agent'] != $UserAgent ) {
    //UPDATE ONLY IF IT HAVE CHANGED.
    $ACES->query("UPDATE iptv_devices SET ip_address = '{$_SERVER['REMOTE_ADDR']}', user_agent = '$UserAgent' 
                    WHERE id='$DEVICE_ID' ");
}

//NO NEED TO COUNT PLAYS FOR RECORDING OR TIMESHIFT.
if($vid_type == 'recording' || $vid_type == 'timeshift')
    exit;

$r=$ACES->query("SELECT id FROM iptv_video_play_count
          WHERE account_id = $DEVICE_ID AND video_file_id = $vid_file AND NOW() + INTERVAL 1 DAY > NOW() ");
if($r->num_rows < 1) {
    $ACES->query("INSERT INTO iptv_video_play_count (account_id,video_file_id,date)
        VALUES($DEVICE_ID,$vid_file,NOW() ) ");
}
