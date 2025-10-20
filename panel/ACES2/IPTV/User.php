<?php

namespace ACES2\IPTV;

class User extends \ACES2\User {

    public $can_add_reseller = false;
    public $can_override_package = false;

    public function __construct(int $user_id) {

        parent::__construct($user_id);

        if(!is_null($this->id)) {
            $db = new \ACES2\DB;
            $r=$db->query("SELECT * FROM iptv_user_info WHERE user_id='$this->id' ");
            $row=$r->fetch_assoc();
            $this->can_add_reseller = !(bool)$row['cant_add_reseller'];
            $this->can_override_package = (bool)$row['can_override_package'];
        }


    }


    public function getResellers() {

        $u_id = [];

        $db = new \ACES2\DB;

        $next_u = " ( ".$this->id." ) ";
        while(true) {

            $r_inf=$db->query("SELECT user_id FROM iptv_user_info WHERE user_owner in $next_u "); $next_u = array();
            while($rerow = $r_inf->fetch_assoc()  ) {
                if($rerow['user_id']) { $u_id[] = $rerow['user_id']; $next_u[] = $rerow['user_id']; }
            }
            if(count($next_u) < 1 ) break;

            $next_u = "(".implode(",",$next_u).")";

        }

        return $u_id;

    }

    public function getCredits():int {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_user_info WHERE user_id = '$this->id'");
        return (int)$r->fetch_assoc()['credits'];
    }

    public function setCredits(int $credits):bool {
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_user_info SET credits='$credits' WHERE user_id = '$this->id'");
        return true;
    }

    public function getOverridePackage() {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT override_packages FROM iptv_user_info WHERE user_id = '$this->id'");
        return json_decode($r->fetch_assoc()['override_packages'],1);
    }

    public function canManageAccount(int $account_id):bool{
        $db = new \ACES2\DB();
        $SubResellers = $this->getResellers();
        $SubResellers[] = $this->id;
        $sql = implode(',',$SubResellers);
        $r=$db->query("SELECT id FROM iptv_devices WHERE user_id in ($sql) AND id = '$account_id' ");
        return !($r->num_rows < 1);
    }

}