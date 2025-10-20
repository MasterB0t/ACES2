<?php

namespace ACES2\IPTV;

use Exception;

class Reseller2 {

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    const STATUS_BLOCKED = 2;

    public $id = 0;
    public $name = '';
    public $email = '';
    public $username = '';
    public $pin = '0000';
    private $password = null;
    public $status = 1;
    public $profile_picture = 'default.png';

    private $credits = 0;
    public $reseller_of = 0;
    public $can_add_resellers = false;
    public $allow_channel_list = false;
    public $allow_vod_list = false;
    private $allow_set_account_username = false;
    private $allow_set_account_password = false;
    private $allow_restart_streams = false;
    public $can_override_package = false;
    public $override_packages = array();

    public function __construct(int $user_id = null ) {

        if(is_null($user_id))
            return;

        $this->id = $user_id;

        $db = new \ACES2\DB();

        $r=$db->query("SELECT * FROM users WHERE id = '$user_id'");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("User #$user_id not found");

        $this->name = $row['name'];
        $this->email = $row['email'];
        $this->username = $row['username'];
        $this->pin = $row['pin'];
        $this->status = $row['status'];
        $this->profile_picture = $row['profile_pic'];

        $r=$db->query("SELECT * from iptv_user_info WHERE user_id = '$user_id' ");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("IPTV User #$user_id not found");

        $this->credits = $row['credits'];
        $this->reseller_of = $row['user_owner'];
        $this->can_add_resellers = !(bool)$row['cant_add_reseller'];
        $this->allow_vod_list = (bool)$row['allow_vod_list'];
        $this->allow_channel_list = (bool)$row['allow_channel_list'];
        $this->allow_set_account_username = (bool)$row['can_account_username'];
        $this->allow_set_account_password = (bool)$row['can_account_password'];
        $this->allow_restart_streams = (bool)$row['can_restart_streams'];
        $this->can_override_package = (bool)$row['can_override_package'];
        $this->override_packages =  json_decode($row['override_packages'],1);


    }

    public function getCredits():int {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT credits FROM iptv_user_info WHERE user_id = '$this->id' ");
        return (int)$r->fetch_assoc()['credits'];
    }


