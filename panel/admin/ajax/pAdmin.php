<?php
$ADMIN = new \ACES2\Admin();

$mysql = new mysqli();

$json = [];
$db = new ACES2\db;
try {

    switch ($_REQUEST['action']) {

        case 'is_logged':
            if(!adminIsLogged(false))
                setAjaxError("",401);
            break;


        case 'login':

            if(!defined("NO_CAPTCHA")) {
                if (empty($_SESSION['captcha']) || strtoupper($_POST['captcha']) !== $_SESSION['captcha']) {
                    $_SESSION['captcha'] = '';
                    setAjaxError("Error resolving captcha. Try again.");
                }
            }

            if(!\ACES2\Armor\Armor::isToken('admin_login', $_POST['token']))
                setAjaxError('Session Expired.', 403);

            try {
                $ADMIN->login($_POST);
            } catch (\Exception $exp ){
                \ACES2\Armor\Armor::log_ban('admin_login');
                //$json['token'] = \ACES2\Armor\Armor::createToken('admin_login', 60 * 10 );
                setAjaxError($exp->getMessage());
            }

            break;

//        case 'get_settings':
//
//            if(!$ADMIN->isLogged(false))
//                setAjaxError("Not logged", 401);
//
//            //IN MINUTES FOR JS
//            $json['AUTO_LOCK_IN'] = (int)$ADMIN->getSetting('AUTO_LOCK_IN') / 60;
//            if(!$json['AUTO_LOCK_IN'])
//                $json['AUTO_LOCK_IN'] = 5;
//
//            break;

        case 'un_lock':
            if( !$_SESSION['admin_id'] || $_SESSION['unlock_tries'] > 4) {
                session_destroy();
                setAjaxError('Not Logged', 401);
            }

            if(!\ACES2\Armor\Armor::isToken('admin_lock', $_POST['token']))
                setAjaxError("Expired Session", 403);

            $db = new ACES2\DB;
            if(\ACES2\Validator::isPin($_REQUEST['password'])) {
                $r=$db->query("SELECT id FROM admins WHERE pin = '{$_REQUEST['password']}' AND id = '{$_SESSION['admin_id']}'");
            } else {
                $password = md5($_REQUEST['password']);
                $r=$db->query("SELECT id FROM admins WHERE password = '$password' AND id = '{$_SESSION['admin_id']}'");
            }

            if(!$r->num_rows) {
                $_SESSION['unlock_tries'] ++;
                setAjaxError('Wrong Password or Pin');
            }

            $json['token'] = \ACES2\Armor\Armor::createToken('admin_lock', 60 * 10);

            adminUpdateSession();
            $_SESSION['unlock_tries'] = 0;
            break;

        case 'info':

            if(!$ADMIN->isLogged())
                setAjaxError("Not logged", 401);

            if(!\ACES2\Armor\Armor::isToken('admin.settings', $_POST['token']))
                setAjaxError('Invalid Session.');

            $name = $db->escape_string($_REQUEST['name']);
            if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL))
                setAjaxError("Enter a valid email address");
            $email = trim($_REQUEST['email']);

            if(!empty($_REQUEST['new_password'])) {
                $new_password = md5($_REQUEST['new_password']);
                if( !\ACES2\Validator::isStrongPassword(trim($_REQUEST['new_password'])) )
                    setAjaxError("New Password is weak.");

                if(trim($_REQUEST['new_password']) != trim($_REQUEST['confirm_password']))
                    setAjaxError("Password do not match");
            }

            if(!empty($_REQUEST['pin'])) {
                if (!preg_match('/^[0-9]{4,6}+$/', $_REQUEST['pin']))
                    setAjaxError('Pin must be a numeric number with at least 4 digits to 6');
                $pin = $_REQUEST['pin'];
            }

            if(!$ADMIN->isPassword($_REQUEST['password']))
                setAjaxError("Invalid Password.");

            $auto_lock = (int)$_REQUEST['auto_lock'] > 299 ?
                (int)$_REQUEST['auto_lock'] : 300 ;

            $_SESSION['admin_expiration_in'] = $auto_lock;

            $db->query("UPDATE admins SET name = '$name', email = '$email', keep_session = '$auto_lock' 
              WHERE id = $ADMIN->id ");

            if($new_password)
                $db->query("UPDATE admins SET password = '$new_password' WHERE id = $ADMIN->id");

            if($pin)
                $db->query("UPDATE admins SET pin = '$pin' WHERE id = $ADMIN->id");


            break;

        case 'update_profile_picture':

            if(!$ADMIN->isLogged())
                setAjaxError("Not logged", 401);

            if(!\ACES2\Armor\Armor::isToken('admin.update_profile_image', $_POST['token']))
                setAjaxError('Invalid Session.');

            if(!$_FILES['profile_picture']['tmp_name'])
                setAjaxError('No image have been selected.');

            $old_image = $ADMIN->profile_pic;
            $image_name = uniqid();

            $file = new \ACES2\File($_FILES['profile_picture'],
                \ACES2\File::getMimeImages()
            );

            $file->upload($image_name ,DOC_ROOT . "/avatars/" );
            $db->query("UPDATE admins SET profile_pic = '$file->filename' WHERE id = '$ADMIN->id'");

            $json['image'] = $file->filename;
            $json['token'] = \ACES2\Armor\Armor::createToken('admin.update_profile_image');
            if($old_image != 'default.png')
                unlink(DOC_ROOT . "/avatars/" .$old_image);

            break;

//        case 'settings' :
//
//            if(!$ADMIN->isLogged())
//                setAjaxError("Not logged", 401);
//
//            if(!\ACES2\Armor\Armor::isToken('admin.settings', $_POST['token']))
//                setAjaxError('Invalid Session.');
//
//            $auto_lock_in = (int)$_REQUEST['auto_lock_in'] > 299 ? $_REQUEST['auto_lock_in'] : 0;
//            $ADMIN->setSettings('AUTO_LOCK_IN', $auto_lock_in);
//            $_SESSION['admin_auto_lock_in'] = $auto_lock_in;
//
//            $json['token'] = \ACES2\Armor\Armor::createToken('admin.settings');
//
//            break;

        default:
            setAjaxError('System Error.');

    }

} catch(\Exception $exp) {
    setAjaxError($exp->getMessage());
}

setAjaxComplete($json);

