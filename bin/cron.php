<?php

require "/home/aces/panel/ACES2/DB.php";
require "/home/aces/stream/config.php";

$db = new \ACES2\DB;


switch(strtolower($argv[1])){

    case 'h':
        if( $SERVER_ID == 1 ) {
            exec("php /home/aces/bin/aces_auto_update_stream_name.php > /dev/null & ");

            $r=$db->query("SELECT id FROM iptv_dynamic_events WHERE active = 1 ");
            while($event_id=$r->fetch_assoc()['id'])
                exec("php /home/aces/bin/iptv_automation_events.php $event_id> /dev/null & ");
        }

        break;

    case 'd':
        if( $SERVER_ID == 1 ) {
            exec("php /home/aces/bin/abackup.php > /dev/null & ");
            #exec("php /home/aces/bin/aces_build_guide.php > /dev/null & ");
            exec("php /home/aces/bin/iptv_build_guide.php > /dev/null & ");
            exec("php /home/aces/bin/aProviderContent.php > /dev/null & ");
        }

        exec("php /home/aces/bin/get_lic.php > /dev/null & ");

        break;


}