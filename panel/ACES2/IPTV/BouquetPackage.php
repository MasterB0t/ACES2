<?php

namespace ACES2\IPTV;

class BouquetPackage {

    public $id = 0;
    public $name = "";
    public $max_connections = 0;
    public $bouquets = array();
    public $official_credits = 0;
    public $trial_credits = 0;
    public $official_duration = 0;
    public $trial_duration = '';
    public $official_duration_in = '';
    public $trial_duration_in = 0;
    public $auto_lock_ip = false;
    public $no_m3u_playlist = false;
    public $hide_vods_from_playlist = false;
    public $allow_mag = true;
    public $allow_xc_apps = true;


    public function __construct(int $id = null) {
        $db = new \ACES2\DB;
        if(!is_null($id)) {
            $r = $db->query("SELECT * FROM iptv_bouquet_packages WHERE id = '$id' ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Bouquet package #$id not found in database.");

            $this->id = $id;
            $this->name = $row['name'];
            $this->bouquets = unserialize($row['bouquets']);
            $this->max_connections = (int)$row['max_connections'];
            $this->official_duration = $row['official_duration'];
            $this->trial_duration = $row['trial_duration'];
            $this->official_duration_in = $row['official_duration_in'];
            $this->trial_duration_in = $row['trial_duration_in'];
            $this->trial_credits = $row['trial_credits'];
            $this->official_credits = $row['official_credits'];

            $this->auto_lock_ip = (bool)$row['auto_ip_lock'];
            $this->hide_vods_from_playlist = (bool)$row['hide_vods_from_playlist'];
            $this->allow_mag = (bool)$row['can_gen_mag'];
            $this->no_m3u_playlist = (bool)$row['no_m3u_playlist'];
            $this->allow_xc_apps = (bool)$row['allow_xc_apps'];

        }
    }

    public function getOfficialExpireInDate() {

        $DB = new \ACES2\DB;

        $r=$DB->query(" SELECT  "
            . " CASE `official_duration_in` "
            . " WHEN 'HOUR' THEN  DATE_FORMAT(NOW() + INTERVAL official_duration HOUR, '%Y/%m/%d %H:%i:%s' )  "
            . " WHEN 'DAY' THEN DATE_FORMAT(NOW() + INTERVAL official_duration DAY, '%Y/%m/%d %H:%i:%s' )  "
            . " WHEN 'MONTH' THEN DATE_FORMAT(NOW() + INTERVAL official_duration MONTH, '%Y/%m/%d %H:%i:%s' ) "
            . " WHEN 'YEAR' THEN  DATE_FORMAT(NOW() + INTERVAL official_duration YEAR, '%Y/%m/%d %H:%i:%s' )  "
            . " END as expire_in "
            . " FROM iptv_bouquet_packages WHERE id = '$this->id' ");

        return $r->fetch_assoc()['expire_in'];

    }

    public function getTrialExpireInDate() {

        $DB = new \ACES2\DB;
        $r=$DB->query(" SELECT  "
            . " CASE `trial_duration_in` "
            . " WHEN 'HOUR' THEN  DATE_FORMAT(NOW() + INTERVAL trial_duration HOUR, '%Y/%m/%d %H:%i:%s' )  "
            . " WHEN 'DAY' THEN DATE_FORMAT(NOW() + INTERVAL trial_duration DAY, '%Y/%m/%d %H:%i:%s' )  "
            . " WHEN 'MONTH' THEN DATE_FORMAT(NOW() + INTERVAL trial_duration MONTH, '%Y/%m/%d %H:%i:%s' ) "
            . " WHEN 'YEAR' THEN  DATE_FORMAT(NOW() + INTERVAL trial_duration YEAR, '%Y/%m/%d %H:%i:%s' )  "
            . " END as expire_in "
            . " FROM iptv_bouquet_packages WHERE id = '$this->id' ");

        return  $r->fetch_assoc()['expire_in'];

    }

    private function set($POST) {
        $db = new \ACES2\DB;
        $this->name = $db->escString($POST['name']);
        if(!$this->name)
            throw new \Exception("Name is required.");

        $this->max_connections = (int)$POST['max_connections'];
        $this->official_duration = (int)$POST['official_duration'];
        $this->trial_duration = (int)$POST['trial_duration'];

        $duration_in_values = array('HOUR','DAY','MONTH','YEAR');
        $this->official_duration_in = strtoupper($POST['official_duration_in']);
        $this->trial_duration_in = strtoupper($POST['trial_duration_in']);

        if(!in_array($this->official_duration_in, $duration_in_values))
            throw new \Exception("Invalid option '$this->official_duration_in' for official duration ");

        if(!in_array($this->trial_duration_in, $duration_in_values))
            throw new \Exception("Invalid option '$this->trial_duration_in' for trial duration");

        $this->official_credits = (int)$POST['official_credits'];
        $this->trial_credits = (int)$POST['trial_credits'];

        foreach($POST['bouquets'] as $bouquet) {
            if((int)$bouquet)
                $this->bouquets[] = (int)$bouquet;
        }

        $this->auto_lock_ip = (bool)$POST['auto_lock_ip'];
        $this->allow_mag = (bool)$POST['allow_mag'];
        $this->hide_vods_from_playlist = (bool)$POST['hide_vods_from_playlist'];
        $this->no_m3u_playlist = (bool)$POST['no_m3u_playlist'];
        $this->allow_xc_apps = (bool)$POST['allow_xc_apps'];


    }

    public function update($POST) {

        $this->set($POST);
        $db = new \ACES2\DB;

        $bouquets = serialize($this->bouquets);
        $db->query("UPDATE iptv_bouquet_packages SET name='$this->name', max_connections='$this->max_connections', 
                                 official_duration = '$this->official_duration', trial_duration = '$this->trial_duration',
                                 official_duration_in = '$this->official_duration_in', trial_duration_in = '$this->trial_duration_in',
                                 official_credits = '$this->official_credits', trial_credits = '$this->trial_credits',
                                 auto_ip_lock = '$this->auto_lock_ip', can_gen_mag = '$this->allow_mag', no_m3u_playlist = '$this->no_m3u_playlist',
                                 hide_vods_from_playlist = '$this->hide_vods_from_playlist', bouquets = '$bouquets', allow_xc_apps = '$this->allow_xc_apps'
                             
                             
                             WHERE id = '$this->id' ");

    }

    public function remove() {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_bouquet_packages WHERE id = '$this->id' ");
    }

    public static function add($POST):self {

        $db = new \ACES2\DB;

        $pkg = new self();
        $pkg->set($POST);

        $bouquets = serialize($pkg->bouquets);

        $db->query("INSERT INTO iptv_bouquet_packages (name,bouquets,max_connections,official_duration,trial_duration,
                                   official_duration_in,trial_duration_in,official_credits,trial_credits,auto_ip_lock,
                                   no_m3u_playlist,hide_vods_from_playlist,can_gen_mag,allow_xc_apps) 
        VALUES('$pkg->name','$bouquets','$pkg->max_connections','$pkg->official_duration','$pkg->trial_duration',
               '$pkg->official_duration_in','$pkg->trial_duration_in',$pkg->official_credits,$pkg->trial_credits,
               '$pkg->auto_lock_ip','$pkg->no_m3u_playlist','$pkg->hide_vods_from_playlist','$pkg->allow_mag','$pkg->allow_xc_apps'  ) ");

        $pkg->id = $db->insert_id;

        return new $pkg;

    }

}