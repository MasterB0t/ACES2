<?php

use ACES2\IPTV\StreamStats;

include "/home/aces/stream/config.php";
include "/home/aces/panel/ACES2/DB.php";
include "/home/aces/panel/ACES2/IPTV/StreamStats.php";

$DB = new ACES2\DB();
$r=$DB->query("SELECT chan_id FROM iptv_streaming WHERE server_id = '$SERVER_ID'");
while($row=$r->fetch_assoc()) {
    $StreamStats = new StreamStats((int)$row['chan_id'], $SERVER_ID);
    $StreamStats->setShutoff();
}

$DB->query("DELETE FROM iptv_streaming WHERE server_id = '$SERVER_ID'");