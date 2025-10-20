<?php


include '/home/aces/stream/config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }

//if(!$last_folder_watch || ($last_folder_watch +(60*10)) < time() ) {
    $next_run = 60 * 60 * 24;

    //$r=$DB->query("SELECT f.*,s.address as server_address,s.port as server_port,s.api_token FROM iptv_folder_watch f INNER JOIN iptv_servers s ON s.id = f.server_id WHERE (f.last_run+".$next_run.") < unix_timestamp() AND f.server_id = $SERVER_ID AND  `running` = 0  ");
    $r=$DB->query("SELECT f.*,s.address as server_address,s.port as server_port,s.api_token FROM iptv_folder_watch f
    INNER JOIN iptv_servers s ON s.id = f.server_id WHERE  f.server_id = $SERVER_ID AND f.enabled = 1 AND ( (interval_mins*60) + last_run) < unix_timestamp() ");
    while($row=$r->fetch_assoc())  {

        if($row['is_xc']) {

            //XC IS ONLY RUN ON MAIN SERVER.
            if ($SERVER_ID == 1) {

                //WE USED UNSERIALIZE BEFORE JUST IN CASE FOR OLD WATCH
                $params = is_array(unserialize($row['params'])) ? json_encode(unserialize($row['params'])) : $row['params'];


                //THE CODE BELOW WAS REMOVED AND IT STILL WAS WORKING?
                //DOES XC WATCH WORKS ANOTHER WAY?
                if ($row['pid']) {
                    $r2 = $DB->query("SELECT id,pid FROM iptv_proccess WHERE id = '{$row['pid']}' ");
                    if ($ppid = $r2->fetch_assoc()['pid'])
                        if (!posix_getpgid($ppid)) $ppid = 0;
                }

                if (!$ppid) {

                    $DB->query("INSERT INTO iptv_proccess (name,args,server_id) VALUES('xc_video_importer','$params',1)");
                    $process_id = $DB->insert_id;

                    exec("nohup php /home/aces/bin/aces_xc_video_importer.php $process_id > /dev/null & ");

                    $DB->query("UPDATE iptv_folder_watch SET last_run = unix_timestamp(), pid = '$process_id' 
                         WHERE id = {$row['id']} ");

                }


            }

        } else if($row['is_plex'] && $SERVER_ID == 1 ) {

                $params = $row['params'];

                $r2 = $DB->query("SELECT id,pid FROM iptv_proccess WHERE id = '{$row['pid']}' ");

                if (!$r2->fetch_assoc()) {
                    
                    $DB->query("INSERT INTO iptv_proccess (name,args,server_id) VALUES('plex_importer','$params',1)");
                    $process_id = $DB->insert_id;


                    exec("nohup php /home/aces/bin/plex_import.php $process_id > /dev/null & ");

                    $DB->query("UPDATE iptv_folder_watch SET last_run = unix_timestamp(), pid = '$process_id' 
                         WHERE id = {$row['id']} ");
                }

        } else {

            $ppid = 0;
            $r2 = $DB->query("SELECT id,pid FROM iptv_proccess WHERE id = '{$row['pid']}' ");
            if ($ppid = $r2->fetch_assoc()['pid']) {

//                $p['api_token'] = $row['api_token'];
//                $data = json_encode(array('pid'=> $ppid,'api_token' => $row['api_token']));
//
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, "http://{$row['server_address']}:{$row['server_port']}/stream/api2.php?action=IS_PROCESS_RUNNING&data=$data");
//                curl_setopt($ch, CURLOPT_HEADER, 0);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
//                $resp = json_decode(curl_exec($ch),1);
//
//                curl_close($ch);
//
//                if($resp['data']['is_running'])
//                    $ppid = null;

                if (!posix_getpgid($ppid))
                    $ppid = 0;

            }

            if (!$ppid) {

                $DB->query("INSERT INTO iptv_proccess (name,args,server_id) 
                                    VALUES('video_directory_import','{$row['params']}','$SERVER_ID') ");
                $process_id = $DB->insert_id;


                if ($row['type'] == 'series')
                    exec(" php /home/aces/bin/aces_series_dir_import.php $process_id  > /dev/null & " );
                else
                    exec(" php /home/aces/bin/aces_video_dir_import.php $process_id  > /dev/null & " );

                $DB->query("UPDATE iptv_folder_watch SET last_run = unix_timestamp(), pid = '$process_id' WHERE id = {$row['id']} ");
                //echo "UPDATE iptv_folder_watch SET last_run = unix_timestamp(), pid = '$process_id' WHERE id = {$row['id']}";

            }

        }
    }

    //$r->free();
    unset($r,$row,$ch,$p,$j,$o);
    $last_folder_watch = time();
//}
