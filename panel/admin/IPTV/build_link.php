<?php

function setError($msg) {
    http_response_code(501);
    echo json_encode(array("error" => $msg));
}

function playlist($url, $name = 'Play Now') {

    echo json_encode(array("url" => $url, "name" => $name));
    exit;

}

$Admin = new \ACES2\Admin();
if (!$Admin->isLogged()) {
    http_response_code(401);
    exit;
}

//if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT))
//    exit;



if (isset($_GET['file_id'])) {

    $file_id = (int)$_GET['file_id'];
    $db = new \ACES2\DB;

    $r = $db->query("SELECT movie_id,episode_id,id,server_id,transcoding,source_file,container FROM iptv_video_files 
              WHERE id = '{$_GET['file_id']}' AND is_processing = 0 ");
    if (!$row = $r->fetch_assoc())
        setError("File not found.");

    if ($row['episode_id'])
        $r2 = $db->query("SELECT title as name FROM iptv_series_season_episodes WHERE id = '{$row['episode_id']}'");
    else
        $r2 = $db->query("SELECT name FROM iptv_ondemand WHERE id = '{$row['movie_id']}'");

    $name = $r2->fetch_assoc()['name'];

    if ($row['transcoding'] == 'redirect')
        playlist($row['source_file'], $name);

    $r_server = $db->query("SELECT id,address,port,sport FROM iptv_servers WHERE id = '{$row['server_id']}'");
    if (!$server = $r_server->fetch_assoc())
        setError("System Error.");

    $format = $row['container'] ? $row['container'] : 'mp4';

    $token = md5(rand(1, 99999) . time() . time() . rand(1, 999999999));
    $r_access = $db->query("INSERT INTO iptv_access (vod_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date)
        VALUES('$file_id','{$server['id']}','{$server['address']}','$token','{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_USER_AGENT']}','$format',NOW() + interval 30 SECOND,NOW() + interval 30 MINUTE,NOW())");

    $aid = $db->insert_id;

    $PRTC = 'http:';
    $PORT = $server['port'];
    if (isset($_SERVER['HTTPS']) && $server['sport']) {
        $PRTC = 'https:';
        $PORT = $server['sport'];
    }

    $url = $row['transcoding'] == 'stream' ?
        $PRTC . "//{$server['address']}:{$PORT}/stream/svod/$aid:$token/$file_id.$format" :
        $PRTC . "//{$server['address']}:{$PORT}/stream/vod/$aid:$token/$file_id.$format";

    playlist($url, $name);


} else if (isset($_GET['stream_id'])) {

    $stream_id = (int)$_GET['stream_id'];
    $db = new \ACES2\DB;

    $r = $db->query("SELECT stream,stream_server,name FROM iptv_channels  WHERE id = '$stream_id' ");
    if (!$stream = $r->fetch_assoc())
        setError("Stream not found.");

    if ($stream['stream'] == 0) {
        $r = $db->query("SELECT url FROM iptv_channels_sources 
           WHERE chan_id = $stream_id AND enable = 1 ORDER BY priority LIMIT 1 ");
        if (!$url = $r->fetch_assoc()['url'])
            setError("Stream have not sources.");

        playlist($url, $stream['name']);

    }

    $rs = $db->query("SELECT count(*) as access, s.server_id, s.status as streaming_status,s.no_client FROM iptv_streaming s 
LEFT JOIN iptv_access a ON a.chan_id = $stream_id AND a.server_id = s.server_id
WHERE  s.no_client = 0 AND s.chan_id = $stream_id AND s.status = 1 OR s.status = 2 AND s.chan_id = $stream_id AND s.server_id = {$stream['stream_server']} 
GROUP BY s.id ORDER BY access ASC LIMIT 1");

    if (!$streaming = $rs->fetch_assoc())
        setError("Stream have not been connected yet.");

    $r_server = $db->query("SELECT id,address,port,sport,api_token
        FROM iptv_servers WHERE id = '{$streaming['server_id']}'");
    if (!$server = $r_server->fetch_assoc())
        setError("System error");

    if ($streaming['streaming_status'] == 2) {
        //WATTING FOR CLIENTS TO START.

        $curl = curl_init();
        $data = json_encode(array('api_token' => $server['api_token'], 'stream_id' => $stream_id));
        curl_setopt($curl, CURLOPT_URL, "http://{$server['address']}:{$server['port']}/stream/api2.php?&action=RESTART_STREAM&data=$data");

        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        #curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        #curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 100);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

        curl_exec($curl);
        curl_close($curl);

    }

    $token = md5(rand(1, 99999) . time() . rand(1, 999999999));

    $db->query("INSERT INTO iptv_access (chan_id,device_id,server_id,server_ip,token,ip_address,user_agent,stream_format,limit_time,end_time,add_date) 
VALUES('$stream_id',-1,'{$server['id']}','{$server['address']}','$token','{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_USER_AGENT']}','MPEGTS',NOW() + interval 60 SECOND,NOW(),NOW())");
    $aid = $db->insert_id;

    $b = base64_encode("$aid:$token");

    $PRTC = 'http:';
    $PORT = $server['port'];
    if (isset($_SERVER['HTTPS']) && (int)$server['sport'] > 0) {
        $PRTC = 'https:';
        $PORT = $server['sport'];
    }

    $channel = $PRTC . "//{$server['address']}:$PORT/stream/$aid/$token/{$stream_id}-.mpegts";

    playlist($channel, $stream['name']);

} else {
    $url = base64_decode($_GET['url']);
    playlist($url);
}