<?php

namespace ACES2\IPTV;

class ChannelEpg {

    private $TVGID = '';
    private $chan_id = 0;

    public function __construct($channel_id) {

        if($channel_id !== false) {
            $db = new \ACES2\DB();
            $r = $db->query("SELECT id,tvg_id FROM iptv_channels WHERE id = $channel_id ");
            $row = $r->fetch_assoc();
            $this->TVGID=$row['tvg_id'];
            $this->chan_id = $row['id'];

        }

    }

    public function getEpg($limit = 4) {

        if(!$this->TVGID) {
            logD("No tvg id is set for this channel.");
            return [];
        }

        $db = new \ACES2\DB();

//        $r_epg = $this->query("SELECT start_time,end_time, FROM_UNIXTIME( start_time, '%H:%i' ) as start_date, FROM_UNIXTIME(end_time, '%H:%i') as  end_date, title,description
//                            FROM iptv_epg WHERE tvg_id = '$this->TVGID' AND tvg_id != '' AND NOW() < end_date GROUP BY start_date LIMIT $limit");


        $r_epg = $db->query("SELECT start_time,end_time, FROM_UNIXTIME( start_time, '%H:%i' ) as start_date, FROM_UNIXTIME(end_time, '%H:%i') as  end_date, title,description 
                            FROM iptv_epg WHERE chan_id = '$this->chan_id'  AND NOW() < end_date GROUP BY start_date LIMIT $limit");

        return $r_epg->fetch_all(MYSQLI_ASSOC);

    }

}