    public function getOverridePackage() {

        $db = new \ACES2\DB();
        $r=$db->query(" SELECT override_packages FROM iptv_user_info 
                          WHERE user_id = '$this->id' ");

        if(!$packages = json_decode($r->fetch_assoc()['override_packages'],true))
            return [];

        return $packages;
    }

    /**
     * Get bouquet packages user are allowed to create/update accounts with.
     * @return BouquetPackage
     * @throws \Exception
     */
    public function getBouquetPackages(): array {
        $db = new \ACES2\DB();

        $r=$db->query("SELECT override_packages FROM iptv_user_info WHERE user_id = '$this->id' ");
        $override_packages = json_decode($r->fetch_assoc()['override_packages'],true);

        $r=$db->query("SELECT id,name,official_credits,trial_credits,official_duration,official_duration_in, 
                trial_duration,official_duration_in
            FROM iptv_bouquet_packages ");

        $packages =[];
        while($row=$r->fetch_assoc()) {
            if($override_packages[$row['id']]['disabled'] != 1 ) {

                $pkg = new BouquetPackage($row['id']);
                if(isset($override_packages[$row['id']])) {
                    if(!empty($override_packages[$row['id']]['trial_credits']))
                        $pkg->trial_credits = (int)$override_packages[$row['id']]['trial_credits'];
                    if(!empty($override_packages[$row['id']]['official_credits']))
                        $pkg->official_credits = (int)$override_packages[$row['id']]['official_credits'];
                }

                $packages[] = $pkg;

            }
        }

        return $packages;
    }

    /**
     * Get BouquetPackage with Override Values for user.
     * @param int $package_id
     * @return BouquetPackage|null:NULL, return NULL of do not exist or package is disabled.
     */
    public function getBouquetPackage(int $package_id): ?BouquetPackage {

        $db = new \ACES2\DB();

        $r=$db->query("SELECT override_packages FROM iptv_user_info WHERE user_id = '$this->id' ");
        $override_packages = json_decode($r->fetch_assoc()['override_packages'],true);

        if(@$override_packages[$package_id]['disabled'] == 1 ) {
            logD("Package #$package_id is disabled for this user.");
            return null;
        }

        try {
            $package = new BouquetPackage($package_id);

            //OVERRIDE VALUES.
            if(is_numeric($override_packages[$package_id]['trial_credits']))
                $package->trial_credits = (int)$override_packages[$package_id]['trial_credits'];
            if(is_numeric($override_packages[$package_id]['official_credits']))
                $package->official_credits = (int)$override_packages[$package_id]['official_credits'];

        } catch( Exception $e) {
            logD($e->getMessage());
            return null;
        }

        return $package;

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
    public function isAllowAccountUsername(): bool {
        return $this->allow_set_account_username;
    }
    public function isAllowAccountPassword(): bool {
        return $this->allow_set_account_password;
    }
    public function isAllowRestartStreams(): bool {
        return $this->allow_restart_streams;
    }

    public function setName(String $name) {
        $db = new \ACES2\DB();
        $this->name = $db->escString($name);
        $this->save();
    }
    public function setUsername(string $username) {

        if(strlen($username) < 5)
            throw new \Exception("Username must be at least 5 characters long.");

        $sql = (int)$this->id > 0 ? "AND id != '$this->id' " : '';

        $db = new \ACES2\DB();
        $r=$db->query("SELECT id from users WHERE username = '$username' $sql ");
        if($r->num_rows > 0 )
            throw new \Exception("Username already exists.");

        $this->username = $username;
        $this->save();
    }
    public function setPassword(string $password) {
        if(strlen($password) < 5)
            throw new \Exception("Password must be at least 5 characters long.");
        $this->password = $password;
        $this->save();
    }
    public function setEmail(string $email) {

        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new \Exception("Enter a valid email address.");

        $db = new \ACES2\DB();
        $sql = (int)$this->id > 0 ? "AND id != '$this->id' " : '';
        $r=$db->query("SELECT id from users WHERE email = '$email' $sql ");
        if($r->num_rows > 0)
            throw new \Exception("Email already exists.");

        $this->email = $email;
        $this->save();
    }
    public function setStatus(int $status) {
        if($status < 0 || $status > 2 )
            throw new \Exception("Invalid status");

        $this->status = $status;
        $db=new \ACES2\DB();
        $db->query("UPDATE users SET status = '$status' WHERE id = '$this->id' ");
    }
    public function setOwner(int $user_id) {
        $db = new \ACES2\DB();

        if($user_id == 0 ){
            $this->reseller_of = 0;
            $this->save();
            return;
        }

        if($this->id == $user_id)
            throw new \Exception("User can not be a reseller of himself.");

        $r=$db->query("SELECT id FROM users WHERE id = '$user_id' ");
        if($r->num_rows < 1 )
            throw new \Exception("User #$user_id does not exist.");

        $this->reseller_of = $user_id;

        $this->save();
    }
    public function setCredits(int $credits) {
        if($credits < 0)
            throw new \Exception("Invalid credit value");

        $this->credits = $credits;
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_user_info SET credits = '$credits' 
                      WHERE user_id = '$this->id' ");
    }
    public function setCanAddResellers(bool $can_add_resellers) {
        $this->can_add_resellers = $can_add_resellers;
        $cant_add_resellers = !$can_add_resellers;
        $db = new \ACES2\DB();
        $db->query("UPDATE iptv_user_info SET cant_add_reseller = '$cant_add_resellers' 
                      WHERE user_id = '$this->id' ");
    }
    public function setAllowChannelList(bool $allow_channel_list) {
        $this->allow_channel_list = $allow_channel_list;
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_user_info SET allow_channel_list = '$allow_channel_list' 
                      WHERE user_id = '$this->id' ");
    }
    public function setAllowAccountUsername(bool $allow_account_username) {
        $this->allow_set_account_username = $allow_account_username;
        $this->save();
    }
    public function setAllowRestartStreas(bool $allow_restart_streams) {
        $this->allow_restart_streams = $allow_restart_streams;
        $this->save();
    }
    public function setAllowAccountPassword(bool $allow_account_password) {
        $this->allow_set_account_password = $allow_account_password;
        $this->save();
    }

    public function setAllowVodList(bool $allow_vod_list) {
        $this->allow_vod_list = $allow_vod_list;
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_user_info SET allow_vod_list = '$allow_vod_list' 
                      WHERE user_id = '$this->id' ");
    }

    public function setCanOverridePackage(bool $can_override_package) {
        $this->can_override_package = $can_override_package;
    }
    public function setOverridePackage($Official_Credits, $Trial_Credits, $Enabled ):bool {

        $this->override_packages = [];
        foreach ($Official_Credits as $id => $val) {

            if ($val == '')
                $this->override_packages[$id]['official_credits'] = '';
            else
                $this->override_packages[$id]['official_credits'] = (int)$val;


            if ($Trial_Credits[$id] == '')
                $this->override_packages[$id]['trial_credits'] = '';
            else
                $this->override_packages[$id]['trial_credits'] = (int)$Trial_Credits['trial_credits'][$id];

            if ($Enabled[$id] == 0)
                $this->override_packages[$id]['disabled'] = 1;

        }

        $override_packages = json_encode($this->override_packages);
        $db = new \ACES2\DB();
        $db->query("UPDATE iptv_user_info SET override_packages = '$override_packages' 
                      WHERE user_id = '$this->id' ");

        return true;
    }


    public function canManageAccount(int $account_id):bool {

        $db = new \ACES2\DB();
        $SubResellers = $this->getResellers();
        $SubResellers[] = $this->id;

        try {
            $Account = new Account($account_id);
        } catch (Exception $e) {
            logD($e->getMessage());
            return false;
        }

        $r = in_array($Account->owner_id, $SubResellers);

        return $r;

    }

    public function remove(int $transfer_reseller_to_user, int $transfer_account_to_user):bool {
        $db = new \ACES2\DB();

        //CONFIRM RESELLER EXIST.
        if($transfer_reseller_to_user > 0)
            $reseller = new self($transfer_reseller_to_user);

        $db->query("UPDATE iptv_user_info SET user_owner = '$transfer_reseller_to_user' 
            WHERE user_id = '$this->id'");

        if($transfer_account_to_user)
            $reseller = new self($transfer_account_to_user);

        $db->query("UPDATE iptv_devices SET user_id = '$transfer_reseller_to_user' 
                    WHERE user_id = '$this->id' ");

        $db->query("DELETE FROM iptv_user_info WHERE user_id = '$this->id'");
        $db->query("DELETE FROM users WHERE id = '$this->id'");

        return true;
    }

    /**
     * Validate Override Package for SubReseller
     * @param $OfficialCredits
     * @param $TrialCredits
     * @return array $OverridePackage
     */
    public function validateOverridePackageForReseller($OfficialCredits, $TrialCredits) {

        $SubResellerPack = [];

        foreach($OfficialCredits as $pack_id => $val ) {

            //MAKE SURE PACKAGE EXIST.
            $Package = new \ACES2\IPTV\BouquetPackage($pack_id);

            $ResellerOfficialVal = $OfficialCredits[$pack_id];
            $ResellerTrialVal = $TrialCredits[$pack_id];

            //IF PACKAGE OF THE USER IS DISABLED IGNORE IT NO VALUE SHOULD BE SET.
            if($this->override_packages[$pack_id]['disabled'])
                $SubResellerPack[$pack_id]['disabled'] = true;

            else {

                //MAKE SURE PACKAGE IS OVERRIDE OTHERWISE WE USING PACKAGE VALUE.
                $override_official = $this->override_packages[$pack_id]['official_credits'] == '' ?
                    $Package->official_credits : (int)$this->override_packages[$pack_id]['official_credits'];

                $override_trial = $this->override_packages[$pack_id]['trial_credits'] == '' ?
                    $Package->official_credits : (int)$this->override_packages[$pack_id]['trial_credits'];


                //VALUE SET FOR RESELLER MUST BE SAME OR GRATER THAN USER
                $SubResellerPack[$pack_id]['official_credits'] = $ResellerOfficialVal > $override_official ?
                    $ResellerOfficialVal :
                    $override_official;

                $SubResellerPack[$pack_id]['trial_credits'] = $ResellerTrialVal >= $override_trial ?
                    $ResellerTrialVal :
                    $override_trial;


            }


        }

        return $SubResellerPack;
    }

    public function save() {
        $db = new \ACES2\DB();
        $username = $db->escString($this->username);
        $name = $db->escString($this->name);

        $sql_pass = !is_null($this->password) ?
            ", password = md5('$this->password') " :
            '';

        $override_packages = json_encode($this->override_packages);

        $db->query("UPDATE users SET name='$name', email = '$this->email', username = '$this->username', 
                 status = '$this->status' $sql_pass
                WHERE id = '$this->id'");

        $cant_add_resellers = !(bool)$this->can_add_resellers;

        $db->query("UPDATE iptv_user_info SET override_packages = '$override_packages' , user_owner = '$this->reseller_of',
                          can_override_package = '$this->can_override_package', cant_add_reseller = '$cant_add_resellers', 
                          allow_channel_list = '$this->allow_channel_list', allow_vod_list = '$this->allow_vod_list', 
                          credits = '$this->credits', can_account_password = '$this->allow_set_account_password',
                          can_account_username = '$this->allow_set_account_username', can_restart_streams = '$this->allow_restart_streams'
                      WHERE user_id = '$this->id' ");

    }

    static public function add( string $username, string $password, string $email, $name = '' ):self {

        $user = new self();

        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);

        $db = new \ACES2\DB();

        $username = $db->escString($username);
        $password = md5($password);
        $name = $db->escString($name);

        $db->query("INSERT INTO users ( username, name, password, pin, email, profile_pic, status ) 
            VALUES ('$username', '$name', '$password', '$user->pin', '$user->email', '$user->profile_picture', '$user->status' ) ");

        $user_id = $db->insert_id;

        $db->query("INSERT INTO iptv_user_info (user_id) VALUES ('$user_id')");

        return new self($user_id);
    }

}




