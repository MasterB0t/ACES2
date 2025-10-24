<?php

$f = @fopen("/home/aces/logs/aces-daemon.log", "r+");
if ($f !== false) {
    ftruncate($f, 0);
    fclose($f);
}

ini_set("error_log", "/home/aces/logs/aces-daemon.log");
//error_reporting(-1);
error_reporting( E_ERROR | E_WARNING );

#exec("php /home/aces/bin/get_lic.php");

require "/home/aces/stream/config.php";
define('DB_NAME', $DATABASE);
define('DB_USER', $DBUSER );
define('DB_PASS', $DBPASS );
define('DB_HOST', $DBHOST );

$RELOAD_SETTINGS=1;
$DB=null;

$SCRIPT = is_file("/home/aces/old_stream") ? 'aces_stream.php' : "iptv_stream.php" ;

$IS_PANEL =0;
//if(is_file('/home/aces/panel/framework/aces.php')) $IS_PANEL = 1;
if($SERVER_ID == 1) $IS_PANEL = 1;

$PID_FILE = "/home/aces/run/aces.pid";
$PID=0;
if(is_file($PID_FILE)) {

    //THERE ARE A PID FILE LET TRY TO READ PID FROM THE FILE.
    if(! ($PID = file_get_contents($PID_FILE)) ) { error_log("IT SEEMS THERE IS A PROCESS ALREADY RUNNING FOR ACES BUT COULDN'T GET PID FROM IT."); die; }

    //WE HAVE PID # LET CHECK IF STILL RUNNING
    if(posix_getpgid($PID) && getmypid() != $PID ) { error_log('THERE IS ALREADY PROCESS RUNNING.'); die; }

}

if( getmypid() != $PID )
    if(! (file_put_contents($PID_FILE,getmypid()) ) ) { error_log("COULD NOT WRITE PID TO FILE"); DIE; }

function ConnectToDB() {
    global $DB,$DBHOST,$DBUSER,$DBPASS,$DATABASE;
    while(true) {
        try  {
            error_log("CONNECTING TO DB...");
            $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
            if($DB->connect_errno > 0)  {
                error_log('Could not connect to mysql trying again in five seconds.');
                sleep(5);
            }  else return;
        } catch (\mysqli_sql_exception $exp) {
            error_log('Could not connect to mysql trying again in five seconds.');
            $ignore = 1;
            sleep(5);

        }
    }

}

ConnectToDB();

error_log("\n\n\nService Start.");


function get_disk_info() {
    $cmd = "blkid";
    $accept_fs = array('ntfs','ext4','ext3','btrfs','vfat','exfat');

    $descriptorspec = array(
        0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
        2 => array("pipe", "w")    // stderr is a pipe that the child will write to
    );
    flush();
    $process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), array());

    $mounts=[];

    $DISKS = [];
    if (is_resource($process)) {
        while ($l = fgets($pipes[1])) {
            list($device,$info ) = explode(':',$l);
            $info_arr = explode(' ',trim($info));
            $device_info = [];

            for($i=0;$i<count($info_arr);$i++) {
                $e = explode("=",$info_arr[$i]);
                $device_info[ $e[0] ] = trim(str_replace('"','',$e[1]));
            }


            $mount_point = explode(" ", trim(exec("findmnt -S $device")) )[0];

            if($mount_point && in_array($device_info['TYPE'], $accept_fs)) {
                $DISKS[] = array(
                    'filesystem' => $device_info['TYPE'],
                    'device' => $device,
                    'size' => round( disk_total_space($mount_point) / 1000000000 , 2) ,
                    'used' => round( (disk_total_space($mount_point) - disk_free_space($mount_point))/ 1000000000 , 2)  ,
                    'available' => round( disk_free_space($mount_point) / 1000000000 , 2 ) ,
                    'mount_point'  => $mount_point,
                );
            }

            flush();

        }
    }

    return $DISKS;
}


