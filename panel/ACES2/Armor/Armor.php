<?php

namespace ACES2\Armor;

use function ACES\Armor\logD;


class Armor {

    static public function createToken( $name,  $reset_time = 60 * 5,
                                        $max_requests = 60 ) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT ip FROM armor__bans WHERE ip = '{$_SERVER['REMOTE_ADDR']}' and type = '$name' 
                             AND expiration_time > UNIX_TIMESTAMP()");
        if($r->num_rows > 0 ) {
            sleep(2);
            return '';
        }

//        if(session_status() == PHP_SESSION_NONE)
//            session_start();
//
//        $session_id = session_id();

        $name = strtoupper($name);

        if($max_requests) {
            $r=$db->query("SELECT ip_address FROM armor__tokens WHERE name = '$name'
                                       AND expire_time > UNIX_TIMESTAMP() AND ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
            if($max_requests < $r->num_rows) {
                logD("Action: Will not create action '$name' for this ip.");
                sleep(2);
                return '';
            }
        }

        $token = md5(uniqid(microtime(), true));

        //TODO MOVE THIS TO CRON
        $db->query("DELETE FROM armor__tokens WHERE expire_time < UNIX_TIMESTAMP()");

        $db->query("INSERT INTO armor__tokens (ip_address, name, token, expire_time) 
                            VALUES ('{$_SERVER['REMOTE_ADDR']}', '$name', '$token', UNIX_TIMESTAMP() + '$reset_time' )");

        return $token;
    }

    static public function isToken(string $name, string $token ):bool {

        if(!$token)
            return false;

//        if(session_status() == PHP_SESSION_NONE)
//            session_start();
//
//        $session_id = session_id();

        $name = strtoupper($name);

        if(!preg_match('/^[a-zA-Z0-9-]{32}+$/', $token ))
            return false;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT expire_time FROM armor__tokens WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' 
                                        AND name = '$name' AND token = '$token' AND expire_time > UNIX_TIMESTAMP() ");

        if($r->num_rows<1)
            return false;

        return true;
    }


    static public function log_ban($name)  {

        $name = strtoupper($name);
        if(is_file("/home/aces/no_armor"))
            return false;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT count_logs,max_logs,ban_time FROM armor__log_ban WHERE ip = '{$_SERVER['REMOTE_ADDR']}' 
                                AND type = '$name' AND expire_time > UNIX_TIMESTAMP() ");

        if(!$row=$r->fetch_assoc() ) {
            include  "/home/aces/panel/includes/armor/" . $name . ".php";

            $max_count = $__ARMOR['max_logs'] ? $__ARMOR['max_logs'] : 25;
            $reset_time = $__ARMOR['reset_time'] ? $__ARMOR['reset_time'] : 60 * 60 ;
            $ban_time = $__ARMOR['ban_time'] ? $__ARMOR['ban_time'] : 60 * 60 * 24;

            $db->query("INSERT INTO armor__log_ban (ip, type, count_logs, max_logs, expire_time, ban_time) 
                            VALUES( '{$_SERVER['REMOTE_ADDR']}', '$name', 1, '$max_count', UNIX_TIMESTAMP() + $reset_time, $ban_time )");

        } else {

            //LET BAN IT.
            if($row['count_logs'] >= $row['max_logs']) {
                $db->query("INSERT INTO armor__bans (ip, type, expiration_time, add_time) 
                            VALUES ('{$_SERVER['REMOTE_ADDR']}', '$name', UNIX_TIMESTAMP() + {$row['ban_time']}, UNIX_TIMESTAMP())");

                $db->query("DELETE FROM armor__log_ban WHERE ip = '{$_SERVER['REMOTE_ADDR']}' 
                                AND type = '$name' ");

                return true;
            }

            $db->query("UPDATE armor__log_ban SET count_logs = count_logs + 1 WHERE ip = '{$_SERVER['REMOTE_ADDR']}' 
                                AND type = '$name' AND expire_time > UNIX_TIMESTAMP() ");
        }

        return true;

    }

    static public function isBan($name):bool {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT ip FROM armor__bans WHERE ip = '{$_SERVER['REMOTE_ADDR']}' and type = '$name' 
                             AND expiration_time > UNIX_TIMESTAMP()");
        if($r->num_rows>0)
            return true;

        return false;

    }

}

//$licfile = '/home/aces/lics/IPTV.txt';
//$mid = trim(file_get_contents('/home/aces/machine-id'));
//$h_mid = hash('md5', $mid);
//$filectime = date('Y-m-d',filemtime($licfile));
//$ftime = strtotime($filectime) + ( 60 * 60 * 24 * 8 );
//$UUID = trim(shell_exec('findmnt -n -o UUID $(stat -c \'%m\' "/")'));
//$hash_hd = hash('sha512', $UUID.'IPTV');
//$hash = hash('sha512', $hash_hd.strrev($filectime). $h_mid  ) . "==";
//${$hash} = 'hello there';
//$lic = trim(file_get_contents($licfile));








