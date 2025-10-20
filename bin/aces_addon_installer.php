<?php

echo "Welcome to the addon installer.\n\n";

if(!isset($argv[1])) { 

    echo "Please enter the name of the addon you want to install.\n";
    $addon_name=trim(fgets(STDIN));
    echo "\n";
    
} else $addon_name = trim($argv[1]);

if($json=json_decode( file_get_contents("http://acescript.ddns.net/download/$addon_name"),true)) { 
    die("ERROR: This addon do not exist.\n");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://acescript.ddns.net/download/$addon_name");
// don't download content
curl_setopt($ch, CURLOPT_NOBODY, 1);
curl_setopt($ch, CURLOPT_FAILONERROR, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
if(curl_exec($ch)===FALSE) {
    
    die("Fail to download the addon. Please try again later.");
    
} 

//REMOVING OLDER INSTALLER FOLDER IF EXIST.
if(is_dir("/home/aces/tmp/$addon_name/"))
    exec("rm -rf /home/aces/tmp/$addon_name/");
    sleep(2);
    clearstatcache();
if(is_dir("/home/aces/tmp/$addon_name/")) die("ERROR: An older installing file from this addon already exist and can't be deleted.");


if(is_file("/home/aces/tmp/{$addon_name}.tar.gz")) 
    if(!unlink("/home/aces/tmp/{$addon_name}.tar.gz")) die("ERROR: Will be imposible to download the addon. A file already exist and can't be deleted.");
 
   
if( !file_put_contents("/home/aces/tmp/{$addon_name}.tar.gz", fopen("http://acescript.ddns.net/download/$addon_name", 'r')) ) die("ERROR: Could not download addon file.");

chdir("/home/aces/tmp/");
exec("tar xf {$addon_name}.tar.gz");
if(!is_dir("/home/aces/tmp/$addon_name/")) die("Error extracting the addon file.");
if(!chdir("$addon_name/")) die("ERROR: Fail installing the addon file.");

include 'install.php';

//REMOVING FILES.
unlink("/home/aces/tmp/{$addon_name}.tar.gz");
exec("rm -rf /home/aces/tmp/$addon_name/");

die;