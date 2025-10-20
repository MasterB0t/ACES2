<?php
//DEPRECATED ???
die ;

ini_set('memory_limit', '-1');
chdir("/home/aces/panel");
set_time_limit ( 0 );

include_once '/home/aces/panel/includes/init.php';
include_once '/home/aces/panel/class/EpgParser.php';



$ACES = new IPTV();
$epg = new EgpParser('/home/aces/guide/guide.xml');

$file = "/home/aces/guide/guide.json";

$r = $ACES->query("SELECT c.id, c.name, c.tvg_id FROM $ACES->_tb_c  p 
		INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )
		WHERE c.enable = 1 
		GROUP BY c.id
		ORDER BY p.i
");

while($row=mysqli_fetch_array($r)) { 
	$epg_id=NULL;
	if($row['tvg_id']) $epg_id = $row['tvg_id'];
	else $epg_id = $epg->findChannelByName( $row['name'] );
	
	if($epg_id) {
		$json['epg'][$row['id']] = $epg->getProgramme($epg_id);
		foreach ( $json['epg'][$row['id']] as $i => $v ) {
			#var_dump($json['epg'][$row['id']][$i]);die;
			unset($json['epg'][$row['id']][$i]['category'],$json['epg'][$row['id']][$i]['date'],$json['epg'][$row['id']][$i]['new']);
			unset($json['epg'][$row['id']][$i]['credits'],$json['epg'][$row['id']][$i]['episode-num'],$json['epg'][$row['id']][$i]['previously-shown'],$json['epg'][$row['id']][$i]['subtitles'],$json['epg'][$row['id']][$i]['rating'],$json['epg'][$row['id']][$i]['audio']);}   
	}
		

		
	
}


$text = json_encode($json['epg']);
//$text = iconv(mb_detect_encoding($text), "UTF-8", $text);
#$text = iconv("utf-8", "utf-8//ignore", $text
file_put_contents($file,  $text  );
