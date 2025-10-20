#!/usr/bin/env php
<?php

//error_reporting(0);
//date_default_timezone_set('UTC');

//if(!file_exists('/home/aces/panel/includes/config.php')) DIE('NO PANEL INSTALLED HERE.. EXIT');

//ok edit

if(file_exists('/home/aces/panel/includes/config.php')) {

    include '/home/aces/panel/includes/config.php';
    if(empty($config['database']['pass']) || empty($config['database']['user']) || empty($config['database']['name']) ) die('COULD NOT GET DATABASE INFO FROM CONFIG.');

} else {

    include '/home/aces/stream/config.php';
    $config['database']['host'] = $DBHOST; $config['database']['user'] = $DBUSER; $config['database']['pass'] = $DBPASS; $config['database']['name'] = $DATABASE;

}

$DB = new mysqli($config['database']['host'],$config['database']['user'],$config['database']['pass'],$config['database']['name']);
if($DB->connect_errno > 0) {
    error_log("Unable to connect to database.", 3, "/home/aces/logs/aces-backup.log");
    DIE("Unable to connect to database.");
}

if (!file_exists('/home/aces/backups/')) {
    if(!mkdir('/home/aces/backups/', 0770, true)) {
        error_log("UNABLE CRATE BACKUPS DIRECTORY", 3, "/home/aces/logs/aces-backup.log");
        DIE('COULD NOT CRATE BACKUPS DIRECTORY');
    }
}

if(!empty($argv[1]) ) $BACKNAME = $argv[1];
else $BACKNAME =  "Backup".date("YFd_H-i-s");

//if(file_exists("/home/aces/backups/$BACKNAME.tar.gz")) sleep(2);
if(file_exists("/home/aces/backups/$BACKNAME.tar.gz"))
    DIE('THERE IS AN BACKUP WITH THIS NAME. WILL NOT OVERWRITE!.');


if(file_exists("/home/aces/backups/$BACKNAME"))
    exec("rm -rf /home/aces/backups/$BACKNAME");

if(!mkdir("/home/aces/backups/$BACKNAME", 0770, true))
    DIE('COULD NOT WRITE ON BACKUP DIRECTORY');

//exec("cp -rf /home/aces/bin /home/aces/panel /home/aces/stream/  /home/aces/backups/$BACKNAME ");
exec (" mysqldump --single-transaction --quick --lock-tables=false --add-drop-table -h {$config['database']['host']} -u {$config['database']['user']} -p{$config['database']['pass']} {$config['database']['name']} > /home/aces/backups/$BACKNAME/database.sql ",$o,$s);
if($s>0) { error_log("FAIL CREATING BACKUP", 3, "/home/aces/logs/aces-backup.log");  die("FAIL CREATING BACKUP."); }

exec ("  tar -czf /home/aces/backups/$BACKNAME.tar.gz -C /home/aces/backups/$BACKNAME . ");

exec("rm -rf /home/aces/backups/$BACKNAME");
