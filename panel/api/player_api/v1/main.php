<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Connection: close');

ini_set('memory_limit','500M');
set_time_limit ( 0 );

if (session_status() !== PHP_SESSION_NONE)
    session_write_close();

include_once '/home/aces/stream/config.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/includes/config.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/functions/logs.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/class/Cache.php";

$TMDB_API_KEY='';

$DB = new \ACES2\DB($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die; } 

//include_once '/home/aces/panel/includes/init.php';

//$ARMOR = new \ACES\Armor();
//if( $ARMOR->isBlock('iptv-player_api') ) { echo json_encode(array('user_info' => array('auth' => 0 ))); die; }

$IGNORE_USERNAME=0;
$r=$DB->query("SELECT value FROM settings WHERE name = 'iptv.ignore_username_on_api' ");
if(!$IGNORE_USERNAME=mysqli_fetch_array($r)['value']) $IGNORE_USERNAME=0;

$USERNAME=null;
if($IGNORE_USERNAME) {
    
    $PASSWORD = $DB->real_escape_string($_GET['password']);
    $sql = " WHERE token = '$PASSWORD'  ";
    
} else {
    
    $PASSWORD = $DB->real_escape_string($_GET['password']);
    $USERNAME = $DB->real_escape_string($_GET['username']);
    if(empty($USERNAME)  || empty($PASSWORD)  ) { echo json_encode(array('user_info' => array('auth' => 0 ))); die; } 
    //$sql = " WHERE username = '$USERNAME' AND token = '$PASSWORD' AND only_mag = 0 AND no_allow_xc_apps = 0 ";
    $sql = " WHERE username = '$USERNAME' AND token = '$PASSWORD' AND status = 1  ";

}


$r=$DB->query("SELECT id,message,token,username,limit_connections,subcription,demo,status,add_date,bouquets,adults,
       TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM iptv_devices $sql ");
if(!$ACCOUNT=$r->fetch_assoc()) {

    echo json_encode(array('user_info' => array('auth' => 0 )));

    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/IPTV/Server.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Armor/Armor.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/Firewall.php";

    $Armor = new \ACES2\ARMOR\Armor();
    $Armor->log_ban("iptv-account");
    if($Armor->isBan('iptv-account')) {
        $fw = ACES2\Firewall::add(
            'INPUT',
            'DROP',
            $_SERVER['REMOTE_ADDR'],
            '',
            '',
            '',
            'AUTO BLOCK'
        );
        $fw->appendRule(1);
    }

    sleep(5);
    die;
}

$ACCOUNT['bouquets'] = join("','", unserialize($ACCOUNT['bouquets']) );
unset($sql);

$protocol = 'http';
//if($server['sport']) $protocol = 'https';
if( isset($_SERVER['HTTPS'] ) )  $protocol  = 'https';