<?php
// @ioncube.dynamickey https://acescript.ddns.net/enckey.php?version=%VERSION%&type=nodo&phass=0a3e31er3rga0odj2 -> "%ENC_KEY%"


$data = json_decode($_GET['data'],1);
$resp = array();
$data['errors'] = 0;

include 'config.php';
if($data['api_token'] != $API_TOKEN ) die;

if(defined('ACES_VER')) { error_log('WRONG VARIABLES ON CONFIG FILE'); die; }


define('ACES_VER', '%VERSION%'); 
$data['aces_version'] = ACES_VER;


//IGNORE CHECKSUM AND VERSION CHECK IF ITS PERFORMING UPDATE OR CHECKING SERVER INFO.
//if( $data['action'] != 'update_aces' && $data['action'] != 'info' && $data['action'] != 'stream_client' ) {
//
//    if(!defined('NOT_STRCT') && version_compare(ACES_VER, $data['version']) != 0 ) {
//        error_log('MISMATCH VERSION.');
//        $data['errors'] = 1 ;
//        $data['error_msg'] = "Mismatch version. Please update to the last version.";
//        echo json_encode($data);
//        die;
//    }
//
//
//    if(!is_file('/home/aces/stream/STRCT.php') && !defined('NOT_STRCT')) { error_log('SOFTWARE FILES MISSING.');  die; }
//    else if(!defined('NOT_STRCT')) {
//
//        include 'STRCT.php';
//
//        if(md5_file('/home/aces/stream/api.php') != F_API ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/stream/index.php') != F_INDEX ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/stream/vod.php') != F_VOD ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/bin/aces.php') != F_ACES ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/bin/aces_stream.php') != F_STREAM ) {  error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/bin/aces_process_video.php') != F_PROCESS_VIDEOS ) { error_log('SOFTWARE FILES MISSING.');  die; }
//        if(md5_file('/home/aces/bin/aces_move_video.php') != F_MOVE_VIDEOS ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//        if(md5_file('/home/aces/bin/aces_build_guide.php') != F_BUILD_GUIDE ) { error_log('SOFTWARE FILES MISSING.');  die;  }
//
//    }
//
//}

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ error_log('COULD NOT CONNECT TO DATABASE');  die(); }

if(!empty($data['stream_id'])) $STREAMID = $data['stream_id']; //AKA CHAN_ID

//GETTING ACES SERVICES.
$ACES_PID_FILE = "/home/aces/run/aces.pid";

if(is_file($ACES_PID_FILE)) { 
    
    //THERE ARE A PID FILE LET TRY TO READ PID FROM THE FILE.
    if(! ($PID = file_get_contents($ACES_PID_FILE)) ) { $data['aces_services'] = 0; }  //COULDN'T READ FROM PID FILE.
     
    //WE HAVE PID # LET CHECK IF STILL RUNNING
    if(posix_getpgid($PID)) { $data['aces_services'] = 1; } 
    else $data['aces_services'] = 0;
        
} else $data['aces_services'] = 0;
$data['aces_services']=1;

if($data['action'] == 'info' ) {  }


