<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission()) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

use \ACES2\IPTV\Server;

try {
    switch($_REQUEST['action']) {

        case 'get_stats':
            $servers = [];
            $db = new \ACES2\DB();
            $r=$db->query("SELECT * FROM iptv_servers ");
            while($row=$r->fetch_assoc()) {

                $status_msg = '';
                switch($row['status']) {
                    case -1:
                        $status_msg = 'Installing Server'; break;
                    case -2:
                        $status_msg = 'Fail to install server'; break;
                    case Server::STATUS_ERROR_UPDATING :
                        $status_msg = 'Error updating ACES'; break;
                    case Server::STATUS_UPDATING:
                        $status_msg = 'Server is updating ACES Software '; break;

                    default:
                        $status_msg = 'Waiting for server.';
                }

                $servers[$row['id']] = array(
                    'status' => $row['status'],
                    'stats' => unserialize($row['stats']),
                    'cpu_history' => json_decode($row['cpu_history'],1),
                    'ram_history' => json_decode($row['ram_history'],1),
                    'bandwidth_history' => json_decode($row['bandwidth_history'],1),
                    'hard_disks' => json_decode($row['hard_disks']),
                    'latency' => $row['latency'],
                    'latency_percent' => round(($row['latency'] / 300) * 100),
                    'status_msg' => $status_msg,
                    'error_msg' => $row['error_msg'],
                );

            }
            echo json_encode($servers);
            exit;

        case 'reboot_server':
            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);
            $Server->reboot();
            break;

        case 'add_server':
            \ACES2\IPTV\Server::add($_REQUEST['ip_address'], $_REQUEST['ssh_password'],
                $_REQUEST['ssh_port'], $_REQUEST['name'], $_REQUEST['serial'], $_REQUEST['bandwidth_port']);
            break;

        case 'update_server':
            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);
            $Server->update($_REQUEST['address'], $_REQUEST['name'], $_REQUEST['bandwidth_port'],
                $_REQUEST['ssh_password']);
            break;

        case 'remove_server':
            $Server = new \ACES2\IPTV\Server($_REQUEST['server_id']);
            if($Server->id == 1)
                throw new \Exception("Main Server cannot be removed.");
            $Server->remove();
            break;

        case 'update_aces':
            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);
            if($Server->id == 1)
                throw new \Exception("Main Server cannot be updated from panel.");

            session_write_close();
            ignore_user_abort(TRUE);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $Server->updateAces();

            break;

        case 'restart_services':
            $Server =  new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);

            session_write_close();
            ignore_user_abort(TRUE);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $Server->restart_service();

            break;

        case 'stop_streams':
        case 'restart_streams':
        case 'start_streams':

            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);

            session_write_close();
            ignore_user_abort(TRUE);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $db = new \ACES2\DB;

            switch($_REQUEST['action']) {
                case 'start_streams':
                    $r=$db->query("SELECT c.id FROM iptv_channels c WHERE stream_server = '$Server->id' AND c.enable = 1
                        AND c.stream = 1 AND NOT EXISTS ( SELECT s.chan_id FROM iptv_streaming s WHERE s.chan_id = c.id )  ");
                    while($row=$r->fetch_assoc()) {
                        $Stream = new \ACES2\IPTV\Stream((int)$row['id']);
                        $Stream->restart();
                    }
                    break;

                case 'stop_streams':
                    $r=$db->query("SELECT c.id FROM iptv_channels c WHERE stream_server = '$Server->id' AND c.enable = 1
                        AND c.stream = 1 AND EXISTS ( SELECT s.chan_id FROM iptv_streaming s WHERE s.chan_id = c.id )  ");
                    while($row=$r->fetch_assoc()) {
                        $Stream = new \ACES2\IPTV\Stream((int)$row['id']);
                        $Stream->stop();
                    }
                    break;

                case 'restart_streams':
                    $r=$db->query("SELECT c.id FROM iptv_channels c WHERE stream_server = '$Server->id' AND c.enable = 1
                        AND c.stream = 1 AND EXISTS ( SELECT s.chan_id FROM iptv_streaming s WHERE s.chan_id = c.id )  ");
                    while($row=$r->fetch_assoc()) {
                        $Stream = new \ACES2\IPTV\Stream((int)$row['id']);
                        $Stream->restart();
                    }
                    break;
            }


            break;


        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    logD($e->getMessage());
    setAjaxError($e->getMessage());
}

setAjaxComplete();