<?php

require '/home/aces/stream/config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }

$MODE = $argv[1];

$sql_1 = $SERVER_ID == 1 ? "transcoding = 'redirect' OR server_id = 1 " : "server_id = $SERVER_ID ";

switch($MODE) {
    case '--all':
    case '--update':
        $sql = $sql_1;
        break;
    default :
        exit("Exit...");
}


$r=$DB->query("SELECT id FROM iptv_video_files WHERE $sql ");
while($row=$r->fetch_assoc()) {
    echo "UPDATING #{$row['id']}\n";
    exec("php /home/aces/bin/aces_get_video_codecs.php {$row['id']}");
}