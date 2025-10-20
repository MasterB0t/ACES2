<?php

#exec("crontab -u aces -l | grep con.php",$o,$s);
#echo $s;exit;

$PID_FILE = "/home/aces/run/iptv_conns.pid";
$PID=0;
if(is_file($PID_FILE)) {

    //THERE ARE A PID FILE LET TRY TO READ PID FROM THE FILE.
    if(! ($PID = file_get_contents($PID_FILE)) ) {  die; }

    //WE HAVE PID # LET CHECK IF STILL RUNNING
    if(posix_getpgid($PID) && getmypid() != $PID ) {  die; }

}

if( getmypid() != $PID )
    if(! (file_put_contents($PID_FILE,getmypid()) ) ) {  DIE; }

include '/home/aces/stream/config.php';

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }

//function setStats($connection_id) {
//
//    global $DB;
//    $r = $DB->query("SELECT chan_id,device_id,ip_address,add_date FROM iptv_access WHERE  id = $connection_id ");
//    $access = $r->fetch_assoc();
//
//    if(!$access['chan_id'])
//        return;
//
//    $DB->query("UPDATE iptv_access SET chan_id = 0 WHERE id = $connection_id");
//
//}


$z=0;
while(true) {

	clearstatcache();

	$files = @scandir("/home/aces/run/conns/");

    //BECAUSE scandir() return '.' and '..' directory let remove it.
	unset($files[0],$files[1]);


	$i=0;
    if(is_array($files))
	foreach ( $files as $con ) {

		if( $con  ) {

			$r=$DB->query("SELECT id,message FROM  iptv_access  WHERE id = $con ");
			if(!$row=$r->fetch_assoc()) { unlink("/home/aces/run/conns/".$con); }
			else  {

                if( (fileatime("/home/aces/run/conns/".$con) + 60 ) < time() ) {
                    unlink("/home/aces/run/conns/".$con);
                    $DB->query("DELETE FROM iptv_access WHERE id = $con ");
                }

				if(!empty($row['message']) && !is_file("/home/aces/run/smsg/$con") )
					touch("/home/aces/run/smsg/$con");
				if(!$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 120 SECOND WHERE id = $con ")) echo "SQL FAIL\n";

			}
		}

		$i++;
	}


	sleep(10);
	$z++;
	echo $z;
}

if($SERVER_ID ==  1) {
    $DB->query("DELETE FROM iptv_access WHERE now() > end_time  AND vod_id > 0 ");
}

unlink($PID_FILE);
