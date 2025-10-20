<?php

$db = new \ACES2\DB;

if(!$UserID=userIsLogged())
    setAjaxError('',401);

switch($_REQUEST['action']) {

    case 'remove_notification':

        $notificationID = (int)$_REQUEST['notification_id'];
        $db->query("UPDATE user_notifications SET hidden = 1
                           WHERE id = $notificationID AND user_id = '$UserID' ");

        //$db->query("DELETE FROM admin_notifications WHERE id = $notificationID AND admin_id = '$AdminID'");

        break;

    case 'get_notifications':

        $notifications = [];
        $new_notifications = [];
        $r=$db->query("SELECT id,time,message,priority,sent,type FROM user_notifications 
                                               WHERE user_id = '$UserID' AND hidden = 0 ");
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

        $db->query("UPDATE user_notifications SET sent = 1 WHERE user_id = '$UserID' ");


        exit;


}

setAjaxComplete();