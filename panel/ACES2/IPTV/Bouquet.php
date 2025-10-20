<?php

namespace ACES2\IPTV;

class Bouquet {

    public int $id = 0;
    public string $name = '';


    public function __construct(int $id = 0) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM `iptv_bouquets` WHERE id= '$id' ");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Bouquet #$id not found in database.");

        $this->id=(int)$row['id'];
        $this->name=$row['name'];

    }

    public function update(string $name):bool {
        $db = new \ACES2\DB;
        $name = $db->escString($name);
        $db->query("UPDATE iptv_bouquets SET name = '$name' WHERE id = '$this->id'");
        return true;
    }
    public function addStreams( $streams):bool {

        $db = new \ACES2\DB;

        $db->query("DELETE FROM iptv_channels_in_bouquet WHERE bouquet_id = '$this->id'");

        if(is_array($streams))
        foreach($streams as $stream) {
            if((int)$stream>0)
                $db->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id, chan_id) 
                    VALUES('$this->id', '$stream')");

        }

        return true;

    }

    public function addVods($vods):bool {
        $db = new \ACES2\DB;

        $db->query("DELETE FROM iptv_ondemand_in_bouquet WHERE bouquet_id = '$this->id'");

        if(is_array($vods))
        foreach($vods as $vod) {
            if((int)$vod>0)
                $db->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id, video_id) 
                    VALUES('$this->id', '$vod')");

        }

        return true;
    }

    public function remove() {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_channels_in_bouquet WHERE bouquet_id = '$this->id'");
        $db->query("DELETE FROM iptv_bouquets WHERE id = '$this->id'");
    }

    public static function add(string $name):self {
        $db = new \ACES2\DB;
        $name = $db->escString($name);
        $r=$db->query("SELECT id FROM iptv_bouquets WHERE name = '$name'");
        if($r->num_rows>0)
            throw new \Exception("A Bouquet $name already exists.");
        $db->query("INSERT INTO `iptv_bouquets` (`name`) VALUES ( '$name')");
        return new self($db->insert_id);
    }

}