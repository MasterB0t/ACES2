<?php

include 'main.php';

$stream_id = (int)$_GET['stream_id'];

if(!$stream_id) {
    echo '[]'; exit;
}


$OFFSET=0;
$SERVER_TIMEZONE = date_default_timezone_get();

$r_ip = $DB->query("SELECT timezone FROM iptv_ip_info WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
if($row=mysqli_fetch_array($r_ip)) {
    if($SERVER_TIMEZONE != $row['timezone'] && !empty($row['timezone']) ) { 
        date_default_timezone_set($row['timezone']);
        $OFFSET = date('Z');
    }
}
    


$rr=$DB->query("SELECT *,NOW() as d FROM iptv_epg 
                    WHERE chan_id = '$stream_id' and NOW() < end_date LIMIT 4 ");

$d=array();$i=0;
while($row=$rr->fetch_assoc()) {

    $title=base64_encode($row['title']);
    $desc= base64_encode($row['description']);

    $start_time = (strtotime($row['start_date'])+$OFFSET);
    $end_time = (strtotime($row['end_date'])+$OFFSET);
    
    $archived = 0; $r_id = 0;
    //$r2=$DB->query("SELECT id FROM iptv_recording WHERE chan_id = '{$_GET['stream_id']}' AND start_time = '{$row['start_date']}' AND status = 3 LIMIT 4 ");
    //if($row2=mysqli_fetch_array($r2)) { $r_id = $row2['id']; $archived = 1; } 

    $i++;
    $d[] = array (
          'id' => $i,
          'epg_id' => $row['id'],
          'title' => $title,
          'lang' => 'en',
          'start' => date("Y-m-d H:i:s",$start_time),
          'end' => date("Y-m-d H:i:s",$end_time),
          'description' => $desc,
          'channel_id' => $row['tvg_id'],
          'start_timestamp' => "$start_time",
          'stop_timestamp' => "$end_time",
        //  'now_playing' => 0,
        //  'has_archive' => $archived,
    );

}

echo json_encode( array ( 'epg_listings' => $d ) ) ;
exit;