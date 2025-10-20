<?php

namespace ACES2\IPTV;

class Account2 {

    CONST STATUS_DISABLED = 2;
    CONST STATUS_ACTIVE = 1;
    CONST STATUS_BLOCKED = 3;

    public $id = 0;
    public $name = '';
    public $username = '';
    public $password = '';

    public $package_id = 0;
    public $mac_address = '';
    public $owner_id = 0;
    public $is_trial = false;
    public $status = 1;
    public $pin='0000';
    public $expire_on = 0;
    public $limit_connections = 1;
    public $auto_ip_lock = false;
    public $hide_vods_from_playlist = false;
    public $no_m3u_playlist = true;
    public $allow_mag = true;
    public $allow_xc_apps = true;
    public $ignore_block_rules = false;
    public $adults_with_pin=false;
    public $allow_adult_content=false;
    public $admin_notes='';
    public $locked_ip = false;
    public $locked_user_agent = false;
    public $reseller_notes;
    public $bouquets = [];

    public function __construct(int $id = null ) {

        if(!is_null($id)) {
            $db = new \ACES2\DB;
            $r=$db->query("SELECT * FROM `iptv_devices` WHERE `id`='$id' ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Account #$id could not be found on database.");

            $this->id = (int) $row['id'];
            $this->name = $row['name'];
            $this->username = $row['username'];
            $this->password = $row['token'];
            $this->mac_address = $row['mag'];
            $this->limit_connections = (int)$row['limit_connections'];
            $this->owner_id = (int)$row['user_id'];
            $this->package_id = (int)$row['package_id'];
            $this->allow_adult_content = (bool)$row['adults'];
            $this->adults_with_pin = (bool)$row['adults_with_pin'];

            $this->allow_mag = (bool)$row['allow_mag'];
            $this->auto_ip_lock = (bool)$row['auto_ip_lock'];
            $this->admin_notes = $row['admin_notes'];
            $this->reseller_notes = $row['reseller_notes'];
            $this->allow_xc_apps = (bool)!$row['no_allow_xc_apps'];
            $this->expire_on = (int)strtotime($row['subcription']);
            $this->bouquets = unserialize($row['bouquets']);
            $this->pin = $row['pin'];
            $this->status = (int)$row['status'];
            $this->no_m3u_playlist = (bool)$row['no_m3u_playlist'];
            $this->hide_vods_from_playlist = (bool)$row['hide_vods_from_playlist'];
            $this->ignore_block_rules = (bool)$row['ignore_block_rules'];
            return ;

        } else
            $this->expire_on = time();

    }


    public static function add(string $name, string $username, string $password ) {

    }

}