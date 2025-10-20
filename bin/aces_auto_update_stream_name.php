<?php

require "/home/aces/stream/config.php";
set_time_limit(0);
ini_set('memory_limit', '4096M');

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die;

$urls = array();
$r=$DB->query("SELECT id FROM iptv_channels WHERE auto_update = 1 AND enable = 1");

while($chan=$r->fetch_assoc()) {
    $new_name = '';
    $r2=$DB->query("SELECT url FROM iptv_channels_sources WHERE chan_id = '{$chan['id']}' ORDER BY priority ASC LIMIT 1");
    if($source=$r2->fetch_assoc()) {

        $purl = parse_url($source['url']);
        $path = explode("/",$purl['path']);

        if(count($path) < 5 ) {
            $username = $path[1];
            $password = $path[2];
            $stream_id = (int)$path[3];
        } else {
            $username = $path[2];
            $password = $path[3];
            $stream_id = (int)$path[4];
        }


        $u = $purl['scheme'] . "://" . $purl['host'] . ":" . $purl['port'] . "/player_api.php?username={$username}&password={$password}&action=get_live_streams";
        $key = array_search($u,$urls);

        if( $key === false ) {
            $urls[] = $u;
            $key = array_search($u,$urls);
            $json[$key] = json_decode(@file_get_contents($purl['scheme'] . "://" . $purl['host'] . ":" . $purl['port'] . "/player_api.php?username={$username}&password={$password}&action=get_live_streams"), 1);
        }

        if(is_array($json[$key])) {

            $inx = array_search($stream_id, array_column($json[$key], 'stream_id'));
            $new_name = $DB->escape_string($json[$key][$inx]['name']);

            $DB->query("UPDATE iptv_channels SET name = '$new_name' WHERE id = '{$chan['id']}'");

        }

    }



}