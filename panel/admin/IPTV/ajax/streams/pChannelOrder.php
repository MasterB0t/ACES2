<?php

if(!adminIsLogged(false)) {
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
}

$ADMIN = new \ACES2\Admin();
if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {

    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}

$DB = new \ACES2\DB;
$json['status'] = 1 ;

switch($_POST['action']) {

    case 'get_progress':

        $json['is_running'] = false;
        if(is_file('/home/aces/run/aces.iptv.channel_order.lock')) {
            $json['is_running'] = true;
            $json['progress'] = file_get_contents('/home/aces/run/aces.iptv.channel_order.lock');
            $json['status'] = 1;
        }
        break;
    case 'order_channels':
        if(!\ACES2\Armor\Armor::isToken("iptv.channel_order", $_POST['token']))
            setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

        ignore_user_abort(true);
        set_time_limit ( 0 );
        session_write_close();

        $json['complete'] = 1 ; $json['status'] = 1;
        echo json_encode($json);
        fastcgi_finish_request();

        if(is_file('/home/aces/run/aces.iptv.channel_order.lock')) exit;
        //touch('/home/aces/run/aces.iptv.channel_order.lock');
        file_put_contents('/home/aces/run/aces.iptv.channel_order.lock', 0);

        $ProgressCount = 0; $set_progress=0;
        foreach($_POST['channels'] as $i => $id ) {
            $i = (int)$i;
            $id = (int)$id;
            $number = (int)$_POST['number'][$i];
            if(  $id  ) {
                $o = $i + 1;
                $DB->query("UPDATE iptv_channels SET ordering = '$i', number = '$number' WHERE id = '$id' ");
            }

            $set_progress++;$ProgressCount++;
            if($set_progress > 1 ) {
                $p = (int) $ProgressCount / count($_POST['channels']) * 100;
                file_put_contents('/home/aces/run/aces.iptv.channel_order.lock', $p);
            }

        }
        unlink('/home/aces/run/aces.iptv.channel_order.lock');

        break;



}

setAjaxComplete($json);
