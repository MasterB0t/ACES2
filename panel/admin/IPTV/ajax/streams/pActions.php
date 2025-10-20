<?php

$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}


try {

    switch ($_POST['action']) {

        case 'analyze_stream_source':

            session_write_close();
            set_time_limit(16);
            $json = \ACES2\IPTV\StreamSource::getSourceStats($_POST['stream_url']);
            if($json == null) {
                setAjaxError('', 408);
                exit;
            }
            setAjaxComplete($json);

            break;

        case 'remove_stream' :
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            $Stream = new \ACES2\IPTV\Stream($_REQUEST['stream_id']);
            $Stream->remove();
            break;

        case 'restart_stream' :
            $Stream = new \ACES2\IPTV\Stream($_GET['channel_id']);
            $Stream->restart();
            break;

        case 'stop_stream' :
            $Stream = new \ACES2\IPTV\Stream($_GET['channel_id']);
            $Stream->stop();
            break;

        case 'mass_restart' :

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            foreach ($_POST['channels'] as $channel_id ) {
                $stream = new \ACES2\IPTV\Stream($channel_id);
                $stream->restart();
            }
            break;

        case 'mass_stop' :
            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            foreach ($_POST['channels'] as $channel_id ) {
                $stream = new \ACES2\IPTV\Stream($channel_id);
                $stream->stop();
            }
            break;

        case 'mass_remove' :
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            foreach ($_POST['channels'] as $channel_id ) {
                $stream = new \ACES2\IPTV\Stream($channel_id);
                $stream->remove();
            }
            break;


        case 'mass_selected_edit' :

            //TODO SEND ERROR NO PERMISSIONS INSTEAD OF EXCEPTION.
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $post=[];

            $set_stream_server = (int)$_POST['stream_server'];

            if(!empty($_POST['category']))
                $post['category_id'] = (int)$_POST['category'];

            if(!empty($_POST['stream_profile']))
                $post['stream_profile'] = (int)$_POST['stream_profile'];

            if($_POST['channel_status'] != '')
                $post['enable'] = $_POST['channel_status'] ? 1 : 0;

            if($_POST['stream_channel'] != '')
                $post['stream'] = $_POST['stream_channel'] ?  1 : 0 ;

            if($_POST['channel_ondemand'] != '')
                $post['ondemand'] = $_POST['channel_ondemand'] ? 1 : 0;

            if($_POST['sync_stream_name'] !== "") {
                $post['auto_update'] = $_POST['sync_stream_name'] ? 1 : 0;
            }

            $sql = ArrayToSql($post);
            foreach ($_POST['ids'] as $id) {

                if( (int)$id ) {

                    if($set_stream_server) {
                        $Stream = new \ACES2\IPTV\Stream($id);
                        $Stream->setServerID($set_stream_server,
                            false); //USING METHOD TO REMOVE LBS IF STREAM SERVER HAVE CHANGED.
                    }

                    if (count($post) > 0) {

                        $DB->query("UPDATE iptv_channels SET $sql WHERE id = $id ");
                    }

                    if (is_array($_POST['bouquets']) && count($_POST['bouquets']) > 0) {

                        $DB->query("DELETE FROM iptv_channels_in_bouquet WHERE chan_id = $id ");
                        foreach ($_POST['bouquets'] as $b) {
                            if ((int)$b)
                                $DB->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id, chan_id) VALUE ('$b',$id )");
                        }

                    }
                }
            }


            break;

        case 'start_all_channels' :

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $r=$DB->query("SELECT id FROM iptv_channels WHERE enable  = 1 ");
            while($id = $r->fetch_assoc()['id']) {
                $stream = new \ACES2\IPTV\Stream($id);
                $stream->restart();
            }

            break ;


        case 'restart_all_channels' :

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $r=$DB->query("SELECT chan_id FROM iptv_streaming ");
            while($id=$r->fetch_assoc()['chan_id']) {
                $stream = new \ACES2\IPTV\Stream($id);
                $stream->restart();
            }

            break;

        case 'stop_all_channels' :

            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $r=$DB->query("SELECT chan_id FROM iptv_streaming ");
            while($id = $r->fetch_assoc()['chan_id']) {
                $stream = new \ACES2\IPTV\Stream($id);
                $stream->stop();
            }

            break;


        case 'add_load_balance' :

            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $chan_id = $_POST['channel_id'];
            $stream = new \ACES2\IPTV\Stream($_POST['channel_id']);
            $stream->addLoadBalance($_POST['source'],$_POST['destination_server']);

            break;

        case 'stream_set_load_balance':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $streams = explode(",",$_POST['stream_ids']);

            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['main_server']);

            foreach($streams as $stream) {
                $Stream = new \ACES2\IPTV\Stream((int)$stream);
                if($Stream->server_id != $Server->id ) {
                    $Stream->setServerId($Server->id, false); //WE NEED TO RESTART STREAM AFTER SETTINGS LBS
                    $restart_stream = (bool)$Stream->getStatus() != \ACES2\IPTV\Stream::STATUS_STOPPED;
                }

                //ALWAYS RESET LBS.
                foreach($Stream->load_balances as $load_balance ) {
                    $Stream->removeLoadBalance($load_balance['destination']);
                }

                if(is_array($_POST['lb_source']))
                foreach($_POST['lb_source'] as $inx  => $s_lb ) {
                    $source_lb = (int)$s_lb;
                    $destination_lb = (int)$_POST['lb_destination'][$inx];
                    $Stream->addLoadBalance($source_lb,$destination_lb);
                }

                if($restart_stream)
                    $Stream->restart();

            }

            break;

        case 'remove_load_balance' :

            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $stream = new \ACES2\IPTV\Stream($_POST['channel_id']);
            $stream->removeLoadBalance($_POST['remove_destination']);

        case 'get_load_balances' :

            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $chan_id = (int)$_POST['channel_id'];
            $stream = new \ACES2\IPTV\Stream($_POST['channel_id']);
            $load_balances = [];
            //ADDING NAMES OF SERVERS...
            foreach($stream->load_balances as $lb) {

                $names=[];
                $names['source_name'] = '[STREAM SOURCE]';

                if($lb['source']) {
                    $ss = new \ACES2\IPTV\SERVER($lb['source']);
                    $names['source_name'] = $ss->name;
                }

                $sd= new \ACES2\IPTV\SERVER($lb['destination']);
                $names['destination_name'] = $sd->name;

                $load_balances[] = array_merge($lb,$names);

            }
            //$data['load_balances'] = $load_balances;
            setAjaxComplete($load_balances);

            break;

        case 'set_catchup' :

            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $stream = new \ACES2\IPTV\Stream($_POST['channel_id']);
            $r=$DB->query("SELECT id,tvg_id,stream_server,catchup_server,catchup_expire_days,catchup 
                                    FROM iptv_channels WHERE id = '$stream->id' ");
            if(!$row_chan=$r->fetch_assoc())
                throw new \ACES2\Exception(\ACES2\ERRORS::SYSTEM_ERROR);

            $exp_days = (int)$_POST['catchup_expire_days'];
            if(!$exp_days)
                throw new \ACES2\Exception(\ACES2\IPTV\ERRORS::IPTV_CATCHUP_EXP_DAYS_REQUIRED);

            $enable_catchup = ($_POST['enable_catchup']) ? 1 : 0;

            $server = new \ACES2\IPTV\SERVER($_POST['catchup_server']);

            //REMOVING..
            foreach($_POST['remove_catchup'] as $remove_id) {
                $remove_id = (int)$remove_id;
                $DB->query("UPDATE iptv_recording SET status = 4 WHERE id = $remove_id ");
            }

            $DB->query("UPDATE iptv_channels SET catchup = $enable_catchup, catchup_expire_days = '$exp_days', catchup_server = '$server->id' WHERE id = '$stream->id' ");
            $DB->query("UPDATE iptv_recording SET server_id = '$server->id', expire_date = NOW() + INTERVAL $exp_days DAY WHERE chan_id = $stream->id AND status = 0 ");

            if($row_chan['catchup'] && !isset($_POST['enable_catchup'])) {
                exec("kill -9  $(ps -eAf  | grep /home/aces/bin/catchup.php | grep '\-{$row_chan['id']}\-'  | grep -v 'ps -eAf'  | awk '{print $2 }' )");
                //setAjaxComplete();
            } else if(!$row_chan['catchup'] && isset($_POST['enable_catchup']) ) {
                exec("nohup php /home/aces/bin/catchup.php -{$row_chan['id']}- > /dev/null & " );
                setAjaxComplete();

            } else if($row_chan['enable_catchup']) {
                setAjaxComplete();
            }

            foreach($_POST['record'] as $i => $v ) {

                if(!empty($_POST['record_start'][$i])) {

                    $r_epg = $DB->query("SELECT title,description,start_time,end_time,start_date,end_date FROM iptv_epg 
                         WHERE start_time = '$v' AND chan_id = $stream->id ");
                    $epg = $r_epg->fetch_assoc();

                    $rs = $_POST['record_start'][$i];

                    $title = $DB->escString($epg['title']);
                    $desc = $DB->escString($epg['description']);

                    $start_date = date('Y-m-d H:i:s',$epg['start_time']);
                    $end_date = date('Y-m-d H:i:s',$epg['end_time']);

                    $start_time = $epg['start_time'];
                    $end_time = $epg['end_time'];

                    $rr=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = {$row_chan['id']} AND start = '$start_time' 
                                AND end = '$end_time' AND status != 4 ");
                    if(!$rr->fetch_assoc()) {
                        $DB->query("INSERT INTO iptv_recording (chan_id,server_id,title,description,start_time,end_time,start,end,expire_date) 
                            VALUES({$row_chan['id']},'$server->id','$title','$desc', '$start_date', '$end_date','$start_time','$end_time', NOW() + INTERVAL $exp_days DAY ) ");
                    }
                }
            }

            setAjaxComplete();

            break;

        case'move_channel' :

            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            $Stream = new \ACES2\IPTV\Stream($_POST['stream_id']);
            $Server = new \ACES2\IPTV\SERVER($Stream->server_id);
            $MoveServer = new \ACES2\IPTV\SERVER($_POST['move_to']);
            $move_to = (int)$_POST['move_to'];

            if ($Stream->type != $Stream::CHANNEL_TYPE_247)
                setAjaxError("Only Channel can be moved.");

            if ($Server->id == $move_to)
                setAjaxError("Will not move channel to same server.");

            //$r_files = $Stream->query("SELECT  id FROM iptv_channel_files WHERE channel_id = '$Stream->id' AND status = 3");

            $r_streaming = $Stream->query("SELECT id FROM iptv_streaming WHERE chan_id = $Stream->id AND status  = '" . $Stream::STATUS_MOVING . "' ");
            if ($r_streaming->num_rows)
                setAjaxError("This channel is in the process of moving. Please wait until it finished.");


            if (empty($MoveServer->ssh_password))
                setAjaxError("The ssh password need to be set.");

            $connection = ssh2_connect($MoveServer->address, 22);
            if (!ssh2_auth_password($connection, 'root', $MoveServer->ssh_password)) {
                setAjaxError("Unable to establish ssh connection. Make sure root password are set correctly.");
            }


            $is_streaming = 0;
            $Stream->stop();
            if ($Stream->getStatus() != $Stream::STATUS_STOPPED) {
                $is_streaming = 1;
            }

            $DB->query("INSERT INTO iptv_streaming (chan_id, server_id, status ) 
                            VALUES('$Stream->id','$Stream->server_id', 4 )");
            $streaming_id = $ADMIN->DB->insert_id;


            set_time_limit(0);
            ignore_user_abort();
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            //@TODO Server should return false if it fail to send action.
            if (false === $Server->send_action("move_channel", array("stream_id" => $Stream->id, "to_server" => $move_to))) {
                AcesLogE("FAIL TO MOVE STREAM #$Stream->id to server #{$move_to} ");
                $DB->query("DELETE FROM iptv_streaming WHERE id = $streaming_id ");
                break;
            }

            break;

        case 'get_server_info' :

            $server_id = (int)$_POST['server_id'];
            $Server = new \ACES2\IPTV\SERVER($server_id);
            $server = array( 'id'=> $Server->id, 'address' => $Server->address );
            setAjaxComplete($server);


//        case 'channel_order' :
//
//            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
//                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);
//
//            if(!\ACES2\Armor\Armor::isToken("iptv.channel_order", $_POST['token']))
//                setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);
//
//
//            ignore_user_abort(true);
//            set_time_limit ( 0 );
//            session_write_close();
//
//            $json['complete'] = 1 ; $json['status'] = 1;
//            echo json_encode($json);
//            fastcgi_finish_request();
//
//            if(is_file('/home/aces/run/aces.iptv.channel_order.lock')) exit;
//            touch('/home/aces/run/aces.iptv.channel_order.lock');
//
//            foreach($_POST['channels'] as $i => $id ) {
//                $i = (int)$i;
//                $id = (int)$id;
//                $number = (int)$_POST['number'][$i];
//                if(  $id  ) {
//                    $o = $i + 1;
//                    $DB->query("UPDATE iptv_channels SET ordering = '$i', number = '$number' WHERE id = '$id' ");
//                }
//            }
//            unlink('/home/aces/run/aces.iptv.channel_order.lock');
//
//            exit;

        case 'dns_replacement' :
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                throw new \ACES2\Exception(\ACES2\ERRORS::NO_PRIVILEGES);

            if(!\ACES2\Armor\Armor::isToken('iptv.stream_dns_replace', $_REQUEST['token']))
                setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

            if( empty($_POST['old_dns']) || empty($_POST['new_dns']) )
                ajaxError("Both dns are required.");


            $old_dns = $DB->escString($_POST['old_dns']);
            $new_dns = $DB->escString($_POST['new_dns']);

            $DB->query("UPDATE iptv_channels_sources SET url = replace(url,'$old_dns','$new_dns')");

            break;

        case 'stop_connection':
            $ConnectionID = (int)$_REQUEST['connection_id'];
            $DB->query("DELETE FROM iptv_access WHERE id = $ConnectionID");
            break;

        case 'stop_connections_from_stream':

            if($StreamID = (int)$_REQUEST['stream_id'])
                $DB->query("DELETE FROM iptv_access WHERE chan_id = '$StreamID'");
            else if($DeviceID = (int)$_REQUEST['device_id']) {
                $DB->query("DELETE FROM iptv_access WHERE device_id = '$DeviceID'");
            } else if($ServerID = (int)$_REQUEST['server_id']) {
                $DB->query("DELETE FROM iptv_access WHERE server_id = '$ServerID'");
            }

            break;

        case 'stop_importer':
            if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
                setAjaxError(\ACES2\Errors::NO_PERMISSIONS);

            $process_id = (int)$_REQUEST['importer_id'];
            $DB->query("DELETE FROM iptv_proccess WHERE id = $process_id");
            break;


        case 'print_message':

            if(!\ACES2\Armor\Armor::isToken('iptv.stream_fingerprint', $_REQUEST['token']))
                setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

            $account_id = (int)$_REQUEST['account_id'];
            $channel_id = (int)$_REQUEST['channel_id'];
            $font_size = (int)$_REQUEST['font_size'] ?: 36;


            if(empty($_POST['font'])) setAjaxError('Please select the font to be used.');
            else if(!is_file('/home/aces/fonts/'.$_POST['font'])) setAjaxError('The fonts do not exist.');

            else if($_POST['type'] == 'message' && empty($_POST['message']) ) setAjaxError("Please enter a message.");


                if($_POST['position'] == 'top_right') $p = 'x=w-tw-50:y=50';
                else if($_POST['position'] == 'top_left') $p = 'x=50:y=50';
                else if($_POST['position'] == 'bottom_right') $p = 'x=w-tw-50:y=h-th-50';
                else if($_POST['position'] == 'bottom_left') $p = 'x=50:y=h-th-50';
                else  $p = 'x=(w-text_w)/2: y=(h-text_h)/2';

                if(!empty($_POST['account_id']))
                    $r=$DB->query("SELECT id FROM iptv_devices WHERE id = {$_POST['account_id']} ");

                else $r=$DB->query("SELECT id FROM iptv_channels WHERE id = {$_POST['channel_id']} ");

                if(!$r->fetch_assoc()) { $json['error'] = 'Unknown Error.';  setAjaxError();  }


                if(!empty($_POST['account_id'])) $r=$DB->query("SELECT id,device_id FROM iptv_access WHERE device_id = {$_POST['account_id']} AND limit_time > NOW() ");
                else $r=$DB->query("SELECT id,device_id FROM iptv_access WHERE chan_id = {$_POST['channel_id']} AND limit_time > NOW() ");


                if(mysqli_num_rows($r) == 0 )  {
                    if(!empty($_POST['account_id'])) $json['error'] = 'Cannot print message if account is not being used.';
                    else $json['error'] = 'Cannot print message if there are no clients on channel.';
                    echo json_encode($json);exit;

                }

                ob_flush();
                flush();
                session_write_close();
                echo json_encode(array('complete' => 1, 'status' => 1));
                fastcgi_finish_request();

                while($row=$r->fetch_assoc()) {

                    if($_POST['type'] == 'id') $_POST['message'] = "ID ". $row['device_id'];
                    else $_POST['message'] = preg_replace("/[^a-zA-Z0-9\s]/", "", $_POST['message']);

                    while(true) {
                        $r2=$DB->query("SELECT id FROM iptv_access WHERE message IS NOT NULL AND limit_time > NOW() ");
                        if(mysqli_num_rows($r2) < 20 ){  break;  }
                        else { sleep(1); }
                    }

                    $m = " -vf drawtext=\"fontfile=/home/aces/fonts/{$_POST['font']}: text=\'{$_POST['message']}\': fontcolor={$_POST['font_color']}: fontsize={$_POST['font_size']}: box=1: boxcolor=black@0.7: boxborderw=7: $p \"  ";

                    $DB->query("UPDATE iptv_access SET message = '$m' WHERE id = {$row['id']} ");

                }

                exit;






            break;


    }
} catch (Exception $exp) {
    setAjaxError($exp->getMessage());
}

setAjaxComplete();