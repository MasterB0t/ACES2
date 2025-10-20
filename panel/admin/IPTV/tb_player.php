<?php

$ACES = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if(!adminIsLogged(false)) {
    die();

} else if(!$ACES->hasPermission('iptv.streams')) {
    die();
}

$stream = $_GET['id'];
if(!is_numeric($stream)) { sleep(1); die(); }


$r = $DB->query("SELECT strm.status as streaming_status,c.id, c.enable,c.stream,c.stream_server,c.name FROM iptv_channels c
		LEFT JOIN iptv_streaming strm ON (c.id = strm.chan_id and c.stream_server = strm.server_id )
		WHERE c.id = $stream
		LIMIT 1
");
if(!$row=$r->fetch_assoc()) {  sleep(1); echo "<h1> Start the stream or wait until it connect.</h1>"; DIE; }

if($row['stream'] == 0 ) { sleep(1); die(); }

if($row['streaming_status'] == 0) { sleep(1); echo "<h1> Please wait until it connect and refresh this page.</h1>";  die; }

else if($row['streaming_status'] == 2 ) {

    $Server = new \ACES2\IPTV\Server($row['stream_server']);

    $Server->send_action(\ACES2\IPTV\Server::ACTION_RESTART_STREAM,array('stream_id' => $stream ) );

//    list($server_status,$null) = $IPTV->sendServerAction($row['stream_server'], 'restart_stream', array('stream_id' => $stream ));
//    if(!$server_status) { echo "<h1> Unknown Error.</h1>"; die; }

    //WATTING FOR THE STREAM TO CONNECT.
    $wtn = 0;
    while( true ) {

        sleep(1);
        $r_srm = $DB->query("SELECT id FROM iptv_streaming where chan_id = $stream and status = 1 ");
        if(mysqli_num_rows($r_srm) > 0 ) break;
        if($wtn > 30 ) { echo "<h1> Stream take to long to start.</h1>"; die; }
        $wtn++;

    }

}


$strm_id = $row['id'];
$token = md5(rand(1,99999).time().time().rand(1,999999999));

$SERVER_ID = $row['stream_server'];
$S_SERVER=null;

$r2=$DB->query("SELECT * FROM iptv_servers WHERE id = $SERVER_ID  LIMIT 1");
if(!($S_SERVER=$r2->fetch_assoc())) { echo "<h1> Error occurred.</h1>"; DIE; }//SERVER NOT FOUND ???


if($S_SERVER['is_array']) {
    $r2 = $DB->query("SELECT * FROM iptv_servers WHERE id = {$S_SERVER['main_server']}  LIMIT 1");
    if(!($S_SERVER=$r2->fetch_assoc())) { echo "<h1> Error occurred.</h1>"; DIE; }
}



$r=$DB->query("INSERT INTO iptv_access (device_id,chan_id,server_id,server_ip,token,ip_address,user_agent,limit_time,end_time,add_date) 
VALUES('0','$strm_id','{$S_SERVER['id']}','{$S_SERVER['address']}','$token','{$_SERVER['REMOTE_ADDR']}','',NOW() + interval 5  SECOND,NOW(),NOW())");
$aid = $DB->insert_id;


$url = "http://{$S_SERVER['address']}:{$S_SERVER['port']}/stream/$aid/$token/$strm_id-.m3u8";


?><html>
<head>
    <script src="/dist/js/hls.js"></script>
</head>
<body>
<style>
    h1 { text-align:center; }
    video { width:100%; }
</style>
<h1>WEB PLAYER</h1>

<video controls id="video"></video>
<script>
    if(Hls.isSupported()) {
        var video = document.getElementById('video');
        var hls = new Hls();
        hls.loadSource('<?=$url;?>');
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED,function() {
            video.play();
        });
    }
</script>
</body>
</html>