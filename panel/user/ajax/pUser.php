<?php

session_start();

$db = new ACES2\db;

$json = [];

try {
    switch($_REQUEST['action']) {

        case 'is_logged':
            if(!userIsLogged(false))
                setAjaxError("",401);
            break;

        case 'login':

            if(!\ACES2\Armor\Armor::isToken('user_login', $_POST['token']))
                setAjaxError('Session Expired.', 403);

            $db = new \ACES2\DB();
            $username = $db->escString($_POST['username']);
            $password = md5($_POST['password']);
            $r=$db->query("SELECT * FROM users WHERE username = '$username' AND password = '$password' ");
            if(!$row=$r->fetch_assoc()) {
                \ACES2\Armor\Armor::log_ban('user_login');
                setAjaxError('Username or password is incorrect.');
            }

            if($row['status'] != 1)
                setAjaxError('Your account have been disabled.');

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_username'] = $row['username'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_avatar'] = $row['profile_pic'];

            $_SESSION['user_expiration_in'] = $row['keep_session'] > 299
                ? $row['keep_session'] : 300 ;


            $_SESSION['user_expiration'] = time() + $_SESSION['user_expiration_in'];


            $db->query("UPDATE users SET login_date = NOW() , login_ip = '{$_SERVER['REMOTE_ADDR']}' 
             WHERE id = '{$row['id']}' ");

            $user_agent = $db->escString($_SERVER['HTTP_USER_AGENT']);
            $browser = get_browser($user_agent, true);

            //LOG THE IP IF IT DOES NOT EXIST.
            $ipinfo = new \ACES2\IPInfo($_SERVER['REMOTE_ADDR']);

            $db->query("INSERT INTO user_logins (user_id, user_agent, os, browser, login_time, ip_address) 
            VALUES('{$row['id']}', '$user_agent', '{$browser['platform']}', '{$browser['parent']}', UNIX_TIMESTAMP(), 
                   '{$_SERVER['REMOTE_ADDR']}' )");

            break;

        case 'un_lock':
            if( !$_SESSION['user_id'] || $_SESSION['user_unlock_tries'] > 4) {
                session_destroy();
                setAjaxError('Not Logged', 401);
            }

            if(!\ACES2\Armor\Armor::isToken('user_lock', $_POST['token']))
                setAjaxError("Expired Session", 403);

            $db = new ACES2\DB;
//            if(\ACES2\Validator::isPin($_REQUEST['password'])) {
//                $r=$db->query("SELECT id FROM users WHERE pin = '{$_REQUEST['password']}' AND id = '{$_SESSION['admin_id']}'");
//            } else {
//                $password = md5($_REQUEST['password']);
//                $r=$db->query("SELECT id FROM users WHERE password = '$password' AND id = '{$_SESSION['admin_id']}'");
//            }

            $password = md5($_REQUEST['password']);
            $r=$db->query("SELECT id FROM users WHERE password = '$password' AND id = '{$_SESSION['user_id']}'");

            if(!$r->num_rows) {
                $_SESSION['user_unlock_tries']++;
                setAjaxError('Wrong Password.');
            }

            $json['token'] = \ACES2\Armor\Armor::createToken('user_lock', 60 * 10);

            userUpdateSession();
            $_SESSION['user_unlock_tries'] = 0;

            break;

        case 'update_profile_picture':

            if(!$UserID=userIsLogged())
                setAjaxError("Not logged", 401);

            $User = new \ACES2\User($UserID);

            if(!$_FILES['profile_picture']['tmp_name'])
                setAjaxError('No image have been selected.');

            $old_image = $User->profile_picture;
            $image_name = "user$User->id-" . uniqid();

            $file = new \ACES2\File($_FILES['profile_picture'],
                \ACES2\File::getMimeImages()
            );

            $file->upload($image_name, DOC_ROOT . "/avatars/");
            $db->query("UPDATE users SET profile_pic = '$file->filename' WHERE id = '{$User->id}'");

            $json['image'] = $file->filename;
            if($old_image != 'default.png')
                unlink(DOC_ROOT . "/avatars/" .$old_image);

            break;


        case 'profile':

            if(!$UserID=userIsLogged())
                setAjaxError("Not logged", 401);

            $User = new \ACES2\User($UserID);

//            if(!\ACES2\Armor\Armor::isToken('user.profile', $_POST['token']))
//                setAjaxError('Invalid Session.');

            $name = $db->escString($_REQUEST['name']);
            if(empty($name))
                setAjaxError('Name is required.');

            $sql_pass = '';
            if(!empty($_REQUEST['new_password'])) {

                $new_pass = md5($_REQUEST['new_password']);
                if(strlen($new_pass) < 6 )
                    setAjaxError("New password must be at least 6 characters long.");

                if( $new_pass != md5($_REQUEST['confirm_password']))
                    setAjaxError('New Password Mismatch.');
                $sql_pass = " , password = '$new_pass' ";
            }

            $pin_sql = '';
            if(!empty($_REQUEST['pin'])) {
                $pin = $_REQUEST['pin'];
                if(!preg_match('/^[0-9-]{4,6}+$/', $_REQUEST['pin'] ))
                    setAjaxError("Pin must be 4 to 6 digits.");

                $pin_sql = " , pin = '$pin' ";

            }

            $auto_lock_in = (int)$_REQUEST['auto_lock_in'] > 299 ? $_REQUEST['auto_lock_in'] : 300 ;
            //$auto_lock_in = (int)$_REQUEST['auto_lock_in'];
            $_SESSION['user_expiration_in'] = $auto_lock_in;

            if(!$User->isPassword($_REQUEST['password']))
                setAjaxError("Wrong Password.");

            $db->query("UPDATE users SET name = '$name', keep_session = '$auto_lock_in' $sql_pass $pin_sql
                WHERE id = '{$User->id}'  ");


            break;


        case 'get_credits':
            if(!$UserID=userIsLogged())
                setAjaxError("Not logged", 401);

            $User = new \ACES2\IPTV\User($UserID);
            $json['credits'] = $User->getCredits();
            break;


        default:
            logE("Unknown action");
            setAjaxError("System Error");

    }



} catch( \Exception $exp ){
    setAjaxError($exp->getMessage());
}

setAjaxComplete($json);