if($data['action'] == 'stop_stream') { 
    //if($PID = (int)  exec("pgrep -f 'ffmpeg.php $STREAMID-'") ) exec("kill -9 $PID ");
    if($PID = (int)  exec("pgrep -f 'aces_stream.php $STREAMID-'",$pids) ) {
            foreach($pids as $pid) exec("kill -9 $pid ");
    }

    if($PID = (int)  exec("pgrep -f 'aces_nstream.php $STREAMID-'",$pids) ) {
            foreach($pids as $pid) exec("kill -9 $pid ");
    }
   
    //while($pid = exec(" ps -eAf  | grep /home/aces/stream_tmp/$STREAMID-.m3u8 | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
    //	if($pid) exec("kill -9 $pid ");
    exec("kill -9  $(ps -eAf  | grep /$STREAMID-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");


    if(is_file("/home/aces/run/aces_stream-$STREAMID.pid"))
        unlink("/home/aces/run/aces_stream-$STREAMID.pid");

    if(is_file("/home/aces/run/aces_nstream-$STREAMID.pid"))
        unlink("/home/aces/run/aces_nstream-$STREAMID.pid");
    
    
    //exec(" rm -f $FOLDER/$STREAMID-*");
    exec(" rm -f $FOLDER/{$STREAMID}_*");
				
				
} else if($data['action'] == 'restart_stream') {

    //DO NOT WORK ON UBUNTU
    // 	if( $pid = (int) exec("pgrep -f 'ffmpeg.php $STREAMID-'") )  {

            // 		exec("kill -9 $pid ");
            // 		while($pid = exec(" ps -eAf  | grep /home/aces/stream_tmp/$STREAMID-.m3u8 | grep ffmpeg | grep -v ps | awk '{print $2 }' " )) exec("kill -9 $pid ");

            // 		if(is_file("/home/aces/run/ffmpeg-$STREAMID.pid"))
            // 			unlink("/home/aces/run/ffmpeg-$STREAMID.pid");

            // 	}
    
                    if($PID = (int)  exec("pgrep -f 'aces_build_channel.php $STREAMID-'",$pids) ) {
                            foreach($pids as $pid) exec("kill -9 $pid ");
                    }
    
                    $b = 'aces_stream.php';
    
    
                    if( $pid = (int) exec("pgrep -f '$b $STREAMID-'",$pids) )  {

                            foreach($pids as $pid) exec("kill -9 $pid "); exec("kill -9 $pid ");
                            exec("kill -9  $(ps -eAf  | grep /$STREAMID-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");


                            if(is_file("/home/aces/run/aces_stream-$STREAMID.pid"))
                                    unlink("/home/aces/run/aces_stream-$STREAMID.pid");

                    }

                    exec(" rm -rf $FOLDER/$STREAMID-*");

                    if(is_file("/home/aces/run/aces_stream-$STREAMID.pid"))
                            unlink("/home/aces/run/aces_stream-$STREAMID.pid");

                            if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );

                            $run = "php /home/aces/bin/$b $STREAMID- ";
                            exec("$run > /dev/null &");
                            //exec("nohup $run > /dev/null & " );


                            //$c = exec("pgrep -cf 'aces_stream.php'");
                            //$n = exec("pgrep -cf 'aces_nstream.php'");

                            //$c = $c + $n;

                            //$DB->query("UPDATE iptv_servers SET streams = '$c' WHERE id = $SERVER_ID ");



    } else if($data['action'] == 'restart_nstream') {


            if( $pid = (int) exec("pgrep -f 'aces_nstream.php $STREAMID-'",$pids) )  {

                    foreach($pids as $pid) exec("kill -9 $pid "); exec("kill -9 $pid ");
                    exec("kill -9  $(ps -eAf  | grep /$STREAMID-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");


                    if(is_file("/home/aces/run/aces_nstream-$STREAMID.pid"))
                            unlink("/home/aces/run/aces_nstream-$STREAMID.pid");

            }

            exec(" rm -rf $FOLDER/$STREAMID-*");

            if(is_file("/home/aces/run/aces_nstream-$STREAMID.pid"))
                    unlink("/home/aces/run/aces_nstream-$STREAMID.pid");


                    $run = "php /home/aces/bin/aces_nstream.php $STREAMID- ";
                    exec("nohup $run > /dev/null & " );

                    if(!is_dir("/home/aces/run/")) mkdir("/home/aces/run/",0777 );


                    $c = exec("pgrep -cf 'aces_stream.php'");
                    $n = exec("pgrep -cf 'aces_nstream.php'");

                    $c = $c + $n;

                    $DB->query("UPDATE iptv_servers SET streams = '$c' WHERE id = $SERVER_ID ");




    } else if($data['action'] == 'get_stream_logs') {

            $data['logs'] =  file_get_contents("/home/aces/logs/streams/stream-$STREAMID.log");
            
    } else if($data['action'] == 'get_video_logs') { 
        
        $data['logs'] =  file_get_contents("/home/aces/logs/vods/{$data['video_id']}.log");

    } else if($data['action'] == 'get_stream_info') {

            //BUG NOTE ON HLS RECONNNECTION THE SEGMENT NUMBER COULD CHANGE..
            $chunk = exec("tail -1 /home/aces/stream_tmp/$STREAMID-.m3u8 ");


            exec("ffprobe -v quiet  -print_format json -show_streams -show_format -i /home/aces/stream_tmp/$chunk",$a );
            $data['stream_logs'] =  file_get_contents("/home/aces/logs/streams/stream-$STREAMID.log");


            for($i=0;isset($a[$i]);$i++) $str .= $a[$i];
            $data['stream_info'] =  json_decode($str,1);



    } else if($data['action'] == 'get_server_load') {

            $net = exec("route | grep 'default' | awk ' {print $8}'",$net);


            clearstatcache();
            $rx[] = @file_get_contents("/sys/class/net/$net/statistics/rx_bytes");
            $tx[] = @file_get_contents("/sys/class/net/$net/statistics/tx_bytes");
            sleep(1);clearstatcache();
            $rx[] = @file_get_contents("/sys/class/net/$net/statistics/rx_bytes");
            $tx[] = @file_get_contents("/sys/class/net/$net/statistics/tx_bytes");


            $tbps = (($tx[1] - $tx[0]) / 0.125);
            $rbps = (($rx[1] - $rx[0]) / 0.125);

            $round_rx=round($rbps/1024000, 2);
            $round_tx=round($tbps/1024000, 2);

            $free = shell_exec('free');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);

            $data['memory_usage'] =  round($mem[2]/1024,2);
            $data['memory_total'] = round($mem[1]/1024,2);
            $data['memory_usage_percent']  = $mem[2]/$mem[1]*100;
            $data['cpu_load'] = round((sys_getloadavg()[0]*100/count(sys_getloadavg())/10)  );
            $data['tx_bandwidth'] = $round_tx ;
            $data['rx_bandwidth'] = $round_rx ;
            $data['disk_usage'] = round( (disk_total_space('/')-disk_free_space('/'))  / 1000000000 ,2) ;
            $data['disk_total'] = round(disk_total_space('/')/1000000000,2)  ;
            $data['disk_usage_percent'] = (disk_total_space('/')-disk_free_space('/')) / disk_total_space('/') * 100;
            $data['uptime'] = explode(' ',trim( file_get_contents( '/proc/uptime' ) ))[0];



            $a = explode('up',trim(exec('uptime')));
            $a= explode(',',$a[1]);
            if (strpos($a[0], 'days') !== false)
                    $data['uptime_string'] = " {$a[0]} {$a[1]} " ;

                    else $data['uptime_string'] = $a[0];



    } else if($data['action'] == 'process_vod') {

        if(!empty($STREAMID)) $VOD_ID = $STREAMID;
        else $VOD_ID = $data['vod_id'];
        
        if(is_file("/home/aces/logs/vods/{$VOD_ID}-prog.txt")) unlink("/home/aces/logs/vods/{$VOD_ID}-prog.txt");
        
        if(is_file("/home/aces/logs/vods/{$VOD_ID}.log")) unlink("/home/aces/logs/vods/{$VOD_ID}.log");
        
        if(is_file("/home/aces/run/aces_process_video_{$VOD_ID}.pid")) { 
            $pid = file_get_contents("/home/aces/run/aces_process_video_{$VOD_ID}.pid");
            exec( "kill -9 $pid " );
        }
        
        exec(" php /home/aces/bin/aces_process_video.php $VOD_ID- > /dev/null & " );


    } else if($data['action'] == 'move_video') {

        if(!empty($STREAMID)) $VOD_ID = $STREAMID;
        else $VOD_ID = $data['vod_id'];

        if(is_file("/home/aces/logs/vods/moving-$VOD_ID.log")) unlink("/home/aces/logs/vods/moving-$VOD_ID.log");

        if(is_file("/home/aces/logs/vods/$VOD_ID-move-prog.txt")) unlink("/home/aces/logs/vods/$VOD_ID-move-prog.txt");

        if($PID = (int)  exec("pgrep -f 'aces_move_video.php $VOD_ID-'") ) exec("kill -9 $PID ");

        exec("nohup php /home/aces/bin/aces_move_video.php $VOD_ID- > /dev/null & " );

    } else if($data['action'] == 'get_vod') {

        $VOD_ID = $data['vod_id'];
        exec("nohup php /home/aces/bin/aces_copy_vod.php $VOD_ID- > /dev/null & " );


    } else if($data['action'] == 'remove_vod' ) { 

        if(!empty($STREAMID)) $VOD_ID = $STREAMID;
        else $VOD_ID = $data['vod_id'];

        $r=$r=$DB->query("SELECT container,source_file,transcoding FROM iptv_video_files WHERE id = $VOD_ID ");
        list($c,$source_file,$transcoding) = mysqli_fetch_array($r);
        
        //$DB->query("DELETE FROM iptv_video_files WHERE id = $VOD_ID  ");
                
        if(!$c) $c='mp4';

        $PID_FILE = "/home/aces/run/aces_process_video_{$VOD_ID}.pid";
        if( is_file($PID_FILE) && $PID = file_get_contents($PID_FILE))  { 
            if(posix_getpgid($PID)) {
                exec("kill -9 $PID");
            }
        } 
        
        //if($PID = (int)  exec("pgrep -f 'aces_process_vod.php $VOD_ID-'") ) exec("kill -9 $PID ");
        //if($PID = (int)  exec("pgrep -f 'aces_copy_vod.php $VOD_ID-'") ) exec("kill -9 $PID ");

       while($pid = exec(" ps -eAf  | grep /home/aces/vods/$VOD_ID.$c | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
                if($pid) exec("kill -9 $pid ");

        //while($pid = exec(" ps -eAf  | grep /home/aces/vods/$VOD_ID-.m3u8 | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
        //{ if($pid) exec("kill -9 $pid ");  } 
        
        while($pid = exec(" ps -eAf  | grep /home/aces/tmp/vod_downloads/$VOD_ID | grep wget | grep -v ps | awk '{print $2 }' " ))
        { if($pid) exec("kill -9 $pid ");  } 


                
        exec("rm -rf /home/aces/tmp/vod_downloads/$VOD_ID ");
        //exec("rm -rf /home/aces/vods/$VOD_ID-* ");
        exec("rm -rf /home/aces/vods/$VOD_ID.* ");
        //exec("rm -rf /home/aces/vods/$VOD_ID* ");
        exec("rm -f /home/aces/logs/vods/$VOD_ID.log");
        exec("rm -f /home/aces/logs/vods/$VOD_ID-prog.txt");
        
        
        if($data['remove_source_file'] && $transcoding == 'symlink') { 
            $source_file = urldecode($source_file);
            $source_file=addslashes($source_file);
            $source_file = str_replace(" ", "\\ ",$source_file);
            $source_file = str_replace("(", "\(",$source_file);
            $source_file = str_replace(")", "\)",$source_file);
            $source_file = str_replace("&", "\&",$source_file);
            exec("rm -f ".urldecode($source_file));
            
        }


    }  else if($data['action'] == 'reboot_server' ) {

            exec(' sudo reboot ');


    } else if($data['action'] == 'restart_aces' ) {
        
            ignore_user_abort(true);

            //DETECTING DISTRO AND VERSION.
            if(is_file('/etc/os-release')) {
                    $fp = fopen('/etc/os-release','r');

                    $arr= array();
                    while (($line = fgets($fp)) !== false) {
                            $e = explode('=',$line);
                            $arr[ str_replace('"','',$e[0]) ]= str_replace('"','',@$e[1]);
                    }

                    //ITS CENTOS
                    if(isset($arr['ID'])) {

                            $DIST = trim(strtoupper($arr['ID']));
                            $DIST_VER = (int)trim($arr['VERSION_ID']);

                    //ITS UBUNTU
                    } else {

                            $DIST =  trim(strtoupper($arr['DISTRIB_ID']));
                            $DIST_VER =  (int)trim($arr['DISTRIB_RELEASE']);
                    }

            }
            
            echo json_encode($data);
            fastcgi_finish_request();
            
            exec(" kill -9  $(ps aux | grep 'aces_stream.php' | awk '{print $2}') 2>/dev/null  ");
            exec(" kill -9  $(ps aux | grep 'ffmpeg' | awk '{print $2}') 2>/dev/null  ");
            sleep(1);

            if( $DIST == 'UBUNTU' && $DIST_VER == 14 ) { 
                exec("( sudo service aces-nginx restart; sudo service aces-php5-fpm restart; sudo service aces restart; ) >/dev/null 2>&1 &"); 
            } else { 
                exec("( sudo systemctl restart aces-nginx aces-php-fpm aces ) >/dev/null 2>&1 &"); 
                exec("( sudo systemctl restart  aces ) >/dev/null 2>&1 &");
            }

    } else if($data['action'] == 'update_aces' ) {


            exec (' cd /home/aces/tmp/; rm ACES/ -rf; rm ACES.tar.gz; wget http://acescript.ddns.net/download.php -O ACES.tar.gz; tar -xf ACES.tar.gz; cd ACES/; ');

            //$xml = simplexml_load_file('/home/aces/tmp/ACES/manifest.xml');
            //$manifest = json_decode(json_encode($xml),TRUE);
            //if(!is_array($manifest)) {  error_log('INSTALLER CANNOT LOAD MANIFEST.'); $data['update_complete'] = 0; }
            //else {

                    //DETECTING DISTRO AND VERSION.
//                    if(is_file('/etc/os-release')) {
//                            $fp = fopen('/etc/os-release','r');
//
//                            $arr= array();
//                            while (($line = fgets($fp)) !== false) {
//                                    $e = explode('=',$line);
//                                    $arr[ str_replace('"','',$e[0]) ]= str_replace('"','',@$e[1]);
//                            }
//
//                            //ITS CENTOS
//                            if(isset($arr['ID'])) {
//
//                                    $DIST = trim(strtoupper($arr['ID']));
//                                    $DIST_VER = (int)trim($arr['VERSION_ID']);
//
//                                    //ITS UBUNTU
//                            } else {
//
//                                    $DIST =  trim(strtoupper($arr['DISTRIB_ID']));
//                                    $DIST_VER =  (int)trim($arr['DISTRIB_RELEASE']);
//                            }
//
//                    }

                    //UPDATING
                    //if(!$DIST) { error_log('THIS DISTRO IS NOT SUPPORTED.'); $data['update_complete'] = 0; }

//                    if($DIST != 'UBUNTU' && $DIST != 'CENTOS') { error_log('THIS DISTRO IS NOT SUPPORTED.'); $data['update_complete'] = 0; }
//
//                    if($DIST == 'CENTOS' && $DIST_VER != 7) { error_log('THIS VERSION OF CENTOS IS NOT SUPPORTED.');  $data['update_complete'] = 0; }
//
//                    if($DIST == 'UBUNTU' )
//                            if($DIST_VER != 14 && $DIST_VER != 16 && $DIST_VER != 18 ) { error_log('THIS VERSION OF UBUNTU IS NOT SUPPORTED.');  $data['update_complete'] = 0; }

                    //$restart_aces_service = 0;
                    //if ( md5_file('/home/aces/bin/aces.php') != md5_file('/home/aces/tmp/ACES/HOME/bin/aces.php')  ) $restart_aces_service = 1;

                    if(!is_dir("/home/aces/panel/class"))
                        mkdir("/home/aces/panel/class",775);

                    exec('cd /home/aces/tmp/ACES; cp -rf HOME/bin/* /home/aces/bin/; cp -rf HOME/stream/* /home/aces/stream/; cp HOME/panel/class/TMDB.php /home/aces/panel/class/; cd /home/aces/tmp/;  rm ACES/ -rf; rm ACES.tar.gz; ');


                    $restart_service = null;


// 			if($manifest['nginx_conf'] !== md5_file('/home/aces/etc/nginx/nginx.conf')) {
// 				exec('cd /home/aces/tmp/ACES; cp -rf FILES/nginx/* /home/aces/etc/nginx/; ');
// 				if($DIST == 'UBUNTU' && $DIST_VER < 15 ) $restart_service[] = ' sudo service aces-nginx restart ';
// 				else $restart_service .= ' aces-nginx ';
// 			}


// 			if($manifest['restart-db']) {
// 				if($DIST == 'CENTOS') $restart_service .= ' mariadb ';
// 				else if($DIST == 'UBUNTU' && $DIST_VER > 14 ) $restart_service .= ' mysql ';
// 				else if($DIST == 'UBUNTU' && $DIST_VER < 15 ) $restart_service[] = ' sudo  service mysql restart  ';
// 			}

// 			if($manifest['php_conf'] !== md5_file('/home/aces/etc/php-fpm.conf')) {
// 				exec('cd /home/aces/tmp/ACES; cp -rf FILES/php-fpm.conf /home/aces/etc/; ');
// 				if($DIST == 'CENTOS') $restart_service .= ' aces aces-php-fpm ';
// 				else if($DIST == 'UBUNTU' && $DIST_VER > 14 ) $restart_service .= ' aces aces-php-fpm ';
// 				else if($DIST == 'UBUNTU' && $DIST_VER < 15 ) $restart_service[] = ' aces-php5-fpm   ';
// 			}


//                    if ($restart_aces_service) {
//                            if($DIST == 'CENTOS' || $DIST == 'UBUNTU' && $DIST_VER > 14 ) $restart_service .= ' aces ';
//                            else if($DIST == 'UBUNTU' && $DIST_VER < 15) $restart_service[] = ' aces ';
//                    }
//
//                    if($restart_service) {
//                            if($DIST == 'CENTOS')  exec("sudo systemctl restart $restart_service ");
//                            else if($DIST == 'UBUNTU' && $DIST_VER > 14 )  exec("sudo systemctl restart $restart_service ");
//                            else if($DIST == 'UBUNTU' && $DIST_VER < 15 ) foreach($restart_service as $s  ) exec(" sudo  service $s restart ");
//                    }

                    exec(" sudo  service aces restart ");

                    //if(!isset( $data['update_complete'] ))
                        $data['update_complete'] = 1;

            //}
            
    }  else if($data['action'] == 'stream_client' ) { 
        
        $token = $data['client_token'];

        exec(" php /home/aces/bin/aces_stream_client.php $STREAMID $token > /dev/null & " );
        
        
    } else if($data['action'] == 'start_streamer' ) { 
        
        $access_id  = $data['access_id'];

        exec(" php /home/aces/bin/streamer.php $access_id  > /dev/null & " );
        $i=0;
        while ( true ) { 
            clearstatcache();
            usleep( 50000 );
            if($i > 100 || is_file("/home/aces/stream_tmp_clients/$access_id-.m3u8") ) break;
            $i++;
            
        }
        
    } else if($data['action'] == 'remove_channel_files' ) { 
        
        foreach($data['channel_files'] as $f ) 
            unlink("/home/aces/channel_files/$f.ts");
        
    } else if($data['action'] == 'loader_options') { 
        
        if(is_file("/home/aces/loaders/{$data['loader']}/{$data['loader']}.php")) { 
            $data['loader_options'] = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php options "),true); 
        }
        
    } else if($data['action'] == 'get_loaders') { 
        
        $data['loaders'] = array();
        foreach(glob('/home/aces/loaders/*',GLOB_ONLYDIR) as $f ) { 
            
            $data['loaders'][] = basename($f);
            
        }
        
    } else if($data['action'] == 'loader_config') {

        $json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php config"),1);
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = 'Sorry something is wrong'; } 
        else { $data['loader_config'] = $json; }
        
        //$data['loader_config'] = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php config"),1);
        
    } else if($data['action'] == 'loader_set_config') { 
        
        $loader_config = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php config"),1);
        $POST = $data['data'];
        
        foreach($loader_config as $inputs ) { 

            foreach($inputs as $type => $opts) { 
                if(isset($opts['required']) && $opts['required'] == 1  && empty($POST[$type])) { $data['errors'] = 1 ; $data['error_msg'] = 'An input is missing.'; }
                if(!empty($POST[$type])) { 

                    if($opts['type'] == 'email' && !filter_var($POST[$type], FILTER_VALIDATE_EMAIL)) { $data['errors'] = 1 ; $data['error_msg'] = "'{$POST[$type]}' is not a valid email."; }
                    else $json[$type] = $POST[$type];

                }
            }

        }
        
        if($data['errors'] == 0 ) {
            if(!file_put_contents ("/home/aces/loaders/{$data['loader']}/{$data['loader']}.config", json_encode($json) )) { $data['errors'] = 1 ; $data['error_msg'] = 'Fail to write loader configuration.';  error_log('Fail to write loader configuration.'); }
            exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php clean");
        }
        
    } else if($data['action'] == 'loader_actions' ) {

        $json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php actions"),1);
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = $json['error']; } 
        else { $data['loader_actions'] = $json; }
        //error_log(print_r($json,1));
        
    } else if($data['action'] == 'loader_test' ) { 
        
        $json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php actions"),1);
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = $json['error']; }
        if(!in_array('test',$json['actions'])) { $data['errors'] = 1; $data['error_msg'] = 'This loader doesn\'t have this action.'; }
        
        if(!$json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php test"),1)) { $data['errors'] = 1 ; $data['error_msg'] = 'Script fail to run. Unable to perform test.'; } 
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = $json['error']; }
        else if($json['test'] == 'ok') $data['loader_test'] = '1';
        else { $data['errors'] = 1 ; $data['error_msg'] = 'Unknown Error.'; } 
        
    } else if($data['action'] == 'loader_clean' ) { 
        
        $json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php actions"),1);
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = $json['error']; }
        if(!in_array('clean',$json['actions'])) { $data['errors'] = 1; $data['error_msg'] = 'This loader doesn\'t have this action.'; }
        
        if(!$json = json_decode(exec("php /home/aces/loaders/{$data['loader']}/{$data['loader']}.php clean"),1)) { $data['errors'] = 1 ; $data['error_msg'] = 'Script fail to run. Unable to perform test.'; } 
        if($json['status'] == 'error')  {  $data['errors'] = 1; $data['error_msg'] = $json['error']; }
        else if($json['clean'] == 'ok') $data['loader_clean'] = '1';
        else { $data['errors'] = 1 ; $data['error_msg'] = 'Unknown Error.'; } 
        
        
    } else if($data['action'] == 'get_local_vods') {
        
        set_time_limit ( 0 );
        
        //exec(' find -L /home/aces/aces_vods/ -type f | grep -iE "\.avi$|\.mkv$|\.flv$|\.wmv$|\.m4v$" ',$files);
        //$data['vods'] = $files;
        
        function getDirContents($dir, &$results = array()) {
            $files = scandir($dir);

            foreach ($files as $key => $value) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
                if (!is_dir($path)) {
                    $results[] = $path;
                } else if ($value != "." && $value != "..") {
                    getDirContents($path, $results);
                    $results[] = $path;
                }
            }

            return $results;
        }

        function rglob($pattern, $flags = 0) {
            $files = glob($pattern, $flags); 
            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
            }
            return $files;
        }
        
        
        $formats = array('mp4','avi','mkv','flv','wmv','m4v');
        $data['vods']=array();
        //$files= rglob('/home/aces/aces_vods/*');
        $files=getDirContents('/home/aces/aces_vods/');
        foreach ($files as $file ) { 
            if(!is_dir($file) && in_array( pathinfo($file, PATHINFO_EXTENSION) ,$formats) ) {
                $data['vods'][] = $file;
            }

        }
        
    } else if($data['action'] == 'get_local_subs') { 
        
        set_time_limit ( 0 );
        
        exec('find -L /home/aces/aces_vods/ -type f | grep -iE "\.srt$" ',$files);
        $data['subs'] = $files;

//        function rglob($pattern, $flags = 0) {
//            $files = glob($pattern, $flags); 
//            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
//                $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
//            }
//            return $files;
//        }
//        
//        $data['vods']=array();
//        $files=rglob('/home/aces/aces_vods/*');
//        foreach ($files as $file ) { 
//            if(!is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) == 'srt') {
//                $data['subs'][] = $file;
//            }
//
//        }
        
    } else if($data['action'] == 'get_local_dirs') { 
        
        set_time_limit ( 0 );
        exec(' find -L /home/aces/aces_vods/ -type d  ',$dirs);
        unset($dirs[0]);
        $data['dirs'] = $dirs;

        

//        function rglob($pattern, $flags = 0) {
//            $files = glob($pattern, $flags); 
//            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
//                $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
//            }
//            return $files;
//        }
//        
//        $data['dirs']=array();
//        $files=rglob('/home/aces/aces_vods/*',GLOB_ONLYDIR);
//        foreach ($files as $file ) { 
//            
//            $data['dirs'][] = $file;
//
//        }
        
    
    } else if($data['action'] == 'video_dir_import' ) { 
        
        $proccess  = $data['proccess_id'];

        exec(" php /home/aces/bin/aces_video_dir_import.php $proccess  > /dev/null & " );

    } else if($data['action'] == 'series_dir_import' ) { 
        
        $proccess  = $data['proccess_id'];

        exec(" php /home/aces/bin/aces_series_dir_import.php $proccess  > /dev/null & " );

    } else if($data['action'] == 'get_content') { 
        
        $folders=array();
        $data['path'] = urldecode($data['path']);
        
        $data['path'] = str_replace("[","\[",$data['path']);
        $data['path'] = str_replace("]","\]",$data['path']);
        $data['path'] = str_replace("{","\{",$data['path']);
        $data['path'] = str_replace("}","\}",$data['path']);
        
        $folders = glob($data['path'] . '/*' , GLOB_ONLYDIR);
                
        if($data['type'] == 'directories' ) { $data['content']  = $folders;   echo json_encode($data); exit; }        
        else if($data['type'] == 'vods') {  $files = glob("{$data['path']}/*.{mp4,avi,mkv,mp3,mp2,wav,flv,m4v,wmv,wma}", GLOB_BRACE); }
        else if($data['type']  == 'subs' ) {   $files = glob("{$data['path']}/*.{srt}", GLOB_BRACE); }

        $data['content']  = array_merge($folders, $files);

        
         
    } 
    
    
    
    

    echo json_encode($data);
