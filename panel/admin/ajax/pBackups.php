<?php

$ADMIN = new \ACES2\ADMIN();
if (!$ADMIN->isLogged(true)) {
    $json['not_logged'] = 1;
    echo json_encode($json);
    exit;
}

if (!$ADMIN->hasPermission('')) {
    setAjaxError(\ACES\ERRORS::NO_PRIVILEGES);
}


switch ($_REQUEST['action']) {

    case 'create_backup':

        set_time_limit(0);

        $pid = (int)file_get_contents("/home/aces/run/aces_backup.pid");
        if ($pid && posix_getpgid($pid))
            setAjaxError("There another backup process running.");

        $backup_name = $_REQUEST['name'] ? trim($_REQUEST['name']) : "backup-" . date('Y-M-d_h:i');
        if (!preg_match('/^[a-zA-Z0-9-:_-]+$/', $backup_name))
            setAjaxError("Invalid backup name.");

//        $descriptorspec = array(
//            0 => array("pipe", "r"),
//            1 => array("pipe", "w"),
//            2 => array("pipe", "w")
//        );

        $command = "php /home/aces/bin/backup.php $backup_name ";

//        $process = proc_open($command, $descriptorspec, $pipes);

        $pid = shell_exec(sprintf(
            '%s > %s 2>&1 & echo $!',
            $command,
            "/dev/null"
        ));

//        proc_get_status($process)['pid'];
        ignore_user_abort();
        file_put_contents("/home/aces/run/aces_backup.pid", $pid);
        echo json_encode(array('complete' => 1, 'status' => 1));
        fastcgi_finish_request();
        session_write_close();


        while (true) {
            if (!posix_getpgid($pid))
                break;

            sleep(5);
        }


        unlink("/home/aces/run/aces_backup.pid");
        exit;

    case 'remove_backup':
        $backup_name = urldecode($_REQUEST['filename']);
        if (!$backup_name or !is_file("/home/aces/backups/" . $backup_name))
            setAjaxError("File could not be found.");

        if (!unlink("/home/aces/backups/{$backup_name}"))
            setAjaxError("Unable to remove backup file");

        break;

    case 'is_backup_running':

        clearstatcache();
        $pid = (int)file_get_contents("/home/aces/run/aces_backup.pid");

        $d['backup_is_running'] = 0;
//        if(posix_getpgid($pid)) {
        if ($pid) {
            $d['backup_is_running'] = 1;
            AcesLogD("RUNNING $pid");
        }

        setAjaxComplete($d);
        break;

    case 'remove_backup_location':

        if($id = (int)$_REQUEST['id']) {
            $db = new \ACES2\DB;
            $db->query("DELETE FROM backup_locations WHERE id = '$id'");
        }

        break;

    case 'add_backup_locations':

        $db = new \ACES2\DB;

        foreach($_REQUEST['protocol'] as $i => $protocol) {

            $id = (int)$_REQUEST['id'][$i];

            switch($protocol) {

                case 'dir':

                    $dir = $_REQUEST['location'][$i];

                    if($dir == '/home/aces/backups' || $dir == '/home/aces/backups')
                        setAjaxError("Directory '$dir' is already being used to storage backups.");

                    if(!is_dir($dir))
                        setAjaxError("Directory '$dir' does not exist.");

                    $filetest = "test-".time();

                    if(!touch("$dir/$filetest"))
                        setAjaxError("Unable to write on directory $dir");

                    unlink("$dir/$filetest");

                    if(!$id)
                        $db->query("INSERT INTO backup_locations (protocol, location)  
                            VALUES ('$protocol', '$dir' )");
                    else
                        $db->query("UPDATE backup_locations SET  location = '$dir', error_msg = ''
                        WHERE id = '$id'");

                    break;

                case 'scp':

                    if($id) {

                        $r=$db->query("SELECT * FROM backup_locations WHERE  id = '$id' ");
                        $row = $r->fetch_assoc();

                        if($_REQUEST['password'][$i] == '' &&
                            $_REQUEST['username'][$i] == $row['username'] &&
                            $_REQUEST['location_scp'][$i] == $row['location'] )

                                break;

                    }

                    list($ip,$location) = explode(':',$_REQUEST['location_scp'][$i]);

                    if(!$connection = ssh2_connect($ip, 22))
                        setAjaxError("Could not connect to server '$ip'");

                    if(!ssh2_auth_password($connection, $_REQUEST['username'][$i], $_REQUEST['password'][$i]))
                        setAjaxError("Unable to connect to server '$ip'. Wrong password or username. ");

                    $filetest = "test-".time();
                    touch("/home/aces/tmp/$filetest");

                    $test = ssh2_scp_send($connection, "/home/aces/tmp/$filetest", "$location/$filetest", 0644);
                    unlink("/home/aces/tmp/$filetest");

                    if(!$test)
                        setAjaxError("Unable to write on directory '$location' of $ip server.");

                    ssh2_exec($connection, "rm $location/$filetest ");

                    $username = $db->escString($_REQUEST['username'][$i]);
                    $password = $db->escString($_REQUEST['password'][$i]);

                    if(!$id)
                        $db->query("INSERT INTO backup_locations (protocol, location, username, password)  
                            VALUES ('$protocol', '{$_REQUEST['location_scp'][$i]}', '$username', '$password')");
                    else
                        $db->query("UPDATE backup_locations SET  location = '{$_REQUEST['location_scp'][$i]}',
                            username = '$username', password = '$password',  error_msg = ''
                            WHERE id = '$id'");

                    break;


            }

        }

        setAjaxComplete();

        break;

    default:
        logE("Unknown action: {$_REQUEST['action']}");
        setAjaxError("Unknown Error.");

}

setAjaxComplete();