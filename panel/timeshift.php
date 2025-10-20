<?php

//DEPRECATED. WILL USE LOADVODS.PHP INSTEAD
DIE;

//error_log($_GET['username']);

if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) { exit(); } 

if(empty($_GET['token']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['token']) || empty($_GET['username']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['username'])  ){ sleep(5); die; }

if(empty($_GET['chan_id']) || !is_numeric($_GET['chan_id']) || $_GET['chan_id'] < 1 ) {  sleep(5); die; } 

$start=explode(":",$_GET['start']);
$start[1]=str_replace('-',':',$start[1]);
$start = "{$start[0]} {$start[1]}:00";
if(!$start_time=strtotime($start)) { sleep(5); die; } 

$ADDR = $_SERVER['HTTP_HOST'];

ignore_user_abort(true);
//ob_start();

error_log("TIMESHIFT");

require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPInfo.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Server.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Armor/Armor.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Firewall.php";

include "/home/aces/stream/config.php";
$ACES = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($ACES->connect_errno > 0) die;

$r = $ACES->query("SELECT id,limit_connections,ignore_block_rules,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,
       lock_ip_address,user_agent,tmp_block,auto_ip_lock,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in 
FROM iptv_devices  
WHERE token = '{$_GET['token']}' and username = '{$_GET['username']}' ");

if($DEVICE = $r->fetch_assoc()) { 
    
    $user_agent = $ACES->real_escape_string($_SERVER['HTTP_USER_AGENT']);
    
    //ACCOUNT IS EXPIRED.
    if($DEVICE['expire_in'] < 1 ) {
        sleep(5);die;
    }  

    $DEVICE_ID  = $DEVICE['id'];
      //$STREAM_SERVER = $DEVICE['stream_server'];
    $LIMIT_CONNECTION = $DEVICE ['limit_connections'];
    if(empty($LIMIT_CONNECTION)) $LIMIT_CONNECTION = 1;


    if($DEVICE['status'] > 1 ) { sleep(5); die; } 


    $ip_info=null;
    if(!$DEVICE['ignore_block_rules']) {

        //include_once $_SERVER['DOCUMENT_ROOT']."/ACES2/init.php";

        try {

            $IPInfo = new \ACES2\IPInfo($_SERVER['REMOTE_ADDR']);

            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$IPInfo->org')) AND type = 3  ");
            if($rb->num_rows>0) {  sleep(5); die; }

            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$user_agent') 
                                    LIKE lower(concat('%',value,'%')) AND type = 2 ");

            if($rb->num_rows>0) { sleep(5); die; }

        } catch(\Exception $e) { }

        //$rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('{$ip_info['org']}') LIKE lower(concat('%',value,'%')) AND type = 3  ");
            
        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('{$ip_info['org']}') LIKE lower(concat('%',value,'%')) AND type = 3  ");
        if($rb->num_rows>0) { sleep(5); die; }


        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$user_agent') LIKE lower(concat('%',value,'%')) AND type = 2 ");
        if($rb->num_rows>0) {  sleep(5); die; }


    } 


//TODO: ADD HERE IPTV_STREAMS BLOCK
} else{  
    $ARMOR = new Armor();
    $ARMOR->action('iptv-account');
    sleep(5); die; 
}

//$r2 = $ACES->query("SELECT id,server_id FROM iptv_recording WHERE chan_id  = '{$_GET['chan_id']}' AND status = 3
//                                          AND start_time = FROM_UNIXTIME('$start_time')  ");
$StartDate = date("Y-m-d H:i:s",$start_time);
$r2 = $ACES->query("SELECT id,server_id FROM iptv_recording WHERE chan_id  = '{$_GET['chan_id']}' AND status = 3 
                                          AND start_time = '$StartDate'  ");
if($row=$r2->fetch_assoc()) {
    $vid_file = $row['id'];
    $SERVER_ID = $row['server_id']; } 
else die;

$S_SERVER=null;
$r3=$ACES->query("SELECT * FROM iptv_servers WHERE id = '$SERVER_ID'  LIMIT 1");
if(!($S_SERVER=mysqli_fetch_array($r3))) {

	//SERVER NOT FOUND ??? 
	DIE;
}



$token = md5(rand(1,99999).time().time().rand(1,999999999));

$ACES->query("INSERT INTO iptv_access (device_id,vod_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
VALUES('$DEVICE_ID','$vid_file','{$S_SERVER['id']}','{$S_SERVER['address']}','$token','{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_USER_AGENT']}','MPEGTS',NOW() + interval 30 SECOND,NOW() + interval 30 MINUTE,NOW())");
$aid = $ACES->insert_id;


$channel = "http://{$S_SERVER['address']}:{$S_SERVER['port']}/stream/record/$aid:$token/$vid_file.ts";

header("HTTP/1.1 302 Moved Permanently");
header("Location: ".$channel );
header("Connection: keep-alive");


fastcgi_finish_request();

$ACES->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = $DEVICE_ID ");


if($DEVICE['auto_ip_lock'] > 0 ) { 
    
    $r_autolock=$ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID  AND ip_address != '{$_SERVER['REMOTE_ADDR']}' ");  
    
}

if($LIMIT_CONNECTION>1) sleep(30);


//LIMIT CONNECTION
$r0=$ACES->query("SELECT id FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ");

if( $r0->num_rows >= $LIMIT_CONNECTION ) {
    
    if($del = $r0->num_rows - $LIMIT_CONNECTION )  
        $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ORDER BY limit_time  LIMIT $del");
        
    //BECAUSE LIMIT CONNECTION WERE EXECED THE MOVIE THAT BEEN PAUSED FOR AWHILE WILL BE REMOVED.
    $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time < NOW() AND end_time > NOW() ");
	
}

//LIMIT CONNECTION
//$r0=$ACES->query("SELECT id FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW()  ");
//
//if( $r0->num_rows > $LIMIT_CONNECTION ) {
//	$del = $r0->num_rows - $LIMIT_CONNECTION; 
//	$ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ORDER BY id ASC LIMIT $del");
//	
//}

$user_agent = $ACES->real_escape_string($_SERVER['HTTP_USER_AGENT']);
if($DEVICE['ip_address'] != $_SERVER['REMOTE_ADDR'] || $DEVICE['user_agent'] != $user_agent ) {
    //UPDATE ONLY IF HAVE CHANGED.
    $ACES->query("UPDATE iptv_devices SET  ip_address = '{$_SERVER['REMOTE_ADDR']}', user_agent = '$user_agent' WHERE id='$DEVICE_ID' ");
}


$r=$ACES->query("SELECT id FROM iptv_video_play_count WHERE account_id = $DEVICE_ID AND video_file_id = $vid_file AND NOW() + INTERVAL 1 DAY > NOW() ");
if(!mysqli_fetch_array($r)) { 

    $ACES->query("INSERT INTO iptv_video_play_count (account_id,video_file_id,date) VALUES($DEVICE_ID,$vid_file,NOW() ) ");
    
}

die;