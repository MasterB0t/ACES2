<?php

include 'main.php';

$categories = array();

$SQL = '';$SQL .= ' AND cat.adults = 0 ';
//if($ACCOUNT['adults'] == 0 ) $SQL .= ' AND cat.adults = 0 ';

if(isset($_GET['type'])) {

    $ordering = 's_ordering';
    if($_GET['type'] == 'movies' )  { $SQL .= "AND o.type = 'movies' "; $ordering = 'm_ordering'; }
    else if($_GET['type'] == 'series' ) $SQL .= "AND o.type = 'series' ";

}


$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 $SQL AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}')
        GROUP BY cat.id ORDER BY $ordering
");

while($row=$r->fetch_assoc($r)) { $categories[] = array('id' => $row['id'], 'name' => $row['name']  ) ; }

echo json_encode($categories);
