<?php

namespace ACES2;

class User {

    const STATUS_ENABLED = 1;

    public $id;
    public $name;
    public $email;
    public $username;
    public $status = 0;
    public $profile_picture = 'default.png';

    public function __construct(int $user_id ) {

        if(is_null($user_id)) {
            return;
        }

        $db = new \ACES2\DB();

        if ($user_id != null) {

            $r=$db->query("SELECT * FROM users WHERE id = '$user_id' ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to get user #$user_id from database.");

            $this->id = $user_id;
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->username = $row['username'];
            $this->profile_picture = $row['profile_pic'] ? $row['profile_pic'] : 'default.png';
            $this->status = (int)$row['status'];

        }

    }

    public function isLogged($keep_session=true)  {
        return userIsLogged($keep_session);
    }

    public function isPassword(string $password):bool {

        if(!$this->id) return false;

        $db = new \ACES2\DB();
        $r=$db->query("SELECT id FROM users WHERE password = md5('$password') AND id = '$this->id' ");
        if($r->num_rows < 1)
            return false;

        return true;

    }

    //DEPRECATED
    public function login(string $username, string $password) : bool {

        $db = new \ACES2\DB();
        $username = $db->escString($username);
        $r=$db->query("SELECT * FROM users WHERE username = '$username' AND password = md5('$password') ");

        if(!$row=$r->fetch_assoc()) {
            throw new \Exception('Wrong password or username.');
        }

        if(session_status() == PHP_SESSION_NONE)
            session_start();

        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->username = $row['username'];
        $this->email = $row['email'];
        $this->profile_picture = $row['profile_pic'] ? $row['profile_pic'] : 'default.png';

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_username'] = $row['username'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['user_avatar'] = $row['profile_pic'];

        $_SESSION['user_expiration'] = time() + 60*60;
        $_SESSION['user_expiration_lock'] = time() + 60*60;

        //TODO
        $_SESSION['user_auto_lock_in'] = (int)$this->getSetting('AUTO_LOCK_IN');

        $db->query("UPDATE users SET login_date = NOW() , login_ip = '{$_SERVER['REMOTE_ADDR']}' 
             WHERE id = $this->id ");

        $user_agent = $db->escString($_SERVER['HTTP_USER_AGENT']);
        $browser = get_browser($user_agent, true);

        $db->query("INSERT INTO user_logins (user_id, user_agent, os, browser, login_time, ip_address) 
            VALUES('$this->id', '$user_agent', '{$browser['platform']}', '{$browser['parent']}', UNIX_TIMESTAMP(), 
                   '{$_SERVER['REMOTE_ADDR']}' )");

        return true;
    }

    public function setSetting($name, $value):bool {

        if(!$this->id) {
            logE("Will not set setting '$name' no user have been not set.");
            return false;
        }

        $db = new \ACES2\DB();
        $name = strtolower($db->escString($name));
        $value = $db->escString($value);

        $r = $db->query("SELECT user_id FROM user_preferences WHERE name = '$name' AND user_id = '$this->id' ");
        if($r->num_rows > 0)
            $db->query("UPDATE user_preferences SET value = '$value' 
                        WHERE user_id = '$this->id' AND name = '$name' ");
        else
            $db->query("INSERT INTO user_preferences (user_id, name, value) 
                VALUES('$this->id','$name','$value')");


        return true;
    }

    public function getSetting($name){

        if(!$this->id) {
            throw new \Exception("Will not set setting '$name' no user have been not set.");
        }

        $db = new \ACES2\DB();
        $name = strtolower($db->escString($name));

        $r=$db->query("SELECT value FROM user_preferences 
             WHERE name = '$name' AND user_id = '$this->id' ");

        if(!$val=$r->fetch_assoc()['value'])
            return null;

        return $val;

    }

    protected function set($POST):bool {

        $sql_id = $this->id ? " AND id != $this->id  " : " ";

        $db = new DB();
        $this->name = $db->escString($POST['name']);

        if(!filter_var($POST['email'], FILTER_VALIDATE_EMAIL))
            throw new \Exception("Enter a valid email address.");
        $this->email = trim($POST['email']);
        $r=$db->query("SELECT id FROM users WHERE lower(email) = lower('$this->email') $sql_id ");
        if($r->fetch_assoc())
            throw new \Exception("Email is already in used by another user.");

        $this->username = $db->escString($POST['username']);
        if(strlen($this->username) < 5 )
            throw new \Exception("Username must be at least 5 characters long.");
        $r=$db->query("SELECT id FROM users WHERE lower(username) = lower('$this->username') $sql_id ");
        if($r->fetch_assoc())
            throw new \Exception("Username is already in used by another user.");

        if( !$this->id || !empty($POST['password'])) {
            if(strlen($POST['password'])< 5)
                throw new \Exception("Password must be at least 5 characters long.");

            $this->password = $POST['password'];
        }

        $this->status = $POST['enabled'] ?  1 : 0;

        return true;
    }

    public function update($POST):bool{

        $this->set($POST);

        $sql_pass = $this->password ? " password = MD5('$this->password'), " : " ";

        $db = new \ACES2\DB();
        $db->query("UPDATE users SET name = '$this->name', username = '$this->username', $sql_pass
                 email = '$this->email', status = $this->status WHERE id = '$this->id' ");

        return true;
    }

    public function remove():bool{
        $db = new \ACES2\DB();
        $db->query("DELETE FROM users WHERE id = '$this->id' ");
        return true;
    }
    public static function add($POST):self {
        $user = new self(null);
        $user->set($POST);
        $db = new DB();

        $db->query("INSERT INTO users (username, name, password, email, phone, profile_pic, status, signup_ip, signup_date)
                   VALUES('$user->username', '$user->name', md5('$user->password'), '$user->email', '', '$user->profile_picture', '1', 
                          '{$_SERVER['REMOTE_ADDR']}', NOW() ) ");
        $user->id = $db->insert_id;

        return $user;

    }

}