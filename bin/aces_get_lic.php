<?php

if(is_file('/home/aces/stream/config.php')) { 
    
    include '/home/aces/stream/config.php';
    $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
    if($DB->connect_errno > 0) die('ERROR: Could not connect to aces database.');
    
} else if(is_file('/home/aces/panel/includes/config.php')) { 
    
    include '/home/aces/panel/includes/config.php';
    $DB = new mysqli($config['database']['host'],$config['database']['user'],$config['database']['pass'],$config['database']['name']);
    if($DB->connect_errno > 0) die('ERROR: Could not connect to aces database.');
    
} else if( is_file('/home/aces/www/includes/config.php') ) {
    
    include '/home/aces/www/includes/config.php';
    $DB = new mysqli($config['database']['host'],$config['database']['user'],$config['database']['pass'],$config['database']['name']);
    if($DB->connect_errno > 0) die('ERROR: Could not connect to aces database.');
    
} else die("ERROR: ACES is not installed correctly.");

$url='';
if($r=$DB->query("SELECT * FROM iptv_loaders WHERE server_id = '$SERVER_ID' "))
while($row=$r->fetch_assoc()) { 
    $url .= "&{$row['name']}_version={$row['version']}";
}


if(!is_dir('/home/aces/lics/')) 
    if(!mkdir('/home/aces/lics')) { die('Could not create directory to store lics.'); }
        
$json = json_decode(file_get_contents("http://acescript.ddns.net/get_lic.php?$url"),1);
foreach($json['lics'] as $k => $j ) { 
    file_put_contents("/home/aces/lics/$k.txt",$j['key']); 
}