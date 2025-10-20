<?php

$db = new ACES2\db;
if(!$AdminID=adminIsLogged(false)) {
    setAjaxError("",401);
}

switch($_REQUEST['action']) {

    case 'remove_notification':

        $notificationID = (int)$_REQUEST['notification_id'];
        $db->query("UPDATE admin_notifications SET hidden = 1
                           WHERE id = $notificationID AND admin_id = '$AdminID' ");

        //$db->query("DELETE FROM admin_notifications WHERE id = $notificationID AND admin_id = '$AdminID'");

        break;

    case 'get_notifications':

        $Cache = new Cache('server-notifications', 60 * 60);
        if($Cache->isExpired()) {

            $mid = trim(file_get_contents('/home/aces/machine-id'));
            $nots=json_decode(file_get_contents("https://acescript.ddns.net/v2/nots.php?mid=$mid"),true);

            foreach($nots as $n) {
                $r=$db->query("SELECT id FROM admin_notifications WHERE type = '{$n['type']}' AND time = '{$n['create_time']}' 
                                     AND admin_id = '$AdminID' ");
                if($r->num_rows < 1 )
                    $db->query("INSERT INTO admin_notifications (admin_id, type, message , priority, time) 
        VALUES($AdminID, '{$n['type']}', '{$n['message']}', '{$n['priority']}','{$n['create_time']}' )");
            }
            $Cache->saveit(1);
        }

        $notifications = [];
        $new_notifications = [];
        $r=$db->query("SELECT id,time,message,priority,sent,type FROM admin_notifications 
                                               WHERE admin_id = '$AdminID' AND hidden = 0 ");
        while($row=$r->fetch_assoc()) {

            $row['icon'] =  match ($row['type']) {
                'update' => 'fa-upload',
                default => 'fa-message',
            };

            if(!$row['sent'])
                $new_notifications[] = $row['message'];

            $row['date'] = DateBeautyPrint::shortPrint($row['time']);


            $notifications[] = $row;
        }

        echo json_encode(
            array(
                'notifications' => $notifications,
                'new_notifications' => $new_notifications
            )
        );

        $db->query("UPDATE admin_notifications SET sent = 1 WHERE admin_id = '$AdminID' ");

        exit;

}

setAjaxComplete();