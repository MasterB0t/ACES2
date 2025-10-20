<?php

if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) { exit(); }

//error_reporting(0);

$ADDR = $_SERVER['HTTP_HOST'];



$sql_u = '';
if(empty($_GET['token']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['token'])  ){  sleep(10); die; }
if(!is_file('/home/aces/no_username')) {
    if( empty($_GET['username']) || !preg_match('/^[a-zA-Z0-9-]+$/',$_GET['username'])  ) {  sleep(10); die; }
    $sql_u = " AND username = '{$_GET['username']}' " ;
}


$ADPT = '';
$S= explode('.',$_GET['stream']);
if (strpos($S[0], '_') !== false) {
    list($strm_id,$RES) = explode('_',$S[0]);
    $ADPT = "_".str_replace('-','',$RES);

} else { $strm_id = (int)$S[0]; $type = $S[1]; }


if(empty($strm_id ) || !is_numeric( $strm_id )) { sleep(5); die; }

if($type == 'mpegts' ) $format = 'MPEGTS';
else if($type == 'm3u8') $format = 'HLS';
else if($type == 'ts' ) { $format = 'MPEGTS'; $type = 'mpegts'; }
else { $format = 'MPEGTS'; $type = 'mpegts'; } //DEFAULT..

include "/home/aces/stream/config.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPInfo.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Server.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Armor/Armor.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Firewall.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/AccountLog.php";

$ACES = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($ACES->connect_errno > 0) { sleep(5); sleep(5); die; }

@define('DB_NAME', $DATABASE );
@define('DB_USER', $DBUSER );
@define('DB_PASS', $DBPASS );
@define('DB_HOST', $DBHOST );

$r=$ACES->query("SELECT id FROM iptv_streaming WHERE chan_id = '$strm_id' AND status > 0 ");
//if(!$r->fetch_assoc()) { sleep(5); die; }

//$sql_u = $ACES->escape_string($sql_u);

//$r = $ACES->query("SELECT id,premium,stream_server,last_stream_server,limit_connections FROM iptv_devices WHERE token = '{$_GET['token']}' AND subcription >= CURDATE()  ");
//if(isset($_GET['mac'])) $r = $ACES->query("SELECT id,limit_connections,security_level,bouquets,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,lock_ip_address,auto_ip_lock,user_agent,tmp_block,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in  FROM iptv_devices WHERE mag = '{$_GET['mac']}'  ");
//if(is_file("/home/aces/no_u"))  $r = $ACES->query("SELECT id,limit_connections,security_level,bouquets,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,lock_ip_address,auto_ip_lock,user_agent,tmp_block,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM iptv_devices  WHERE token = '{$_GET['token']}' $sql_u    ");
//else
$r = $ACES->query("SELECT id,limit_connections,ignore_block_rules,bouquets,status,adults,pin,adults_with_pin,lock_user_agent,ip_address,lock_ip_address,auto_ip_lock,user_agent,tmp_block,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM iptv_devices  WHERE token = '{$_GET['token']}' $sql_u    ");



