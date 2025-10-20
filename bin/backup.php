#!/usr/bin/env php
<?php

// /home/aces/logs/aces-backup.log

require '/home/aces/stream/config.php';

function err($msg) {
    die($msg."\n");
}

if (!file_exists('/home/aces/backups/')) {
    if(!mkdir('/home/aces/backups/', 0770, true)) {
        err('COULD NOT CRATE BACKUPS DIRECTORY');
    }
}

$DB = new \mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) {
    err("Unable to connect to database.");

}

if(!empty($argv[1]) ) $BACKNAME = $argv[1];
else $BACKNAME =  "Backup".date("YFd_H-i-s");

if(file_exists("/home/aces/backups/$BACKNAME.sql.gz"))
    err('THERE IS AN BACKUP WITH THIS NAME. WILL NOT OVERWRITE!.');

//if(!mkdir("/home/aces/backups/$BACKNAME", 0770, true))
//    err('COULD NOT WRITE ON BACKUP DIRECTORY');

exec (" mysqldump --single-transaction --quick --lock-tables=false --add-drop-table -h {$DBHOST} -u {$DBUSER} -p{$DBPASS} {$DATABASE} | gzip > /home/aces/backups/$BACKNAME.sql.gz ",
    $o,$s);

if($s != 0 ) {
    unlink("/home/aces/backups/$BACKNAME.sql.gz");
    err("FAIL CREATING BACKUP WITH STATUS $s ");
}


//SCANNING BACKUPS.
$DB->query("DELETE FROM backups");
foreach(glob("/home/aces/backups/*.gz" ) as $file ) {
    $size_kb = filesize($file) / 1024;
    $filename = str_replace("/home/aces/backups/", "", basename($file));
    $create_time = filectime($file);
    $DB->query("INSERT INTO backups (create_time, name, filesize_kb)  
        VALUES('$create_time', '$filename', '$size_kb') ");
}

//UPLOAD BACKUPS.
$r=$DB->query("SELECT * FROM backup_locations ");
while($row=$r->fetch_assoc()) {

    $id = $row['id'];
    $err_msg = '';

    switch($row['protocol'])  {

        case 'dir':
            if(!copy("/home/aces/backups/$BACKNAME.sql.gz", "{$row['location']}/$BACKNAME.sql.gz"))  {
                $err_msg = "Unable to copy last backup to directory.";
            }
            break;

        case 'scp':
            list($ip,$location) = explode(':',$row['location']);

            if(!$connection = ssh2_connect($ip, 22)) {
                $err_msg = "Could not connect to server";
                break;
            }


            if(!ssh2_auth_password($connection, $row['username'], $row['password'] )) {
                $err_msg = "Unable to connect to server '$ip'. Wrong password or username. ";
                break;
            }

            if(!ssh2_scp_send($connection, "/home/aces/backups/$BACKNAME.sql.gz", "$location/$BACKNAME.sql.gz", 0640)) {
                $err_msg = "Unable to send backup to server.";
                break;
            }



            break;

    }


    $DB->query("UPDATE backup_locations SET error_msg = '$err_msg' WHERE id = '$id'");

}
