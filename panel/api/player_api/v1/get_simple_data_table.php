<?php

include 'main.php';

$stream_id = (int)$_GET['stream_id'];

if(!$stream_id) {
    echo '[]'; exit;
}



$OFFSET=0;
$SERVER_TIMEZONE = date_default_timezone_get();

//$r_ip = $DB->query("SELECT timezone FROM iptv_ip_info WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
//if($row=mysqli_fetch_array($r_ip)) {
//    if($SERVER_TIMEZONE != $row['timezone'] && !empty($row['timezone']) ){ 
//        date_default_timezone_set($row['timezone']);
//        $OFFSET = date('Z');
//    }
//}


$rr=$DB->query("SELECT e.*,NOW() as d,c.name FROM iptv_epg e RIGHT JOIN iptv_channels c ON c.id = '$stream_id'
                             WHERE e.chan_id = '$stream_id' ");

$d=array();$i=1;$time=time();
while($row=$rr->fetch_assoc()) {

    $title=base64_encode($row['title']);
    $desc= base64_encode($row['description']);
    
    $start_time = (strtotime($row['start_date'])+$OFFSET);
    $end_time = (strtotime($row['end_date'])+$OFFSET);
    
    $r2=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '$stream_id' 
                                AND start_time = '{$row['start_date']}' AND status = 3  ");
    //if($row2=mysqli_fetch_array($r2)) { $archived = 1; }
    $archived = $r2->fetch_assoc() ? 1 : 0;


    $now_playing = $time  > $start_time && $time < $end_time ? 1 : 0;
    $i++;
    $d[] = array (
          'id' => "$i",
          'epg_id' => "$stream_id",
          'title' => $title,
          'lang' => "",
          'start' => date("Y-m-d H:i:s",$start_time),
          'end' => date("Y-m-d H:i:s",$end_time),
          'description' => $desc,
          'channel_id' => $row['name'],
          'start_timestamp' => "$start_time",
          'stop_timestamp' => "$end_time",
          'now_playing' => $now_playing,
          'has_archive' => $archived,
    );

}

header('Content-Type: application/json');
echo json_encode( array ( 'epg_listings' => $d ) ) ;
exit;