//LOADING FIREWALL RULES.
if ($IS_PANEL) {
    if (!is_file('/home/aces/NO_FIREWALL') && !is_file('/home/aces/no_firewall')) {

        include_once '/home/aces/panel/functions/logs.php';
        include_once '/home/aces/panel/ACES2/DB.php';
        include_once '/home/aces/panel/ACES2/Firewall.php';
        include_once '/home/aces/panel/ACES2/IPTV/Server.php';

        exec("iptables -F ");
        if ($r_fw = $DB->query("SELECT id FROM firewall ")) {
            while ($row = $r_fw->fetch_assoc()) {

                $firewall = new ACES2\Firewall($row['id']);
                $firewall->appendRule($SERVER_ID);

            }
            $r_fw->free();
            unset($row);
        }

    }
}

if($IS_PANEL) $DB->query("DELETE FROM iptv_streaming ");
else $DB->query("DELETE FROM iptv_streaming WHERE server_id = '$SERVER_ID' ");

//$DB->query("DELETE FROM iptv_access WHERE server_id = '$SERVER_ID' AND device_id != 0  "); //DO NOT DELETE ACCESS FROM LBs


$procss = glob('/home/aces/run/aces_stream-*.pid', GLOB_BRACE);
foreach($procss as $proc) {
    $p= file_get_contents($proc);
    exec("kill -9 $p");
    unlink($proc);
}


$DB->query("UPDATE iptv_servers SET status = 1, last_update = UNIX_TIMESTAMP() WHERE id = $SERVER_ID ");

