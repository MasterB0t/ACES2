<?php

$r = $DB->query("SELECT bouquets,adults FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=$r->fetch_assoc();

$device['bouquets'] = join("','", unserialize($device['bouquets']) );
$sql = '';
if($device['adults'] == 0 ) $sql  = " AND cat.adults = 0 ";
$r=$DB->query("SELECT c.category_id, cat.name as category_name, cat.adults as adults  FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
                LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 	
                WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $sql
                GROUP BY c.category_id
                ORDER BY cat.ordering,cat.id ");
                

$data = array();

$d['id'] = '*';
$d['title'] = 'All Channels';
$d['alias'] = 'all';
$d['censored'] = 0;
$data[] = $d;

$n = 0;
while($row=mysqli_fetch_array($r)) { 
    $n++;
    $d['id'] = $row['category_id'];
    $d['title'] = $row['category_name'];
    $d['modified'] = '';
    $d['number'] = $n;
    $d['alias'] = $row['category_name'];
    if($row['adults'] == 1 && $MAG_ADULT_PIN ) $d['censored'] = 1;
    else $d['censored'] = 0;
    $data[] = $d;
}

$json['js'] = $data;

echo json_encode($json);
die;
                