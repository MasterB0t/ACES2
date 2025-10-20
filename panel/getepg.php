<?php
ini_set('memory_limit', '-1');

//ob_end_clean();
//ob_start();

require_once $_SERVER['DOCUMENT_ROOT'].'/functions/logs.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";


$DB = new \ACES2\DB;

$username = $DB->escString($_GET['username']);
$password = !empty($_GET['token']) ? $_GET['token'] : $_GET['password'];
$password = $DB->escString($password);
$file = empty($_GET['file']) ? 'guide.xml' : $_GET['file'];
list($file,$type) = explode('.',$file);

if($type == 'gz' ) { $file = 'guide.xml'; $type = 'gz'; }

//if($type == 'tar' ) $type = 'tar.gz';
//else if($type == 'zip' ) $type = 'zip';
//else if($type == 'gz' ) { $file = 'guide.xml'; $type = 'gz'; }
//else if($type == 'xml' ) $type = 'xml';
//else die('');


$r = $DB->query("SELECT id,lock_ip_address,lock_user_agent FROM iptv_devices 
      WHERE token = '$password' AND username = '$username' AND subcription >= NOW() AND status = 1 ");

if($r->num_rows < 1 ) {

//    $ARMOR = new Armor();
//    $ARMOR->action('iptv-account');

    sleep(3);
    die;

}

clearstatcache();

if(!is_file("/home/aces/guide/$file.$type")) {
    sleep(3); die();
}


header('Content-Length: ' . filesize("/home/aces/guide/$file.$type"));

switch($type) {
    case 'gz':
        header('Content-Disposition: attachment; filename=guide.xml.gz');
        header('Content-type: application/octet-stream' );
        break;
    case 'zip':
        header('Content-Disposition: attachment; filename=guide.zip');
        header('Content-type: application/zip' );
        break;
    default:
        header('Content-Disposition: attachment; filename=guide.xml'); //NOTE. THIS WAS DISABLED ON OLD PANEL. I DONT KNOW IF IT HAVE ANY EFFECT ON ANY APP.
        header('Content-Type: text/xml');
        break;

}

header("Connection: close");
header("Accept-Ranges: bytes");


//ob_clean();
@$fp = fopen("/home/aces/guide/$file.$type", 'rb');
@fpassthru($fp);
@fclose($fp);
//ob_end_flush();
//@ob_flush();@flush();
die();

