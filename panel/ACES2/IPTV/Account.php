<?php

namespace ACES2\IPTV;

class Account {

    CONST STATUS_DISABLED = 2;
    CONST STATUS_ACTIVE = 1;
    CONST STATUS_BLOCKED = 3;

    public $id = 0;
    public $name = '';
    public $username = '';
    public $password = '';

    public $package_id = 1;
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

    private $db = null;

    public function __construct(int $id = null ) {

        $this->db = new \ACES2\DB;

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

        } else
            $this->expire_on = time();

    }

    public function getExpirationDate() {
        return date("Y/m/d H:i", (int)$this->expire_on);
    }

    public function getLockedIps() {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT value FROM iptv_account_locks WHERE type = '1' AND account_id = '$this->id'");
        $ips = [];
        while($row=$r->fetch_assoc())
            $ips[] = $row['value'];
        return $ips;
    }

    public function getLockedUserAgents() {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT value FROM iptv_account_locks WHERE type = '2' AND account_id = '$this->id'");
        $ua = [];
        while($row=$r->fetch_assoc())
            $ua[] = $row['value'];
        return $ua;
    }

    public function setName(string $name) {
        if (!preg_match('/^[a-zA-Z0-9 -]+$/', $name))
            throw new \Exception("Invalid account name");
        if(strlen($name)> 15)
            throw new \Exception("Invalid account name");

        $this->name = $name;
    }

    public function setUsername($username) {
        if(empty($username)) {
            $username = substr(md5(rand(100, 999999) . rand(100, 999999)), 0, 12);
        } else if (!preg_match('/^[a-zA-Z0-9-]+$/', $username))
            throw new \Exception("Invalid characters in username.");
        if(strlen($username) < 4 || strlen($username) > 32 )
            throw new \Exception("Username can not be less than 5 characters or more 
            than 32 characters long.");

        $this->username = $username;

        $sql_id = $this->id ? " AND id != '$this->id' " : '' ;
        $r=$this->db->query("SELECT id FROM `iptv_devices` WHERE `username`='$this->username' 
                                AND `token`='$this->password' $sql_id ");
        if($r->num_rows > 0)
            throw new \Exception("There is already an account with this username and password.");


    }
    public function setPassword($password) {
        if(empty($password)) {
            $password = substr(md5(rand(100, 999999) . rand(100, 999999)), 0, 12);
        } elseif (!preg_match('/^[a-zA-Z0-9-]+$/', $password ))
            throw new \Exception("Invalid characters in password.");
        if(strlen($password) < 4 || strlen($password) > 32 )
            throw new \Exception("Password can not be less than 5 characters or more than 32 characters long.");

        $this->password = $password;

        $sql_id = $this->id ? " AND id != '$this->id' " : '' ;
        $r=$this->db->query("SELECT id FROM `iptv_devices` WHERE `username`='$this->username' 
                                AND `token`='$this->password' $sql_id ");
        if($r->num_rows > 0)
            throw new \Exception("There is already an account with this username and password.");

    }
    public function setOwner(int $owner_id) {
        $this->owner_id = $owner_id;
    }
    public function setTrial( $is_trial) { $this->is_trial = (bool)$is_trial; }
    public function setAllowAdultContent(bool $allow_adult_content){
        $this->allow_adult_content = $allow_adult_content;
    }
    public function setAdultsWithPin(bool $adults_with_pin){
        $this->adults_with_pin = $adults_with_pin;
    }
    public function setIgnoreBlockRules(bool $ignore_block_rules){
        $this->ignore_block_rules = $ignore_block_rules; }
    public function setNoM3uPlaylist(bool $no_m3u_playlist){ $this->no_m3u_playlist = $no_m3u_playlist ; }
    public function setAllowMag(bool $allow_mag){ $this->allow_mag = $allow_mag; }
    public function setAutoLockIp(bool $auto_ip_lock){ $this->auto_ip_lock = $auto_ip_lock; }
    public function setAllowXCApps(bool $allow_xc_apps){ $this->allow_xc_apps = $allow_xc_apps; }
    public function setResellerNotes(String $reseller_notes){ $this->reseller_notes = $reseller_notes; }
    public function setAdminNotes(String $admin_notes){ $this->admin_notes = $admin_notes; }
    public function setLimitConnections(int $limit_connections){ $this->limit_connections = $limit_connections; }
    public function setMacAddress(String  $mac_address){

        if(!self::isValidMacAddress($mac_address))
            throw new \Exception("Invalid MAC address.");

        $sql_id = $this->id ? " AND id != '$this->id' " : '' ;

        $r=$this->db->query("SELECT id FROM iptv_devices WHERE mag = '$mac_address' $sql_id ");
        if($r->num_rows > 0)
            throw new \Exception("There is already an account with this MAC address.");
        $this->mac_address = strtoupper($mac_address);
    }
    public function setUserAgentLocks(Array $ua_locks){

        $this->locked_user_agent = ( is_array($ua_locks) && count($ua_locks) > 0 );

        $this->db->query("DELETE FROM iptv_account_locks WHERE account_id = $this->id AND type = 2  ");
        if($this->locked_user_agent) {
            foreach ($ua_locks as $ua) {
                $this->db->query("INSERT INTO iptv_account_locks (type,account_id,value) VALUES(2,'$this->id','$ua') ");
            }
        }

    }
    public function setIpLocks(Array $ip_locks){

        $this->locked_ip = ( is_array($ip_locks) && count($ip_locks) > 0 );

        $this->db->query("DELETE FROM iptv_account_locks WHERE account_id = $this->id AND type = 1  ");
        if($this->locked_ip) {
            foreach ($ip_locks as $ip) {
                $this->db->query("INSERT INTO iptv_account_locks (type,account_id,value) VALUES(1,'$this->id','$ip') ");
            }

        }
    }
    public function setPin(String $pin){
        if (!preg_match('/^[0-9]{4}+$/', $pin))
            throw new \Exception("Pin must be a numeric value with 4 characters long.");

        $this->pin = $pin;
    }
    public function setExpireOn(String $expire_on){
        if(strtotime($expire_on)) {
            //MAKE SURE DATE FORMAT HAVE TIME.
            if (count(explode(":", $expire_on)) < 2)
                $expire_on = $expire_on . date(' H:i:s');

            $this->expire_on = strtotime($expire_on);
            return;
        }
        else $this->expire_on = time();
    }
    public function setPackageId(int $package_id){
        $this->package_id = $package_id;
        if($this->package_id) {
            $package = new BouquetPackage($this->package_id);
            $this->bouquets = $package->bouquets;
        } else {
            $this->bouquets = $_POST['bouquets'];
        }
    }
    public function setStatus(int $status){
        $this->status = $status;
    }
    public function setPackage(int $package_id) {
        if($package_id) {
            $package = new BouquetPackage($package_id);
            $this->package_id = $package->id;
            $this->bouquets = $package->bouquets;
            $this->allow_mag = $package->allow_mag;
            $this->auto_ip_lock = $package->auto_lock_ip;
            $this->hide_vods_from_playlist = $package->hide_vods_from_playlist;
            $this->allow_xc_apps = $package->allow_xc_apps;
            $this->limit_connections = $package->max_connections;
        }
    }
    public function setBouquets(Array $bouquets){
        $this->bouquets = [];
        foreach($bouquets as $bouquet) {
            if((int)$bouquet)
                $this->bouquets[] = $bouquet;
        }
    }

    public function sendMagEvent(String $event, String $message, bool $reboot_on_confirm = true ):bool {

        if(!in_array($event,array('send_msg','reload_portal','reboot','cut_off','reset_stb_lock')) )
            throw new \Exception("Unknown Event.");

        $message = $this->db->escString($message);

        if($event == 'send_msg') {
            $this->db->query("INSERT INTO iptv_mag_event (account_id,event,message,reboot_after_ok) 
                VALUES('$this->id','send_msg','$message','$reboot_on_confirm' )");
        } else {
            $r=$this->db->query("SELECT id FROM iptv_mag_event WHERE account_id = $this->id AND event = '$event'");
            if(!$r->fetch_assoc())
                $this->db->query("INSERT INTO iptv_mag_event (account_id,event,reboot_after_ok) 
                    VALUES('$this->id','$event','$reboot_on_confirm' )");
        }

       return true;
    }

    public function remove():bool {

        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_account_locks WHERE account_id = '$this->id' ");
        $db->query("DELETE FROM iptv_mag_devices WHERE account_id = $this->id ");
        $db->query("DELETE FROM iptv_devices WHERE id = $this->id");

        return true;

    }

    public function set($POST) {

        $db = new \ACES2\DB;
        $this->name = $db->escString($POST['name']);

        $this->username = trim($POST['username']);
        if(empty($this->username)) {
            //$this->username = substr(md5(rand(100, 999999) . rand(100, 999999)), 0, 12);
            $this->username = $this->genRandomString(8);
        } else if (!preg_match('/^[a-zA-Z0-9-]+$/', $this->username ))
            throw new \Exception("Invalid characters in username.");
        if(strlen($this->username) < 4 || strlen($this->username) > 32 )
            throw new \Exception("Username can not be less than 5 characters or more than 32 characters long.");


        $this->password = trim($POST['password']);
        if(empty($this->password)) {
            //$this->password = substr(md5(rand(100, 999999) . rand(100, 999999)), 0, 12);
            $this->password = $this->genRandomString(8);
        }elseif (!preg_match('/^[a-zA-Z0-9-]+$/', $this->password ))
            throw new \Exception("Invalid characters in password.");
        if(strlen($this->password) < 4 || strlen($this->password) > 32 )
            throw new \Exception("Password can not be less than 5 characters or more than 32 characters long.");


        $sql_id = $this->id ? " AND id != '$this->id' " : '' ;
        $r=$db->query("SELECT id FROM `iptv_devices` WHERE `username`='$this->username' 
                                AND `token`='$this->password' $sql_id ");
        if($r->num_rows > 0)
            throw new \Exception("There is already an account with this username and password.");


        $this->owner_id = (int)$POST['owner_id'];
        $this->status = (int)$POST['status'];
        $this->is_trial = (bool)$POST['is_trial'];
        $this->allow_adult_content = (bool)$POST['allow_adult_content'];
        $this->adults_with_pin = (bool)$POST['adults_with_pin'];
        $this->ignore_block_rules = (bool)$POST['ignore_block_rules'];
        $this->hide_vods_from_playlist = (bool)$POST['hide_vods_from_playlist'];
        $this->no_m3u_playlist = (bool)$POST['no_m3u_playlist'];
        $this->allow_mag = (bool)$POST['allow_mag'];

        $this->auto_ip_lock = (bool)$POST['auto_ip_lock'];
        $this->allow_xc_apps  = (bool)$POST['allow_xc'];

        $this->reseller_notes = $db->escString($POST['reseller_notes']);
        $this->admin_notes = $db->escString($POST['admin_notes']);
        $this->limit_connections = (int)$POST['limit_connections'];

        //$this->no_series_club 0= $POST['no_series_club'] ? 1 : 0;
        //$extra_opts = serialize($extra_opts);

        $this->locked_ip = ( is_array($POST['allowed_ip_address']) && count($POST['allowed_ip_address']) > 0 );
        $this->locked_user_agent = ( is_array($POST['allowed_user_agent']) && count($POST['allowed_user_agent']) > 0 );

        if(!empty($POST['mac_address'])) {
            $this->mac_address = trim($POST['mac_address']);
            if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $this->mac_address ))
                throw new \Exception("Invalid MAC address.");

            $r=$db->query("SELECT id FROM iptv_devices WHERE mag = '$this->mac_address' $sql_id ");
            if($r->num_rows > 0)
                throw new \Exception("There is already an account with this MAC address.");
        }


        $this->pin = '0000';
        if(!empty($POST['pin'])){
            if (!preg_match('/^[0-9]{4}+$/', $POST['pin']))
                throw new \Exception("Pin must be a numeric value with 4 characters long.");

            $this->pin = $POST['pin'];
        }

        $this->expire_on = time();
        if(strtotime($POST['expire_on'])) {
            //MAKE SURE DATE FORMAT HAVE TIME.
            if (count(explode(":", $POST['expire_on'])) < 2)
                $POST['expire_on'] = $POST['expire_on'] . date(' H:i:s');

            $this->expire_on = strtotime($POST['expire_on']);
        }

        $this->package_id = (int)$POST['package_id'];
        if($this->package_id) {
            $package = new BouquetPackage($this->package_id);
            $this->bouquets = $package->bouquets;
        } else {
            $this->bouquets = $_POST['bouquets'];
        }


    }

    public function update($POST) {

        $db = new \ACES2\DB;
        //$this->set($POST);
        $this->name = $db->escString($POST['name']);
        $this->setUsername($POST['username']);
        $this->setPassword($POST['password']);
        $this->setOwner((int)$POST['owner_id']);
        $this->setStatus($POST['status']);
        $this->setTrial($POST['is_trial']);
        $this->setAllowAdultContent((bool)$POST['allow_adult_content']);
        $this->setAdultsWithPin((bool)$POST['adults_with_pin']);
        $this->setPin($POST['pin']);
        $this->setIgnoreBlockRules((bool) $POST['ignore_block_rules']);
        $this->setNoM3uPlaylist( (bool) $POST['no_m3u_playlist']);
        $this->setAllowMag((bool) $POST['allow_mag']);
        $this->setAutoLockIp((bool) $POST['auto_ip_lock']);
        $this->setAllowXcApps((bool) $POST['allow_xc_apps']);
        $this->hide_vods_from_playlist = (bool) $POST['hide_vods_from_playlist'];
        $this->setResellerNotes($POST['reseller_notes']);
        $this->setAdminNotes($POST['admin_notes']);
        $this->setLimitConnections($POST['limit_connections']);
        $this->setExpireOn($POST['expire_on']);
        $this->allow_xc_apps = $_REQUEST['allow_xc'];

        if($POST['package_id'])
            $this->setPackageId($POST['package_id']);
        else
            $this->setBouquets((array)$POST['bouquets']);

        if(!empty($_POST['mac_address']))
            $this->setMacAddress($_POST['mac_address']);


        $this->save();

        $this->setUserAgentLocks((Array) $_POST['allowed_user_agent']);
        $this->setIpLocks((Array) $_POST['allowed_ip_address']);

        return true;

    }

    public function save() {

        $db = new \ACES2\DB;

        $no_allow_xc_apps = !$this->allow_xc_apps;
        $bouquets = serialize($this->bouquets);
        $name = $db->escString($this->name);
        $resellers_notes = $db->escString($this->reseller_notes);
        $admin_notes = $db->escString($this->admin_notes);

        $this->db->query("UPDATE `iptv_devices` SET status = '$this->status', name = '$name', username = '$this->username', token = '$this->password',
            package_id = '$this->package_id', bouquets = '$bouquets', adults = '$this->allow_adult_content', user_id = '$this->owner_id',
            demo = '$this->is_trial', limit_connections = '$this->limit_connections', adults_with_pin = '$this->adults_with_pin', pin = '$this->pin',
            mag = '$this->mac_address', subcription = FROM_UNIXTIME('$this->expire_on'), ignore_block_rules = '$this->ignore_block_rules', 
            reseller_notes = '$resellers_notes', admin_notes = '$admin_notes', auto_ip_lock = '$this->auto_ip_lock', 
            hide_vods_from_playlist = '$this->hide_vods_from_playlist', no_m3u_playlist = '$this->no_m3u_playlist', allow_mag = '$this->allow_mag',
            adults_with_pin = '$this->adults_with_pin', no_allow_xc_apps = '$no_allow_xc_apps', lock_ip_address = '$this->locked_ip',
            lock_user_agent = '$this->locked_user_agent' WHERE id = '$this->id' ");
    }

    static public function add($POST):self {

        $account = new self();
        $account->set($POST);

        $bouquets = serialize($account->bouquets);
        $no_allow_xc_apps = !$account->allow_xc_apps;

        $db = new \ACES2\DB;

        $resellers_notes = $db->escString($account->reseller_notes);
        $admin_notes = $db->escString($account->admin_notes);


        $db->query("INSERT INTO iptv_devices (name,status,package_id,bouquets,demo,user_id,limit_connections,adults,
                          adults_with_pin,pin,username,token,mag,lock_ip_address,lock_user_agent,add_date,subcription,
                          ignore_block_rules,reseller_notes,admin_notes,auto_ip_lock,hide_vods_from_playlist,no_m3u_playlist,
                          allow_mag,no_allow_xc_apps ) 

                        VALUES('$account->name',$account->status,$account->package_id,'{$bouquets}',0,'$account->owner_id',
                               '$account->limit_connections','$account->allow_adult_content','$account->adults_with_pin','$account->pin',
                               '$account->username','$account->password','$account->mac_address','$lock_ip','$lock_user_agent',
                               NOW(), FROM_UNIXTIME('$account->expire_on') ,'$account->ignore_block_rules','$resellers_notes',
                               '$admin_notes','$account->auto_ip_lock','$account->hide_vods_from_playlist','$account->no_m3u_playlist',
                               '$account->allow_mag', '$no_allow_xc_apps' ) ");


        return new Account($db->insert_id);

    }

    public function setAddByAdmin(int $admin_id ) {
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_devices SET add_by_admin = $admin_id WHERE id = $this->id ");
    }

    public function setAddByUser(int $user_id ) {
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_devices SET add_by_user = '$user_id' WHERE id = '$this->id'");
    }


    static public function isValidMacAddress(string $mac_address):bool {
        if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac_address ))
            return true;

        return false;
    }

    private function genRandomString(int $length = 8):string {

        $chars = '23456789abcdefghjklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
        $string = '';

        for($i = 0; $i < $length; $i++) {
            $index = random_int(0, strlen($chars) - 1);
            $string .= $chars[$index];
        }

        return $string ;

    }

}