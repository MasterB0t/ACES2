<?php

namespace ACES2\IPTV;

class Blacklist {

    const TYPE_IP = 1;
    const TYPE_USER_AGENT = 2;
    const TYPE_IP_ORG = 3;

    static function addIpBlacklist($ip_address):bool {
        if(!filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
            AcesLogE("Cannot blacklist '$ip_address' is not a valid IP address");
            return false;
        }

        $db = new \ACES2\DB;
        $type = self::TYPE_IP;
        $r=$db->query("SELECT id FROM iptv_blocks WHERE ip_address = '$ip_address' AND type = '$type'");
        if(!$r->fetch_assoc()) {

            $db->query("INSERT INTO iptv_blocks (ip_address, type) VALUES ('$ip_address', '$type')");

            //TODO
//            $db->query(" UPDATE iptv_devices d  LEFT JOIN iptv_ip_info ip ON ip.ip_address = d.ip_address
//     INNER JOIN iptv_blocks b on ( b.value = d.ip_address AND b.type = 1 OR d.ip_address = ip.ip_address AND LOWER(ip.org)
//                                                                                                                 LIKE lower(CONCAT('%',b.value,'%')) OR lower(d.user_agent) LIKE lower(concat('%',b.value,'%')) AND b.type = 2 )
//        SET d.tmp_block = 1 WHERE d.security_level > 0  ");

            //RESET CACHE FORM DEVICE
            $db->query("UPDATE iptv_devices set tmp_block = 0");

            //TODO
//            $db->query(" DELETE FROM iptv_access WHERE id IN ( SELECT * FROM (  SELECT a.id FROM iptv_access a INNER JOIN iptv_devices d on d.id = a.device_id AND d.security_level > 0
//                JOIN iptv_ip_info ip ON ip.ip_address = d.ip_address  INNER JOIN iptv_blocks b on ( b.value = d.ip_address AND b.type = 1 OR d.ip_address = ip.ip_address AND LOWER(ip.org) LIKE lower(CONCAT('%',b.value,'%')) OR lower(d.user_agent) LIKE lower(concat('%',b.value,'%')) AND b.type = 2 )  ) as p )  ");
        }


        return true;
    }

    static public function addUserAgentBlacklist($user_agent) {
        $db = new \ACES\DB;
        $type = self::TYPE_USER_AGENT;
        $user_agent = $db->escString(strtoupper($user_agent));
        $r = $db->query("SELECT id FROM iptv_blocks WHERE user_agent = '$user_agent' AND type = '$type'");
        if(!$r->fetch_assoc()) {
            $db->query("INSERT INTO iptv_blocks (user_agent, type) VALUES ('$user_agent', '$type')");

            $db->query("UPDATE iptv_devices set tmp_block = 0");

            //TODO
            //DELETING CONNECTION WITH USER AGENT BLOCKED.
            //$db->query(" DELETE FROM iptv_access WHERE id IN ( SELECT * FROM (  SELECT a.id FROM iptv_access a INNER JOIN iptv_devices d on d.id = a.device_id AND d.security_level > 0  JOIN iptv_ip_info ip ON ip.ip_address = d.ip_address  INNER JOIN iptv_blocks b on ( b.value = d.ip_address AND b.type = 1 OR d.ip_address = ip.ip_address AND LOWER(ip.org) LIKE lower(CONCAT('%',b.value,'%')) OR lower(d.user_agent) LIKE lower(concat('%',b.value,'%')) AND b.type = 2 )  ) as p )  ");

            return true;
        }

        return false;
    }

}