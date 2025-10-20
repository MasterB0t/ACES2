<?php

error_reporting(0);

$RED  = "\033[31m";
$WHITE = "\033[0m";
$GREEN = "\033[32m";

$connected=0;

function l($msg) { error_log("$msg\n", 3, "/home/aces/logs/xtream_merge.log"); }

function logErr() { 
    global $sql,$DB;
    
    l("MYSQL FAIL WHEN EXECUTING '$sql' MYSQL ERROR ".mysqli_error($DB));
    unlink('/home/aces/run/aces_merge_xc.pid');
    die;
}



function get_string_between($string, $start, $end){
    //$string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

if(is_file("/home/aces/run/aces_merge_xc.pid")) { 
    
    $PID = file_get_contents("/home/aces/run/aces_merge_xc.pid");
    if(posix_getpgid($PID)) die("Another importer proccess is running please wait until it finished.");
} else {
 
    if(!file_put_contents("/home/aces/run/aces_merge_xc.pid",getmypid())) die('Could not write pid file.');
 
}

if(isset($argv[1]) && $argv[1] == '--auto' ) { 
    
    if(empty($argv[2])) $pass = '';
    else $pass = $argv[2]; 
    
    $DB = new mysqli('localhost','root',$pass,'aces_iptv');
    if($DB->connect_errno > 1) { Die ('Could not connect to database.'); } 
    
} else {


    while(true) { 
        echo "\n\nPlease enter the mysql root password. ";
        $pass=trim(fgets(STDIN));
        $DB = new mysqli('localhost','root',$pass,'aces_iptv');
        if($DB->connect_errno == 0) break;
        echo "\n{$RED}Wrong password.{$WHITE}";
    }

}


unlink('/home/aces/logs/xtream_merge.log');
l("PROCCESS START.\n");

$DB = new mysqli('localhost','root',$pass,'aces_iptv');
if($DB->connect_errno > 0){ die('Unable to connect to database.'); }

if(!$r=$DB->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA  WHERE SCHEMA_NAME = 'xtream_iptvpro'")) die("There is no xtreamcodes database installed nothing to import.");
if(!mysqli_fetch_array($r)) die("There is no xtreamcodes database installed nothing to import.");

$DB_XC = new mysqli('localhost','root',$pass,'xtream_iptvpro');
if($DB_XC->connect_errno > 0){ die('Unable to connect to xtreamcodes database.'); }


$DB->query("DELETE FROM users ");



unlink('/home/aces/run/aces_merge_xc.pid');