function StartStreams()  {

    global $DB,$SERVER_ID,$IS_PANEL, $SCRIPT;

    #exec("kill -9 $(ps -eAf  | grep /stream_tmp/ | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }') ");
    exec("kill -9  $(ps aux | grep '$SCRIPT' | awk '{print $2}') 2>/dev/null ");

    //STOPING RECORDINGS.
    exec("pgrep -f 'catchup.php'",$pids);
    foreach($pids as $pid) exec("kill -9 $pid "); exec("kill -9 $pid ");
    exec("rm -f /home/aces/run/catchup-*.pid");


    exec("pgrep -f 'record.php'",$pids);
    foreach($pids as $pid) exec("kill -9 $pid "); exec("kill -9 $pid ");
    exec("rm -f /home/aces/run/record-*.pid");

    exec("kill -9 $(ps -eAf  | grep /recordings/ | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }')");

    exec(" rm -rf /home/aces/stream_tmp/*");


    try {

        $r=$DB->query("SELECT * FROM settings WHERE name = 'iptv.streamsstartup' AND value = '1' ");
        if($r->fetch_assoc()) {
            //LET START STREAMS

            //$res=$DB->query("SELECT id,load_balances,stream_server,catchup,ondemand,type FROM iptv_channels WHERE stream = 1 AND enable = 1  ");

            if($IS_PANEL)
                $res=$DB->query("SELECT id,stream_server,catchup,ondemand,type FROM iptv_channels 
                    WHERE stream = 1 AND enable = 1  
                        AND  exists ( SELECT 1 FROM iptv_channels_sources WHERE chan_id = iptv_channels.id AND enable = 1 LIMIT 1 )
                ");
            else
                $res=$DB->query("SELECT id,stream_server,catchup,ondemand,type FROM iptv_channels 
                      WHERE stream = 1 AND enable = 1 AND stream_server = '$SERVER_ID' 
                        AND exists ( SELECT 1 FROM iptv_channels_sources WHERE chan_id = iptv_channels.id AND enable = 1 LIMIT 1 )
                ");
            while($stream=$res->fetch_assoc()) {

                $stream_id = $stream['id'];

                //ONLY IF IT HAVE SOURCES.
                //$r=$DB->query("SELECT id FROM iptv_channels_sources WHERE chan_id = '$stream_id' AND enable = 1  ");
                //if($r->fetch_assoc() || $stream['type'] == 1) {
                //if($r->fetch_assoc() || $stream['type'] == 1) {

                //INSERTING STREAMING ROW.
                if($stream['ondemand'])
                    $DB->query("INSERT INTO iptv_streaming (chan_id,action,status,server_id,last_access,start_time) VALUES ('$stream_id',0,2,'{$stream['stream_server']}',NOW(),NOW()) ");
                else {
                    $DB->query("INSERT INTO iptv_streaming (chan_id,action,server_id,last_access,start_time) VALUES ('$stream_id',1,'{$stream['stream_server']}',NOW(),NOW()) ");
                    if($IS_PANEL && $stream['catchup'] == 1) exec("su aces -c 'nohup php /home/aces/bin/catchup.php -{$stream['id']}- > /dev/null &' " );
                }

                //}

            }

            //DOING LBS
            $r_lb = $DB->query("SELECT * FROM iptv_channels_in_lb WHERE destination_server = $SERVER_ID ");
            while($stream_lb = $r_lb->fetch_assoc()) {
                $DB->query("INSERT INTO iptv_streaming (chan_id,action,server_id,source_server_id,last_access,start_time) 
                    VALUES ('{$stream_lb['channel_id']}',1,'$SERVER_ID','{$stream_lb['source_server']}',NOW(),'".time()."') ");
            }

            //STATS
            $c = exec("pgrep -cf '$SCRIPT'");
            $n = exec("pgrep -cf 'aces_nstream.php'");
            $c = $c + $n;

            $DB->query("UPDATE iptv_servers SET streams = '$c' WHERE id = '$SERVER_ID' ");

        }

        //RESTARTING RECORDINGS.
        $r_recordings=$DB->query("SELECT id FROM iptv_recording WHERE status in (1,2) AND server_id = '$SERVER_ID' ");
        while($row_recording=mysqli_fetch_array($r_recordings)) {
            @exec("su aces -c 'nohup php /home/aces/bin/record.php {$row_recording['id']}- > /dev/null &' " );
        }


    } catch(\mysqli_sql_exception $exp ) {
        sleep(5);
        //RETRY.
        ConnectToDB();
    }


    return;

}


StartStreams();


$last_time_lic = null;
$net = exec("route | grep 'default' | awk ' {print $8}'",$net);

$SERVER_NAME = "SERVER_ID_#{$SERVER_ID}";
$r_srv=$DB->query("SELECT max_bandwidth_mbps,name FROM iptv_servers WHERE id = $SERVER_ID ");
if(!$row_srv=mysqli_fetch_array($r_srv)) $MAX_BANDWIDTH = 1000;  // 1GbPS by default.
else if(empty($row_srv['max_bandwidth_mbps'])) $MAX_BANDWIDTH = 1000; // 1GbPS by default if is not set.
else $MAX_BANDWIDTH = $row_srv['max_bandwidth_mbps'];

if(!empty($row_srv['name'])) $SERVER_NAME = $row_srv['name'];

$r_main_server = $DB->query("SELECT address FROM iptv_servers WHERE id = 1 ");
$MAIN_SERVER = $r_main_server->fetch_assoc()['address'];

//$r_srv->free();


//GETTING MOUNT POINTS
$output = array();
exec("df -h | awk '{print $6 }' ", $output);
foreach($output as $i => $v ) {

    if( $v == '/' || $v == '/home' || $v == '/home/aces' || $v == '/home/aces/vods' || $v == '/home/aces/stream_tmp' ) $mount_points[] = $v;

}

while(true) {

    try {


        clearstatcache();

//        $c_lost_msg = null;
//        while (!$DB->ping()) {
//            if (!$c_lost_msg) {
//                error_log("Connecting lost to database.");
//                $c_lost_msg = 1;
//            }
//            unset($DB);
//            $DB = new mysqli($DBHOST, $DBUSER, $DBPASS, $DATABASE);
//            if ($DB->connect_errno > 0) {
//                sleep(3);
//            } else {
//
//                //RESTART STREAMS...
//                if ($IS_PANEL) {
//                    $DB->query("DELETE FROM iptv_streaming ");
//                    StartStreams();
//                }
//
//                break;
//            }
//        }

        if ($RELOAD_SETTINGS && !$IS_PANEL) {


            $RELOAD_SETTINGS = 0;
            $DB->query("UPDATE iptv_servers SET reload_settings = 0 WHERE id = $SERVER_ID ");

        }


        $time = time();

        if (!$last_check_streams || ($last_check_streams + 1) < time()) {

            if ($r_streams = $DB->query("SELECT s.id,s.chan_id,s.action,c.catchup FROM iptv_streaming s 
                INNER JOIN iptv_channels c ON c.id = s.chan_id WHERE s.server_id = '$SERVER_ID' AND s.action != 0 ")) {
                while ($row_streams = mysqli_fetch_array($r_streams)) {

                    //REMOVING TMPS FILES
                    exec(" rm -rf /home/aces/stream_tmp/{$row_streams['chan_id']}-*");
                    exec(" rm -rf /home/aces/stream_tmp/{$row_streams['chan_id']}_*");

                    //KILL CATCHUP IF EXIST.
                    //exec("kill -9  $(ps -eAf  | grep /home/aces/bin/catchup.php | grep '\-{$row_streams['chan_id']}\-'  | grep -v 'ps -eAf'  | awk '{print $2 }' )");

                    if (is_file("/home/aces/run/aces_stream-{$row_streams['chan_id']}.pid")) {
                        if ($stream_pid = file_get_contents("/home/aces/run/aces_stream-{$row_streams['chan_id']}.pid")) {
                            if (posix_getpgid($stream_pid)) exec("kill -9 $stream_pid");
                            exec("kill -9  $(ps -eAf  | grep /{$row_streams['chan_id']}-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");
                            unlink("/home/aces/run/aces_stream-{$row_streams['chan_id']}.pid");
                        }

                    }

                    //STOPPING BUILDING
                    if (is_file("/home/aces/run/aces_build_channel-{$row_streams['chan_id']}.pid")) {
                        if ($stream_pid = file_get_contents("/home/aces/run/aces_build_channel-{$row_streams['chan_id']}.pid")) {
                            if (posix_getpgid($stream_pid)) exec("kill -9 $stream_pid");
                            unlink("/home/aces/run/aces_build_channel-{$row_streams['chan_id']}.pid");
                        }

                    }

                    $DB->query("DELETE FROM iptv_access WHERE chan_id = '{$row_streams['chan_id']}' AND device_id != 0 ");

                    //START STREAM
                    if ($row_streams['action'] == 1) {

                        $run = "php /home/aces/bin/$SCRIPT {$row_streams['chan_id']}- ";
                        @exec("su aces -c 'nohup $run > /dev/null &' ");
                        //if($IS_PANEL && $row_streams['catchup'] == 1) exec("su aces -c 'nohup php /home/aces/bin/catchup.php -{$row_streams['chan_id']}- > /dev/null &' " );

                        $DB->query("UPDATE iptv_streaming set action = 0 WHERE id = '{$row_streams['id']}' ");

                    } else if ($row_streams['action'] == 2) {

                        $DB->query("DELETE FROM iptv_streaming WHERE id = '{$row_streams['id']}' ");
                        //STOPING CATCHUP IF EXIST
                        //exec("kill -9  $(ps -eAf  | grep /home/aces/bin/catchup.php | grep '\-{$row_streams['chan_id']}\-'  | grep -v 'ps -eAf'  | awk '{print $2 }' )");

                    }

                }

                $r_streams->free();

                $last_check_streams = time();
            }

        }


        //STARTING RECORINGS.
        if (!$last_time_record || ($last_time_record + 10) < time()) {
            if ($r_recordings = $DB->query("SELECT id FROM iptv_recording WHERE status = 0 AND server_id = '$SERVER_ID' ")) {
                while ($row_recordings = mysqli_fetch_array($r_recordings)) {

                    //exec("kill -9  $(ps -eAf  | grep 'record.php {$row_recordings['id']}-' | grep -v 'ps -eAf' | awk '{print $2 }' )");

                    if (is_file("/home/aces/run/record-{$row_recordings['id']}.pid")) {

                        if ($pid = file_get_contents("/home/aces/run/record-{$row_recordings['id']}.pid")) exec("kill -9 $pid ");
                        unlink("/home/aces/run/recording-{$row_recordings['id']}.pid");

                    }
                    @exec("su aces -c 'nohup php /home/aces/bin/record.php {$row_recordings['id']}- > /dev/null &' ");
                }
                $r_recordings->free();
            }
            $last_time_record = time();
        }

        //DELETE OLD RECORDS
        if (!$last_time_del_record || ($last_time_del_record + (30)) < time()) {

            if ($r_delrecord = $DB->query("SELECT id FROM iptv_recording WHERE status = 4 AND server_id = '$SERVER_ID' OR expire_date IS NOT NULL AND server_id = '$SERVER_ID' AND expire_date < NOW()  ")) {

                while ($row_del_record = mysqli_fetch_array($r_delrecord)) {

                    exec("kill -9 $(pgrep -f 'php /home/aces/bin/record.php {$row_del_record['id']}\-') ");
                    exec("kill -9 $(ps -eAf  | grep /home/aces/recordings/ | grep ffmpeg | grep 'p{$row_del_record['id']}\-'  |  grep -v 'ps -eAf'  | awk '{print $2 }') ");

                    exec("rm -f /home/aces/recordings/r{$row_del_record['id']}.ts");
                    exec("rm -f /home/aces/recordings/p{$row_del_record['id']}-*.ts");
                    exec("rm -f /home/aces/recordings/r{$row_del_record['id']}.mkv");
                    exec("rm -f /home/aces/recordings/r{$row_del_record['id']}.mp4");

                    $DB->query("DELETE FROM iptv_recording WHERE id = '{$row_del_record['id']}' ");
                }

                $r_delrecord->free();
            }

            $last_time_del_record = time();

        }


        //GET DISK SPACE EVERY 10 MINUTES
        if (!$last_time_disk_space || ($last_time_disk_space + (60 * 10)) < time()) {

            $DISKS = get_disk_info();

            $last_time_disk_space = time();
        }

        //DOING SERVER STATS HERE.
        $rx[] = @file_get_contents("/sys/class/net/$net/statistics/rx_bytes");
        $tx[] = @file_get_contents("/sys/class/net/$net/statistics/tx_bytes");
        sleep(1);
        clearstatcache();
        $rx[] = @file_get_contents("/sys/class/net/$net/statistics/rx_bytes");
        $tx[] = @file_get_contents("/sys/class/net/$net/statistics/tx_bytes");

        $tbps = (($tx[1] - $tx[0]) / 0.125);
        $rbps = (($rx[1] - $rx[0]) / 0.125);

        $round_rx = round($rbps / 1024000, 2);
        $round_tx = round($tbps / 1024000, 2);

        unset($rx, $tx);

        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);

        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);

        $data = array();
        $data['memory_usage'] = round($mem[2] / 1024, 2);
        $data['memory_total'] = round($mem[1] / 1024, 2);
        $data['memory_usage_percent'] = $mem[2] / $mem[1] * 100;
        //$data['cpu_load'] = round((sys_getloadavg()[0]*100/count(sys_getloadavg())/10)  );
        $data['cpu_load'] = exec('top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'  ');
        $data['tx_bandwidth'] = $round_tx;
        $data['rx_bandwidth'] = $round_rx;
        $data['bandwidth_usage_percent'] = (($round_rx + $round_tx) / $MAX_BANDWIDTH) * 100;
        $data['disk_usage'] = @$disk_usage . " GB";
        $data['disk_total'] = @$disk_total . " GB";
        $data['disk_usage_percent'] = @$disk_percent;
        $data['uptime'] = explode(' ', trim(file_get_contents('/proc/uptime')))[0];

        //PING CODE
        $ping = 0;
        $pingResults = null;
        $output = null;
        $pingResult = null;
        $matches = null;
        $cmd = sprintf('ping -w %d -%s %d %s', 3, 'c', 4, $MAIN_SERVER);
        exec($cmd, $output, $result);
        if ($result) {
            $pingResults = preg_grep('/time(=|<)(.*)ms/', $output); // discard output lines we don't need
            $pingResult = array_shift($pingResults); // we wanted just one ping anyway
            preg_match('/time(=|<)(.*)ms/', $pingResult, $matches); // we get what we want here
            $ping = floatval(trim($matches[2])); // here's our time
        }

        if ($r00 = $DB->query("SELECT count(id) as total FROM iptv_streaming WHERE server_id = '$SERVER_ID' AND status = 1 "))
            $data['online_channels'] = (int)$r00->fetch_assoc()['total'];

        if ($r00 = $DB->query("SELECT count(id) as total FROM iptv_access WHERE server_id = '$SERVER_ID' AND device_id != 0 AND limit_time > NOW() GROUP BY device_id "))
            $data['online_users'] = (int)$r00->num_rows;

        if ($r00 = $DB->query("SELECT count(id) as total FROM iptv_access WHERE server_id = '$SERVER_ID' AND device_id != 0 AND limit_time > NOW() "))
            $data['connections'] = (int)$r00->fetch_assoc()['total'];


        $r = $DB->query(" SELECT stats FROM iptv_servers WHERE id = '$SERVER_ID'");
        $stats = unserialize(mysqli_fetch_assoc($r)['stats']);

        $limit = 20;

        if (is_array($cpu_history) && count($cpu_history) > $limit)
            while (count($cpu_history) > $limit) array_shift($cpu_history);

        $cpu_history[] = $data['cpu_load'];


        if (is_array($ram_history) && count($ram_history) > $limit)
            while (count($ram_history) > $limit) array_shift($ram_history);

        $ram_history[] = $data['memory_usage_percent'];

        if (is_array($bd_history) && count($bd_history) > $limit)
            while (count($bd_history) > $limit) array_shift($bd_history);

        $bd_history[] = $data['bandwidth_usage_percent'];


        $a = explode('up', trim(exec('uptime')));
        $a = explode(',', $a[1]);
        if (strpos($a[0], 'days') !== false)
            $data['uptime_string'] = " {$a[0]} {$a[1]} ";

        else $data['uptime_string'] = $a[0];

        $s_cpu = json_encode($cpu_history);
        $s_ram = json_encode($ram_history);
        $s_bd = json_encode($bd_history);
        $s_disks = json_encode($DISKS);

        $DB->query("UPDATE iptv_servers SET latency = '$ping',  
                        stats ='" . serialize($data) . "', cpu_history = '$s_cpu', ram_history = '$s_ram', 
                        bandwidth_history = '$s_bd',  hard_disks = '$s_disks',  last_update = UNIX_TIMESTAMP(), status = 1 
                        WHERE id = $SERVER_ID ");


        if ($IS_PANEL) {

            //DELETING EXPIRED ACCESS HERE INSTEAD ON loadstream.php
            //if ($r_exp = $DB->query("SELECT device_id FROM iptv_access WHERE limit_time < NOW() AND chan_id != 0 OR end_time < NOW() AND vod_id != 0 ")) {
            if ($r_exp = $DB->query("SELECT device_id FROM iptv_access WHERE limit_time < NOW() AND chan_id != 0 OR end_time < NOW() AND vod_id != 0 ")) {
                while ($row_exp = mysqli_fetch_array($r_exp)) {
                    $DB->query("UPDATE iptv_devices SET last_activity = NOW() WHERE id = '{$row_exp['device_id']}' ");
                }
                $r_exp->free();
                $row_exp = null;
            }


            if (!$_last_time_server_check || ($_last_time_server_check + 25) < time()) {

                if ($r_srvs = $DB->query("SELECT * FROM iptv_servers WHERE id != $SERVER_ID AND last_update < (UNIX_TIMESTAMP()-25) ")) {
                    while ($row_servers = mysqli_fetch_array($r_srvs)) { ///echo "CHECKING SERVER {$row_servers['name']}\n";

                        $a = array();
                        $a['api_token'] = $row_servers['api_token'];
                        $a['action'] = 'info';
                        $a = urlencode(json_encode($a));

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                        curl_setopt($ch, CURLOPT_URL, "http://{$row_servers['address']}:{$row_servers['port']}/stream/api.php?data=$a");
                        $return = json_decode(curl_exec($ch), 1);

                        //0 SERVER OFF
                        //1 SERVER ONLINE
                        //2 NO SERVICE
                        //3 NO DB CONNECTION
                        //4 NO LICENSE
                        //5 SYSTEM ERROR
                        //6 SOFTWARE ERROR

                        $server_status = 5;
                        if (($c_err = curl_errno($ch)) || !$return || $return['errors'] > 0 || $return['aces_services'] != 1) {
                            if ($c_err == 28) {
                                //SERVER TIMEOUT
                                $server_status = 0;
                            } else if ($c_err == 7) {
                                #SERVER OFFLINE || COULD NOT CONNECT
                                $server_status = 0;
                            } else if ($c_err) {
                                //OTHER CURL ERRORS.
                                //echo "CURL ERROR $c_err\n";
                                $server_status = 5;
                            } else if (!$return) {
                                //NO RESPONSE FROM SERVER.
                                $server_status = 5;
                                //echo "NO RESPONSE FROM SERVER\n";
                            } else if ($return['errors'] > 0) {
                                //SERVER ERROR.
                                $server_status = 6;
                            } else if ($return['aces_services'] != 1) {
                                $server_status = 2;
                            }

                        }

                        // Closing
                        curl_close($ch);
                        $DB->query("UPDATE iptv_servers SET status = $server_status, last_update = UNIX_TIMESTAMP() WHERE id = {$row_servers['id']} ");

                    }
                    $r_srvs->free();
                    unset($ch, $server_status, $return, $c_err, $r_srvs, $a);
                }


                $_last_time_server_check = time();
            }


            //$DB->query("DELETE FROM iptv_access WHERE limit_time < NOW() ");
            //$DB->query("DELETE FROM iptv_access WHERE limit_time < NOW() AND chan_id != 0 AND device_id != 0 ");
            //$DB->query("DELETE FROM iptv_access WHERE end_time < NOW() AND vod_id != 0 AND device_id != 0  ");

            $DB->query("DELETE FROM iptv_access WHERE limit_time < NOW() AND end_time < NOW()  ");
            //$DB->query("DELETE FROM iptv_access WHERE limit_time < NOW()  ");
            $DB->query("DELETE FROM armor WHERE NOW() > exp_date ");

        }


        sleep(2);

        if (!$IS_PANEL) {
            if ($r_srv_set = $DB->query("SELECT reload_settings FROM iptv_servers WHERE id = $SERVER_ID ")) {
                if (mysqli_fetch_array($r_srv_set)['reload_settings'] == 1) {
                    $RELOAD_SETTINGS = 1;
                }
                $r_srv_set->free();
            }

            if (!$check_armor || ($check_armor + 60) < time()) {
                $DB->query("DELETE FROM armor__tokens WHERE expire_time < UNIX_TIMESTAMP()");
                $check_armor = time();
            }
        }


    } catch (\mysqli_sql_exception $e) {
        //QUERY FAIL.
        error_log("DB Query Fail #" . $e->getCode() . " " . $e->getMessage() );
        ConnectToDB();
        if($e->getCode() == 2006 ) {
            //MYSQL HAVE GONE AWAY. LETS RESTART STREAMS.
            StartStreams();
        }
    }



}
