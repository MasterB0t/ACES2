<?php
//DEPRECATED?
die;

include '/home/aces/panel/includes/init.php';

$ACES = new IPTV();

$json = array();
set_time_limit(0);
error_reporting(E_ERROR | E_PARSE);

$ADDR = $_SERVER['HTTP_HOST'];




$json['server_timezone'] = date_default_timezone_get();
$json['server_time'] = time();
$json['server_time_offset'] = date('Z');


//GETTING ACCOUNT BY ROKU SERIAL
//if(isset($_GET['rokuserial'])) {
//
//	if(empty($_GET['rokuserial'])) {
//		$json['status'] = 'error';
//		$json['error'] = $ERRORS[101];
//		$json['error_code'] = 101;
//	} else if(!preg_match('/^[a-zA-Z0-9-]+$/',trim($_GET['rokuserial']))) {
//		$json['status'] = 'error';
//		$json['error'] = $ERRORS[102];
//		$json['error_code'] = 102;
//	} else {
//		
//		$roku_serial = $ACES->escString($_GET['rokuserial']);
//		
//		if($_GET['action'] == 'register_roku'){
//			
//			$r=$ACES->query("SELECT id,package_id,adults,token,subcription,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM $ACES->_tb_devices WHERE roku_serial = '$roku_serial' ");
//			if(mysqli_fetch_array($r)) { 
//				
//				$json['status'] = 'error';
//				$json['error'] = $ERRORS[201];
//				$json['error_code'] = 201;
//				echo json_encode($json); die;
//				
//			} else {
//				
//				$_GET['token'] = $ACES->escString($_GET['token']);
//				$r=$ACES->query("SELECT id,package_id,adults,token,subcription,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM $ACES->_tb_devices WHERE token = '{$_GET['token']}' AND roku_serial = '' ");
//				if(!$row=mysqli_fetch_array($r)) {
//					
//					$json['status'] = 'error';
//					$json['error'] = $ERRORS[202];
//					$json['error_code'] = 202;
//					echo json_encode($json); die;
//					
//				} else {
//					$id = (int)$row['id'];
//					$ACES->query(" UPDATE $ACES->_tb_devices SET roku_serial = '$roku_serial' WHERE id=$id ");
//					if($ACES->get_setting('iptv.rokulockagent')) $ACES->query(" UPDATE $ACES->_tb_devices SET lock_user_agent = 1 WHERE id=$id ");
//					$json['status'] = 'complete';
//					echo json_encode($json); die;
//				}
//				
//			}
//				
//			
//		} else { 
//		
//			
//			$r=$ACES->query("SELECT id,package_id,adults,token,subcription,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expire_in FROM $ACES->_tb_devices WHERE roku_serial = '$roku_serial' ");
//			if(!$DEVICE=mysqli_fetch_array($r)) {
//			
//				$json['status'] = 'error';
//				$json['error'] = $ERRORS[103];
//				$json['error_code'] = 103;
//			
//			
//			} else {
//	
//				if($DEVICE['expire_in'] < 1 ){
//					
//					$json['status'] = 'error';
//					$json['error'] = $ERRORS[104];
//					$json['error_code'] = 104;
//					
//				} else { 
//					//ACCOUNT IS NOT EXPIRE. OVERWRITING INFO FOR LINK
//					$_GET['token'] = $DEVICE['token'];
//					
//				}
//			}
//			
//		}
//	}
//	
////GETTING DEVICE BY TOKEN
//} else {

	if(empty($_GET['token']))  {
		$json['status'] = 'error';
		$json['error'] = $ERRORS[101];
		$json['error_code'] = 101;
	} else if(!preg_match('/^[a-zA-Z0-9-]+$/',trim($_GET['token']))) {
		$json['status'] = 'error';
		$json['error'] = $ERRORS[102];
		$json['error_code'] = 102;
	} else {
		$r=$ACES->query("SELECT id,package_id,bouquets,adults FROM $ACES->_tb_devices WHERE token = '{$_GET['token']}' AND subcription >= CURDATE()  ");
		if(!$DEVICE=mysqli_fetch_array($r)) {
				
			$json['status'] = 'error';
			$json['error'] = $ERRORS[103];
			$json['error_code'] = 103;
	
		}
	} 
//}


if(@$json['status'] == 'error') {echo json_encode($json); die; } 

