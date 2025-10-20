<?php

include 'main.php';

$categories = array();

$SQL_ADULTS = '';
if($ACCOUNT['adults'] == 0 ) $SQL_ADULTS = ' AND cat.adults = 0 ';

$r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_channels_in_bouquet p 
        LEFT JOIN iptv_channels chan on chan.id = p.chan_id
        RIGHT JOIN iptv_stream_categories cat on cat.id = chan.category_id
        WHERE p.bouquet_id IN ('{$ACCOUNT['bouquets']}') AND chan.enable = 1 $SQL_ADULTS
        GROUP BY cat.id ORDER BY cat.ordering,cat.name
");
        
//$r=$ACES->query("SELECT id,name FROM $ACES->_tb_stream_categories ");
//while($row=mysqli_fetch_array($r)) { $categories[] = array('category_id' => $row['id'], 'category_name' => mb_convert_encoding( utf8_encode($row['name']), "UTF-8", "auto"), 'parent_id' => 0 ) ; }
while($row=mysqli_fetch_array($r)) { $categories[] = array('category_id' => $row['id'], 'category_name' => $row['name'], 'parent_id' => 0 ) ; }

        
        
echo json_encode($categories);
if(json_last_error()) AcesLogE("Json ERROR ". json_last_error_msg());