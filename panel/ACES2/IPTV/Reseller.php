<?php

namespace ACES2\IPTV;

use ACES2\DB;

class Reseller extends \ACES2\User {

    public $credits = 0;
    public $reseller_of = 0;
    public $can_add_resellers = false;
    public $allow_channel_list = false;
    public $allow_vod_list = false;
    public $can_override_package = false;
    public $override_packages = array();

    public function __construct(int $id ) {

        parent::__construct($id);

        $db = new DB();
        $r=$db->query("SELECT * from iptv_user_info WHERE user_id = '$this->id' ");
        if($row=$r->fetch_assoc()){
            $this->credits = $row['credits'];
            $this->reseller_of = $row['user_owner'];
            $this->can_add_resellers = !(bool)$row['cant_add_reseller'];
            $this->allow_vod_list = (bool)$row['allow_vod_list'];
            $this->allow_channel_list = (bool)$row['allow_channel_list'];
            $this->can_override_package = (bool)$row['can_override_package'];


            $this->override_packages =  json_decode($row['override_packages'],1);
            return;

        }

    }

    protected function set($POST):bool{
        parent::set($POST);

        $db = new DB();

        $this->credits = (int)$POST['credits'];

        $sql_id = $this->id ? " AND id != '$this->id' " : " ";

        if($POST['reseller_of']) {
            $this->reseller_of = (int)$POST['reseller_of'];
            $r=$db->query("SELECT id FROM users WHERE id = $this->reseller_of $sql_id ");
            if(!$r->fetch_assoc())
                throw new \Exception("Reseller #$this->reseller_of  not exist.");
        }

        $this->can_add_resellers = (bool)$POST['can_add_resellers'];
        $this->allow_vod_list = (bool)$POST['allow_vod_list'];
        $this->allow_channel_list = (bool)$POST['allow_channel_list'];
        $this->can_override_package = (bool)$POST['can_override_package'];


        return true;

    }

    public function setOverridePackage($Official_Credits, $Trial_Credits, $Enabled ):bool {

        $this->override_packages = [];
        foreach ($Official_Credits as $id => $val) {

            if($val == '' )
                $this->override_packages[$id]['official_credits'] = '';
            else
                $this->override_packages[$id]['official_credits'] = (int)$val;


            if($Trial_Credits[$id] == '' )
                $this->override_packages[$id]['trial_credits']  = '';
            else
                $this->override_packages[$id]['trial_credits']  = (int)$Trial_Credits['trial_credits'][$id];

            if( $Enabled[$id] == 0 )
                $this->override_packages[$id]['disabled'] = 1;

        }


        $override_package = json_encode($this->override_packages);

        $db = new DB();
        $db->query("UPDATE iptv_user_info SET override_packages = '$override_package' 
                      WHERE user_id = '$this->id'");

        return true;
    }

    public function remove( $move_account_to_user = null, $move_resellers_to_user = null ):bool {

        $db = new DB();

        //parent::remove();

        if($move_account_to_user == 'remove' || $move_account_to_user == 'delete') {
            $db->query("DELETE FROM iptv_devices WHERE user_id = '$this->id' ");
            //TODO USE \ACES2\IPTV\ACCOUNT->remove() INSTEAD
        } else {
            $new_user = (int)$move_account_to_user;
            $db->query("UPDATE iptv_devices SET user_id = $move_account_to_user WHERE user_id = '$this->id' ");
        }

        if($move_resellers_to_user == 'remove' ) { AcesLogD("Removing $move_resellers_to_user");

            $db->query("DELETE FROM iptv_user_info WHERE user_id = $move_resellers_to_user ");
            $db->query("DELETE FROM users WHERE id = $move_resellers_to_user ");

        } else {

            $move_resellers_to_user = (int)$move_resellers_to_user;
            if($move_resellers_to_user == $this->id )
                $move_resellers_to_user = 0;

            $db->query("UPDATE iptv_user_info SET user_owner = $move_resellers_to_user WHERE user_owner = $this->id ");

        }

        $db->query("DELETE FROM iptv_user_info WHERE user_id = $this->id ");
        $db->query("DELETE FROM users WHERE id = $this->id ");

        return true;
    }

    public function update($POST):bool {

        $this->set($POST);
        parent::update($POST);

        $cant_add_resellers = !(bool)$this->can_add_resellers;

        $db = new \ACES2\DB();
        $db->query("UPDATE iptv_user_info SET credits = '$this->credits', user_owner = '$this->reseller_of', 
                          cant_add_reseller = '$cant_add_resellers', allow_channel_list = '$this->allow_channel_list', 
                          allow_vod_list = '$this->allow_vod_list', can_override_package = '$this->can_override_package'
                      WHERE user_id = $this->id  ");

        return true;
    }

    public static function add($POST): \ACES2\User {
        $reseller = new \ACES2\IPTV\Reseller();
        //MAKE SURE THERE IS NO POST ERROR BEFORE ADDING USER.
        $reseller->set($POST);

        $user = \ACES2\User::add($POST);
        $reseller = new Reseller($user->id);
        $reseller->set($POST);

        $cant_add_reseller = $reseller->can_add_resellers ? 0 : 1;
        $allow_channel_list = $reseller->allow_channel_list ? 1 : 0;
        $allow_vod_list = $reseller->allow_vod_list ? 1 : 0;

        $override_package = json_encode($reseller->override_packages);

        $db = new DB();
        $db->query("INSERT INTO iptv_user_info (user_id,credits,user_owner,cant_add_reseller, allow_channel_list, 
                            allow_vod_list , override_packages)
                VALUES($user->id, $reseller->credits, $reseller->reseller_of, '$cant_add_reseller', '$allow_channel_list', 
                       '$allow_vod_list', '$override_package')");
        return $user;
    }

}