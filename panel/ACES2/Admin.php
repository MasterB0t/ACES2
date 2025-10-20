<?php
namespace ACES2;
class Admin {

    public $id = 0;
    public $username = '';
    public $name;
    public $pin ;
    public $full_name = '';
    public $email = '';
    public $is_logged = false;
    public $profile_pic;
    public $group_id;
    public $is_full_admin = false;

    public function __construct(int $admin_id = null) {

        $db = new \ACES2\DB();

        if(session_status() == PHP_SESSION_NONE)
            session_start();

        if(is_null($admin_id)) {

            if(!$admin_id=adminIsLogged())
                return false;
            $this->is_logged = true;

        }

        $r = $db->query("SELECT id,name,username,email,profile_pic,group_id,full_admin FROM admins WHERE id = $admin_id ");
        if(!$row=$r->fetch_assoc()) throw new \Exception("Unable to get admin #$admin_id");

        $this->id = $row['id'];
        $this->name = $row['name'];

        $this->username= $row['username'];
        $this->email = $row['email'];
        $this->profile_pic = $row['profile_pic'] ? $row['profile_pic'] : 'default.png';
        $this->group_id = $row['group_id'];
        $this->is_full_admin = (bool)$row['full_admin'];

        return true;
    }

    public function isLogged($keep_session=true) : bool {
        return adminIsLogged($keep_session);
    }

