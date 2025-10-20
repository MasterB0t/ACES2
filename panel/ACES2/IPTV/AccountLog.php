<?php

namespace ACES2\IPTV;

class AccountLog {

    CONST TYPE_PLAY_STREAM = 0;
    CONST TYPE_PLAY_VIDEO = 1;

    static public function addLog(int $account_id,
                                  int $type, int $stream_or_video_id,
                                  string $user_agent = '', string $ip_address = '' ):void {
        $db = new \ACES2\DB;
        $db->query("INSERT INTO iptv_account_logs (account_id, type, stream_id, ip_address, user_agent, log_date) 
            VALUES('$account_id', '$type', '$stream_or_video_id', '$ip_address', '$user_agent', NOW() ) ");
    }

}