$DEVICE_ID = $DEVICE['id'];
$DEVICE_PACKAGE = $DEVICE['package_id'];
$DEVICE_BOUQUETS = join("','", unserialize($DEVICE['bouquets']) );

$DEVICE_ADULTS = $DEVICE['adults'];

if(isset($_GET['format'])) $_GET['format'] = strtolower($_GET['format']);

if($_GET['action'] == 'get_info') { 
	
	//GETTING DEVICE INFO
	$r2=$ACES->query("SELECT * FROM $ACES->_tb_devices WHERE id = $DEVICE_ID  ");
	if($device=mysqli_fetch_array($r2)) { 

		$json['status'] = 'complete';
		$json['device_info']['id'] = $device['id'];
		$json['device_info']['token'] = $device['token'];
		$json['device_info']['expire_on'] = $device['subcription'];
		$json['device_info']['limit_connections'] = $device['limit_connections'];
		$json['device_info']['allow_adults'] = $device['adults'];
		$json['device_info']['adults_lock'] = $device['adults_with_pin'];
		$json['device_info']['allow_formats'] = 'mpegts,hls';
		$json['device_info']['status'] = 'active';
		
		
	}
	

} else if($_GET['action'] == 'get_streams_categories') { 
	
	//$r2=$ACES->query("SELECT cat.name,cat.id FROM $ACES->_tb_stream_categories cat
	//		INNER JOIN $ACES->_tb_channels o ON cat.id = o.category_id 
	//		INNER JOIN $ACES->_tb_channels_in_bouquet p ON p.package_id = $DEVICE_PACKAGE  AND p.chan_id = o.id
	//		GROUP BY cat.name ORDER BY cat.id");
	
	$r2=$ACES->query("SELECT cat.name as name,cat.id as id FROM $ACES->_tb_channels_in_bouquet p 
			LEFT JOIN $ACES->_tb_channels chan on chan.id = p.chan_id
			RIGHT JOIN $ACES->_tb_stream_categories cat on cat.id = chan.category_id
			WHERE p.bouquet_id IN ('{$DEVICE_BOUQUETS}') AND chan.enable = 1
			GROUP BY cat.id ORDER BY cat.name
			
	");
	

	
	while($row=mysqli_fetch_array($r2)) {
	
		$arr=null;
		$arr['id'] = $row['id'];
		$arr['name'] = $row['name'];
		$arr['logo'] = '';
		$json['categories'][] = $arr;
	
	}
	
	
} else if($_GET['action'] == 'get_streams') { 
	
	
		set_time_limit ( 0 );
		include_once '/home/aces/panel/class/EpgParser.php';
		//$epg = new EgpParser('/home/aces/guide/guide.xml');

                ini_set('memory_limit', '-1');
                $jef = '/home/aces/guide/guide.json';
                $json_epg = json_decode(file_get_contents($jef),true);

        
	
		if(!empty($_GET['order_by'])) {
			
			if($_GET['order_by'] == 'name') $orderby = 'ORDER BY c.name';
			else if($_GET['order_by'] == 'category') $orderby = 'ORDER BY p.i';
			else if($_GET['order_by'] == 'recent') $orderby = 'ORDER BY c.id DESC ';
			else if($_GET['order_by'] == 'oldest') $orderby = 'ORDER BY c.id ASC ';
			else $orderby = 'ORDER BY p.i';
			
		} else $orderby = 'ORDER BY p.i';
		
		
		$filter = ' ';
		if(!empty($_GET['category'])) {
			
			$filter = " AND lower(cat.name) = '".strtolower($ACES->escString($_GET['category'])). "'";
		}
		
		$cache = new cache();
		$cache->cache=0;
		$cache->time = 60*60;
		$cache->file = "iptv_api_device_get_streams_p{$DEVICE_PACKAGE}_format{$_GET['format']}_cat{$_GET['category']}_order{$_GET['order_by']}";
		

		if(!$streams=json_decode($cache->getCache(),true)) {
		
			
			$r2 = $ACES->query("SELECT c.*, cat.name as category_name, cat.adults  FROM $ACES->_tb_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )
					LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id
					WHERE c.enable = 1 AND p.bouquet_id IN ('$DEVICE_BOUQUETS') $filter
					GROUP BY c.id
					$orderby
						
			");
			
			
			while($row=mysqli_fetch_array($r2)) { 
				
				
				$arr=null;
				if($row['adults'] && $DEVICE['adults'] || !$row['adults']) { 
					
					$arr['id'] = $row['id'];
					$arr['name'] = $row['name'];
					$arr['category'] = $row['category_name'];
					if($row['adults'] == 1 )$arr['adults_category'] = 1;
					else $arr['adults_category'] = 0;
					
					if(!empty($row['logo'])) $arr['logo'] = "http://$ADDR/logos/{$row['logo']}";
					else $arr['logo'] = '';
					
					if($_GET['format'] == 'hls' ) $arr['url'] = "http://$ADDR/load/{$_GET['token']}/{$row['id']}.m3u8";
					else $arr['url'] = "http://$ADDR/load/{$_GET['token']}/{$row['id']}.mpegts";
					
					if(isset($_GET['with_epg'])) {
                        
                        

                                            $max=0;$ap=array();
                                            foreach($json_epg[$arr['id']] as $x => $v) { 
                                                if($max >= 6 )break;

                                                if($max == 0) {
                                                    if(time() >= strtotime($v['start']) && time() < strtotime($v['end']) ) {  array_push($ap,$v);$max++; } }
                                                else if($max > 0) {
                                                    if(time() < strtotime($v['end'] )) { array_push($ap,$v);$max++; } }

                                            }
                                            $arr['epg'] = $ap;

                                            //$arr['epg'] = $json_epg[$arr['id']];

					}
					
					$json['streams'][] = $arr;
					$cache->setCache(json_encode($json));
					
				}
				
			}
		} else $json['streams'] = $streams;
	
} else if($_GET['action'] == 'get_ondemand' ) { 
	
	
	if(!empty($_GET['order_by'])) {
	
		if($_GET['order_by'] == 'name') $orderby = 'ORDER BY o.name';
		else if($_GET['order_by'] == 'category') $orderby = 'ORDER BY cat.name';
		else if($_GET['order_by'] == 'recent') $orderby = 'ORDER BY o.id DESC ';
		else if($_GET['order_by'] == 'oldest') $orderby = 'ORDER BY o.id ASC ';
		else $orderby = 'ORDER BY p.i';
	
	} else $orderby = 'ORDER BY o.id DESC';
	
	$filter = ' o.enable = 1  AND o.status = 1 ';
	if(!empty($_GET['category'])) {
	
		$filter .= " AND lower(cat.name) = '".strtolower($ACES->escString($_GET['category'])). "'";
	}
	
	if(!empty($_GET['type']) && in_array(strtolower($_GET['type']),array('movies','series','replay','adults','documentaries')) ) {
		
		$filter .= " AND lower(o.type) = '".strtolower($_GET['type'])."' ";
		
	}
	
	
	if(!empty($_GET['genre'])) { 
		
		$c_f = $filter;
		
		$filter .= " AND lower(o.genre1) =  '".strtolower($ACES->escString($_GET['genre']))."'";
		
		$filter .= " OR $c_f AND lower(o.genre2) =  '".strtolower($ACES->escString($_GET['genre']))."'";
		
		$filter .= " OR $c_f AND lower(o.genre3) =  '".strtolower($ACES->escString($_GET['genre']))."'";
		
	}
	

	$r2=$ACES->query("SELECT o.*,cat.name as category_name, cat.adults FROM iptv_ondemand o
			LEFT JOIN iptv_stream_categories cat ON o.category_id = cat.id
			WHERE  $filter $orderby ");
	
	while($row=mysqli_fetch_array($r2)) {
	
			$arr=null;
		
			$arr['id'] = $row['id'];
			$arr['name'] = $row['name'];
			$arr['category'] = $row['category_name'];
			if($row['adults'] == 1 )$arr['adults_category'] = 1;
			else $arr['adults_category'] = 0;
			$arr['year'] = $row['year'];
			$arr['type'] = $row['type'];
			$arr['genre1'] = $row['genre1'];
			$arr['genre2'] = $row['genre2'];
			$arr['genre3'] = $row['genre3'];
			$arr['description'] = $row['about'];
		
			if(!empty($row['logo'])) $arr['logo'] = "http://$ADDR/logos/{$row['logo']}";
			else $arr['logo'] = '';
		
			$arr['url'] = "http://$ADDR/load/movie/{$_GET['token']}/{$row['id']}.m3u8";
		
		
			$json['ondemands'][] = $arr;
	
	}
	
	
} else if($_GET['action'] == 'get_ondemand_categories') { 
	
	$filter = '';
	if(!empty($_GET['type']) && in_array(strtolower($_GET['type']),array('movies','series','replay','adults','documentaries')) ) {
	
		$filter .= " AND lower(o.type) = '".strtolower($_GET['type'])."' ";
	
	}
	
	$r2=$ACES->query("SELECT cat.name,cat.id from $ACES->_tb_stream_categories cat 
			INNER JOIN $ACES->_tb_ondemand o ON cat.id = o.category_id AND o.status = 1 $filter 
			GROUP BY cat.name ORDER BY cat.name");
	
	while($row=mysqli_fetch_array($r2)) { 
		
		$arr=null;
		
		$arr['id'] = $row['id'];
		$arr['name'] = $row['name'];
		$json['categories'][] = $arr;
		
	}
	
} else if($_GET['action'] == 'get_ondemand_genres') { 
	
	
		$filter = ' enable = 1  AND status = 1 ';
		if(!empty($_GET['type']) && in_array(strtolower($_GET['type']),array('movies','series','replay','adults','documentaries')) ) {
		
			$filter .= " AND lower(type) = '".strtolower($_GET['type'])."' ";
		
		}
	
	
		$r2=$ACES->query("SELECT genre1,genre2,genre3  FROM $ACES->_tb_ondemand  
			WHERE $filter
		");
		
	
		$a_g=array();
		while($row=mysqli_fetch_array($r2))  {
			
			if( $row['genre1'] && !in_array($row['genre1'],$a_g)) $a_g[] = $row['genre1'];
			if( $row['genre2'] && !in_array($row['genre2'],$a_g)) $a_g[] = $row['genre2'];
			if( $row['genre3'] && !in_array($row['genre3'],$a_g)) $a_g[] = $row['genre3'];

		}
			
		$json['genres']=$a_g;
                
} else if($_GET['action'] == 'get_series') { 
    
    if(!empty($_GET['season'])) {

        $r3 = $ACES->query("SELECT e.id,e.title,e.number,e.about,v.logo FROM $ACES->_tb_series_episodes e "
                . "INNER JOIN $ACES->_tb_series_seasons s ON s.id = e.season_id "
                . "INNER JOIN $ACES->_tb_ondemand v on v.id = s.series_id "
                . "WHERE e.season_id = {$_GET['season']} AND e.status = 1  ORDER BY e.number ");
        
        
    }
    
    else if(!empty($_GET['series'])) {
        
        $r3 = $ACES->query("SELECT s.* FROM $ACES->_tb_series_seasons s "
            . "INNER JOIN $ACES->_tb_series_episodes e ON e.season_id = s.id AND e.status = 1 "
            . "WHERE s.series_id = {$_GET['series']}  GROUP BY s.id  ");
    
    } else $r3 = $ACES->query("SELECT * FROM $ACES->_tb_ondemand WHERE type = 'series' ");
    
    while($row=mysqli_fetch_array($r3)) { 
        
        if(!empty($row['logo'])) $row['logo'] = "http://$ADDR/logos/{$row['logo']}";
        
        $arr[] = $row;
    }
    
    $json['series'] = $arr;
    
} else if($_GET['action'] == 'get_epg') { 
	

    $file = "/home/aces/guide/guide.json";
    if(is_file($file)) { 

            ob_start();

            echo '{"server_timezone":"'.$json['server_timezone'].'","server_time":'.$json['server_time'].',"server_time_offset":"'.$json['server_time_offset'].'","epg":';

            $chunkSize = 1024 * 1024 ;
            $handle = fopen("$file", 'r');
            while (!feof($handle))
            {
                    $buffer = fread($handle, $chunkSize);
                    echo $buffer;
                    ob_flush();
                    flush();

            }
            fclose($handle);

            echo "}";
            die;


    }
		
	
} else if($_GET['action'] == 'get_epg_from_stream') { 
	
	include_once '/home/aces/panel/class/EpgParser.php';
	$epg = new EgpParser('/home/aces/guide/guide.xml');
	$epg->max_programme = 1;
	
	$xml_id =$epg->findChannelByName('TBS');
	$json['epg'] = $epg->getProgramme($xml_id);
        	
	foreach($json['epg'] as $v) { var_dump($v); die; } 
	
	
	
}

echo json_encode($json);
die;


