<?php

require   '/home/aces/stream/config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die("Could not connect to database."); }

$r=$DB->query("SELECT id FROM iptv_xc_videos_imp WHERE auto_update_content = 1 ");

while($id=$r->fetch_assoc()['id']){
    exec("php /home/aces/bin/update_provider_content.php $id > /dev/null & ");
}