    public function login($POST) {

        if(empty($POST['username']) || empty($POST['password']))  throw new \ACES\UserException('Wrong password or username.');
        else if(!preg_match('/^[a-zA-Z0-9-]+$/', trim($POST['username']))) throw new \ACES\UserException('Wrong password or username.');

        $username = trim($POST['username']);
        $password = md5(trim($POST['password']));

        $DB = new \ACES2\DB();

        $r=$DB->query("SELECT * FROM admins WHERE username='$username' AND password='$password' LIMIT 1");
        if(!$row=$r->fetch_assoc()) {
            throw new \Exception('Wrong password or username.');
        }

        $this->is_logged = true;
        $this->id= $row['id'];
        $this->username = $row['username'];
        $this->email = $row['email'];
        $this->full_name = $row['name'];
        if(empty($row['profile_pic']))
            $this->profile_pic = 'default.png';


        if(session_status() == PHP_SESSION_NONE)
            session_start();

        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_username'] = $row['username'];
        $_SESSION['admin_email'] = $row['email'];
        $_SESSION['admin_full_name'] = $row['full_name'];
        $_SESSION['admin_avatar'] = $row['profile_pic'];
        //$_SESSION['enc_key'] = trim($POST['password']);

        $_SESSION['admin_expiration_in'] = $row['keep_session'] > 299
            ? $row['keep_session'] : 300 ;

        $_SESSION['admin_expiration'] = time() + $_SESSION['admin_expiration_in'];

        $DB->query("UPDATE admins SET login_ip='{$_SERVER['REMOTE_ADDR']}', login_time=UNIX_TIMESTAMP() 
              WHERE id={$row['id']} " );

        $user_agent = $DB->escString($_SERVER['HTTP_USER_AGENT']);
        $browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);

        //LOG THE IP IF IT DOES NOT EXIST.
        $ipinfo = new IPInfo($_SERVER['REMOTE_ADDR']);

        $DB->query("INSERT INTO admin_logins (admin_id, user_agent, os, browser, login_time, ip_address )
                VALUES ('$this->id', '$user_agent', '{$browser['platform']}', '{$browser['parent']}', 
                        UNIX_TIMESTAMP(), '{$_SERVER['REMOTE_ADDR']}'  )");

        return true;

    }

    public function getPin() {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT pin FROM admins WHERE id = '$this->id' ");
        return (int)$r->fetch_assoc()['pin'];
    }

    public function logout() {

        adminLogout();

        $this->is_logged = false;
        return true;

    }

    public function isPassword(string $password ) {

        if(!$password)
            return false;

        $password = md5($password);
        $db = new \ACES2\DB;
        $r=$db->query("SELECT id FROM admins WHERE 
                          password = '$password' AND id = $this->id ");
        if($r->num_rows>0)
            return true;

        return false;

    }

    public function getSetting(string $setting_name) {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT value FROM admin_settings 
             WHERE admin_id = $this->id AND name like '$setting_name'");

        if(!$value=$r->fetch_assoc()['value'])
            return NULL;

        return $value;
    }

    public function setSettings(string $setting_name, string $value):bool {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT value FROM admin_settings WHERE admin_id = $this->id AND name like '$setting_name'");
        if($r->fetch_assoc())
            $db->query("UPDATE admin_settings SET value = '$value' WHERE admin_id = $this->id AND name like '$setting_name' ");
        else
            $db->query("INSERT INTO admin_settings (admin_id, name, value) VALUES ('$this->id', UPPER('$setting_name'), '$value' )");

        return true;
    }

    public function addLog(string $message, int $type = 0) {

        $db= new \ACES2\DB;
        $message = $db->escape_string($message);

        $db->query("INSERT INTO `admin_logs` (admin_id, type, message, ip_address, time) 
                    VALUES ('$this->id', '$type', '$message', '{$_SERVER['REMOTE_ADDR']}', UNIX_TIMESTAMP()) ");

    }

    public function hasPermission(string $name='full'):bool {

        $db = new DB;
        $name = $db->escString(strtolower($name));

        if( $this->is_full_admin )
            return true;
        else if($name == 'full' && $this->full_privileges != 1 )
            return false;

        $r2=$db->query("SELECT value FROM admin_privileges WHERE name = '$name' AND admin_id = $this->id ");
        if( $r2->fetch_assoc()['value'] == 1 )
            return true;

        return false;
    }

    public function setGroup(int $group_id = 0) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT permissions FROM admin_groups WHERE id = '$group_id' ");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Group #$group_id does not exist.");

        $db->query("UPDATE admins SET group_id = $group_id WHERE id = '$this->id' ");
        $db->query("DELETE FROM admin_privileges WHERE admin_id = '$this->id' ");

        $permissions = json_decode($row['permissions'],1);
        foreach($permissions as $permission => $value) {
            $value = (bool)$value;
            $r=$db->query("SELECT value FROM admin_privileges 
                WHERE admin_id = $this->id AND name = lower('$permission') ");
            if($r=$r->fetch_assoc())
                $db->query("UPDATE admin_privileges SET value = '$value' WHERE admin_id = '$this->id' 
                                              AND name = lower('$permission') ");
            else
                $db->query("INSERT INTO admin_privileges (admin_id, name, value) 
                    VALUES ('$this->id', '$permission', '$value' )");
        }


    }

    public function update(string $username, String $password,
                           String $email ,  String $name ,
                           int $group_id , String $pin = ''):bool {

        $DB = new \ACES2\DB();
        $username =  $DB->escString($username);

        $r=$DB->query("SELECT * FROM admins WHERE username = '$username' AND id != $this->id LIMIT 1");
        if($r->num_rows >0)
            throw new \Exception('Username already exists.');
        $this->username = $DB->escString($username);

        if(!empty($password)) {
            if(strlen(trim($password))<6)
                throw new \Exception('Password must be at least 6 characters.');
            $sql_pass = "password = md5('$password'), ";
        }


        if(!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                throw new \Exception('Email must be a valid email address.');
            $this->email = $email;
        }

        if(!empty($pin)) {
            if(!is_numeric($pin))
                throw new \Exception('PIN must be a numeric value.');

            if(strlen($pin) < 4 || strlen($pin) > 6 )
                throw new \Exception('PIN must be at least 4 characters and no more than 6.');

            $this->pin = $pin;
        }

        $this->name = $DB->escString($name);
        $this->group_id = $group_id;
        if($group_id)
            $this->setGroup($group_id);

        $this->is_full_admin = $group_id > 0 ? 0 : 1;
        $DB->query("UPDATE admins SET username = '$this->username', $sql_pass 
                  email = '$this->email', pin = '$this->pin', name = '$this->name', group_id = $this->group_id, 
                  full_admin = '$this->is_full_admin'
              WHERE id = $this->id  ");

        $this->__construct($this->id);
        return true;
    }

    public function remove():bool {

        $db = new DB();
        $db->query("DELETE FROM admins WHERE id = $this->id ");

        return true;

    }


    static public function create(string $username, String $password,
                                  String $email = "",  String $name = "",
                                  int $group_id = 0, String $pin = "0000") : self{

        $DB = new DB();
        $r=$DB->query("SELECT * FROM admins WHERE username = '$username' LIMIT 1");
        if($r->num_rows >0)
            throw new \Exception('Username already exists.');

        if(strlen(trim($password))<6)
            throw new \Exception('Password must be at least 6 characters.');
        $password = md5(trim($password));

        if(!empty($email))
            if(!filter_var($email, FILTER_VALIDATE_EMAIL))
                throw new \Exception('Email must be a valid email address.');


        $r=$DB->query("SELECT * FROM admins WHERE email = '$email' LIMIT 1");
        if($r->num_rows>0)
            throw new \Exception('Email already exists.');

        if(!is_numeric($pin))
            throw new \Exception('PIN must be a numeric value.');

        if(strlen($pin) < 4 || strlen($pin) > 6 )
            throw new \Exception('PIN must be at least 4 characters and no more than 6.');


        $name = $DB->escString($name);
        $username = $DB->escString($username);
        $is_full_admin = $group_id > 0 ? 0 : 1;
        $profile_pic = "default.png";

        $DB->query("INSERT INTO admins (username, name, email, password, pin, group_id, 
                    full_admin, profile_pic )  
                    VALUES('$username', '$name', '$email', '$password','$pin', '$group_id', '$is_full_admin', 
                           '$profile_pic' )");


        return new self();
    }



}