if($DEVICE = $r->fetch_assoc()) {

    $UserAgent = $ACES->real_escape_string($_SERVER['HTTP_USER_AGENT']);


    //ACCOUNT IS EXPIRED.
    if($DEVICE['expire_in'] < 1) {
        sleep(5);die;
    }


    $DEVICE_ID  = $DEVICE['id'];
    $LIMIT_CONNECTION = $DEVICE ['limit_connections'];
    if(empty($LIMIT_CONNECTION)) $LIMIT_CONNECTION = 1;

    //DEVICE IS DISABLED OR BLOCK
    if($DEVICE['status'] > 1 ) { sleep(30); die; }


    if($DEVICE['lock_ip_address']) {
        $r_iplock=$ACES->query("SELECT id FROM iptv_account_locks WHERE type = 1 AND account_id = '$DEVICE_ID' 
                                    AND value = '{$_SERVER['REMOTE_ADDR']}' ");
        if($r_iplock->num_rows < 1 ) { sleep(5); die; }
    }

    if($DEVICE['lock_user_agent']) {
        $r_iplock=$ACES->query("SELECT id FROM iptv_account_locks WHERE type = 2 AND account_id = '$DEVICE_ID' 
                                    AND value = lower('$UserAgent') ");
        if($r_iplock->num_rows < 1 ) { sleep(5); die; }
    }


    $ip_info=null;$tmp_block=0;
    if(!$DEVICE['ignore_block_rules']) {

//NO NEEDED ? FIREWALL WILL BLOCK IT INSTEAD.
//            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND type = 1  "); 
//            if(mysqli_fetch_array($rb)) { 
//                sleep(5); die;
//            }


        //NO NEEDED ? FIREWALL WILL BLOCK IT INSTEAD.
//            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND type = 1  ");
//            if(mysqli_fetch_array($rb)) {
//                sleep(5); die;
//            }

        try {

            $IPInfo = new \ACES2\IPInfo($_SERVER['REMOTE_ADDR']);

            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$IPInfo->org') AND type = 3  ");
            if($rb->num_rows>0) {  sleep(5); die; }

            $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$UserAgent') 
                                    LIKE lower(concat('%',value,'%')) AND type = 2 ");

            if($rb->num_rows>0) { sleep(5); die; }

        } catch(\Exception $e) { }


        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('{$ip_info['org']}') LIKE lower(concat('%',value,'%')) AND type = 3  ");
        if($rb->fetch_assoc()) { $tmp_block = 1; sleep(30); die; }


        $rb=$ACES->query("SELECT id FROM iptv_blocks WHERE lower('$UserAgent') LIKE lower(concat('%',value,'%')) AND type = 2 ");
        if($rb->fetch_assoc()) { $tmp_block = 1; sleep(30); die; }

    }

//TODO: ADD HERE IPTV_STREAMS BLOCK
} else{

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

$DEVICE['bouquets'] = join("','", unserialize($DEVICE['bouquets']) );

$r = $ACES->query("SELECT  c.enable,c.ondemand,c.stream,c.stream_server,c.name,cat.adults FROM iptv_channels_in_bouquet p 
		INNER JOIN iptv_channels c ON (c.id = p.chan_id) 
		INNER JOIN iptv_stream_categories cat ON ( cat.id = c.category_id)
		WHERE p.bouquet_id IN ('{$DEVICE['bouquets']}') AND $strm_id = p.chan_id
		
");

//CHANNEL NOT FOUND.
if(!$row=$r->fetch_assoc()) { sleep(5); die; }
//if(!$r->num_rows){ sleep(5);  die; } //CHANNEL NOT FOUND.


//BUG???: THIS IS WRONG?!?!?!
//GETTING THE SERVER FROM iptv_channels instead of iptv_streaming.
//$SERVER_ID = $row['stream_server'];

if($row['enable'] == 0 ) { sleep(5); die; }
if($row['adults'] && !$DEVICE['adults'] ) { sleep(5); die;}
//if($row['adults'] && $DEVICE['adults_with_pin'] && $DEVICE['pin'] != $_GET['pin'] ) { sleep(1); die; } 

if($DEVICE['auto_ip_lock'] > 0 ) {

    $r_autolock=$ACES->query("SELECT id FROM iptv_access 
          WHERE limit_time > NOW() AND device_id = $DEVICE_ID  AND ip_address != '{$_SERVER['REMOTE_ADDR']}' ");
    if($r_autolock->fetch_assoc()) { sleep(5); die; }
}


if($row['stream'] == 0 ) {

    //NOT FOR RESTREAM GETTING SOURCE AND REDIRECTING.
    $r2=$ACES->query("SELECT url FROM  iptv_channels_sources WHERE chan_id = $strm_id  and enable = 1 ORDER BY priority LIMIT 1 ");
    if(!$row2=$r2->fetch_assoc()) { die; } //NO SOURCES
    else {

        header("HTTP/1.1 302 Moved Permanently");
        header("Connection: close");
        header("Content-Type: application/vnd.apple.mpegurl");
        header("Location: ".$row2['url'] );

        //ob_end_flush();ob_flush();flush();

        die;
    }

}


//$rs=$ACES->query("SELECT count(*) as access, s.server_id, s.status as streaming_status,s.no_client FROM iptv_streaming s
//    LEFT JOIN iptv_access a ON a.chan_id = $strm_id AND a.server_id = s.server_id
//    WHERE  s.no_client = 0 AND s.chan_id = $strm_id AND s.status = 1 OR s.status = 2 AND s.chan_id = $strm_id
//    AND s.server_id = {$row['stream_server']} GROUP BY s.id ORDER BY access ASC LIMIT 1
//");

//FOR ONDEMAND STREAM WE CANT CHECK STATUS IS CONNECTED CUZ IT WILL CLOSE CLIENT TILL STREAM CONNECT TO A SOURCE
$sql_status = $row['ondemand'] ? ' ' :  " s.status = 2 AND ";

$rs=$ACES->query("SELECT count(*) as access, s.server_id, s.status as streaming_status,s.no_client FROM iptv_streaming s 
    LEFT JOIN iptv_access a ON a.chan_id = $strm_id AND a.server_id = s.server_id
    WHERE  s.no_client = 0 AND s.chan_id = $strm_id AND s.status = 1 OR $sql_status s.chan_id = $strm_id 
    AND s.server_id = {$row['stream_server']} GROUP BY s.id ORDER BY access ASC LIMIT 1
");

if(!$strm_row=$rs->fetch_assoc()){
    //NO STREAMING.
    http_response_code(404);
    sleep(1);
    die;
}



$token = md5(rand(1,99999).time().rand(1,999999999));


$S_SERVER=null;$r2=null;

$r3=$ACES->query("SELECT id,port,sport,address,ip_address,api_token FROM iptv_servers WHERE id = {$strm_row['server_id']}  LIMIT 1");
if(!($S_SERVER=$r3->fetch_assoc())) { sleep(4); DIE; } //SERVER NOT FOUND


$PRTC = 'http:'; $PORT = $S_SERVER['port'];
if( isset($_SERVER['HTTPS'] ) && (int)$S_SERVER['sport'] > 0  ) { $PRTC = 'https:'; $PORT = $S_SERVER['sport']; }

//ONDEMAND!!!!!
if($strm_row['streaming_status'] == 2 ) {
    //WAITING FOR CLIENTS TO START.

    $curl = curl_init();
    $data =  json_encode(array('api_token' => $S_SERVER['api_token'], 'stream_id' => $strm_id));

    //FOR SOME REASON IF HTTPS IS ENABLED AND RUNNING ON CLOUDFLARE SAVER GET A 400 ERROR.
    //FORCING HTTP WITH IP TO FIX THE ISSUE
//    if($S_SERVER['ip_address'] != '') {
//        $url = "http://88.99.95.225:8080/stream/api2.php?&action=RESTART_STREAM&data=$data";
//    } else {
//        $url = $PRTC."//{$S_SERVER['address']}:{$PORT}/stream/api2.php?&action=RESTART_STREAM&data=$data";
//    }

    //SHALL WE SEND THE ACTION VIA ADDRESS OR IP ???
    $url = $PRTC."//{$S_SERVER['address']}:{$PORT}/stream/api2.php?&action=RESTART_STREAM&data=$data";

    curl_setopt($curl, CURLOPT_URL, $url);

    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl,  CURLOPT_RETURNTRANSFER, false);
    #curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    #curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 100);
    //curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    //curl_setopt($curl, CURLOPT_FAILONERROR, true);

    $resp = curl_exec($curl);

//    if(curl_errno($curl)) {
//        error_log("CURL ERROR".curl_error($curl));
//    } else
//        error_log("CURL RESPONSE $resp");



    curl_close($curl);

}


$ACES->query("INSERT INTO iptv_access (device_id,chan_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
        VALUES('$DEVICE_ID','$strm_id','{$S_SERVER['id']}','{$S_SERVER['address']}','$token','{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_USER_AGENT']}','$format',NOW() + interval 60 SECOND,NOW(),NOW())");
$aid = $ACES->insert_id;

$b = base64_encode("$aid:$token");

$PORT = $PORT == "443" || $PORT == "80" ?  "" : ":$PORT";

if($type == 'm3u8') $channel = $PRTC."//{$S_SERVER['address']}$PORT/stream/$aid/$token/{$strm_id}{$ADPT}-.m3u8";
else if($type == 'mpegts') $channel = $PRTC."//{$S_SERVER['address']}$PORT/stream/$aid/$token/{$strm_id}{$ADPT}-.mpegts";


header("HTTP/1.1 302 Moved Permanently");
header("Location: ".$channel );
header("Connection: close");
header("Content-Type: application/vnd.apple.mpegurl");
header("Content-Length: 0", true);
header('Access-Control-Allow-Origin: *');
//ignore_user_abort(true);
fastcgi_finish_request();


$ACES->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = $DEVICE_ID ");


//THIS FREEZE THE SCRIPT AND DO NOT SEND CLIENT TO LB
//if($LIMIT_CONNECTION>1) sleep(30);


//LIMIT CONNECTION
$r0=$ACES->query("SELECT id FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ");

if( $r0->num_rows >= $LIMIT_CONNECTION ) {

    if($del = $r0->num_rows - $LIMIT_CONNECTION )
        //THIS WAS DISABLED TO PREVENT CUTTING OFF ON MOVIES THAT BEEN PAUSED. BUT CAUSING ISSUES ON SWAPPING STREAMS
        //$ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ORDER BY limit_time ASC LIMIT $del");
        $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time > NOW() ORDER BY id ASC LIMIT $del");

    //BECAUSE LIMIT CONNECTION WHERE EXCEED THE MOVIE THAT BEEN PAUSED FOR A WHILE WILL BE REMOVED.
    //$ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time < NOW() AND end_time > NOW() ");
    $ACES->query("DELETE FROM iptv_access WHERE device_id = $DEVICE_ID AND limit_time < NOW() AND end_time > NOW() ");

}



$r1=$ACES->query("SELECT value FROM settings WHERE name = 'iptv.devicelog' ");
$set = (int)$r1->fetch_assoc()['value'];
if($set > 0  ) {
    \ACES2\IPTV\AccountLog::addLog($DEVICE_ID, 0, $strm_id, $UserAgent, $_SERVER['REMOTE_ADDR'] );
}



//UPDATING LAST IP
if($DEVICE['ip_address'] != $_SERVER['REMOTE_ADDR'] || $DEVICE['user_agent'] != $UserAgent ) {
    //UPDATE ONLY IF HAVE CHANGED.
    $ACES->query("UPDATE iptv_devices SET ip_address = '{$_SERVER['REMOTE_ADDR']}', user_agent = '$UserAgent'
                    WHERE id='$DEVICE_ID' ");
}