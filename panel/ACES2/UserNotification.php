<?php

namespace ACES2;

class UserNotification {

    const PRIORITY_LOW = 0;
    const PRIORITY_NORMAL = 1;
    const PRIORITY_HIGH = 2;

    public static function sendNotification(

        int $user_id,
        string $type,
        string $message,
        int $priority = 0,
        string $link = '' ):bool {

        $db = new \ACES2\DB;
        $db->query("INSERT INTO user_notifications (user_id, type,  priority, message, time ) 
            VALUES ('$user_id', '$type',  '$priority', '$message', UNIX_TIMESTAMP() )");


        return true;


    }

}