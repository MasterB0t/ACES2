<?php

header('Content-Type: application/json');
header('Connection: close');

$r = $DB->query("SELECT bouquets,token FROM iptv_devices WHERE id = '$MAG_ACCOUNT_ID' ");
$device=mysqli_fetch_array($r);

$device['bouquets'] = join("','", unserialize($device['bouquets']) );

$SQL_ADULTS = '';
if($MAG_ADULTS == 0) $SQL_ADULTS = ' AND cat.adults = 0 ';



$sql_type = '';
if(empty($MAG_EXTRA_OPTIONS['no_series_club'])) { 

    $ordering = 'cat.m_ordering';

    if($_GET['type'] == 'series') { $ordering = 'cat.s_ordering'; $sql_type = "AND o.type = 'series'"; }
    else $sql_type = "AND o.type = 'movies'";
    
}
    
//$r=$DB->query("SELECT cat.id as cat_id, cat.name as category_name, cat.adults  FROM iptv_ondemand_in_bouquet p INNER JOIN iptv_ondemand o ON ( o.id = p.video_id  $sql_type )
//                RIGHT JOIN iptv_stream_categories cat ON o.category_id = cat.id 
//                WHERE o.enable = 1  AND o.status = 1 AND  p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS GROUP BY cat.id ORDER BY cat.ordering ");

$r=$DB->query("SELECT cat.name as category_name,cat.id as cat_id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        RIGHT JOIN iptv_ondemand_in_bouquet p on o.id = p.video_id
        WHERE o.status = 1 $sql_type AND  p.bouquet_id IN ('{$device['bouquets']}') $SQL_ADULTS
        GROUP BY cat.id ORDER BY $ordering
");

$data = array();
$data[] = array (
      'id' => '*',
      'title' => 'All',
      'alias' => '*',
      'censored' => 0,
);

//$data[] = array (
//      'id' => 'movies',
//      'title' => 'Movies',
//      'alias' => '*',
//      'censored' => 0,
//);

//if(empty($CONFIG['hide_series_cat'] )) { 
//
//    $data[] = array (
//          'id' => 'series',
//          'title' => 'Series',
//          'alias' => '*',
//          'censored' => 0,
//    );
//    
//}

//$data[] = array (
//      'id' => 'replay',  
//      'title' => 'Replay',
//      'alias' => '*',
//      'censored' => 0,
//);
//
//$data[] = array (
//      'id' => 'adults',
//      'title' => 'Adults',
//      'alias' => '*',
//      'censored' => 1,
//);

while($row=mysqli_fetch_assoc($r)) { 
    
    $cen = 0;
    if($row['adults'] == 1 && $MAG_ADULT_PIN ) $cen = 1;
    
    $d = array (
      'id' => $row['cat_id'],
      'title' => $row['category_name'],
      'alias' => $row['category_name'],
      'censored' => $cen,
    );
    
    $data[] = $d;
    
}

$js= array ( 'js' => $data );
  


echo json_encode($js);
die; 