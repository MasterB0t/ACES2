<?php

$username = $_GET['username'];
$password = $_GET['password'];

$ADDR = $_SERVER['HTTP_HOST'];
$PRTC = 'http:';
if( isset($_SERVER['HTTPS'] ) )  $PRTC = 'https:';

require_once $_SERVER['DOCUMENT_ROOT'].'/functions/logs.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ACES2/DB.php";
require_once $_SERVER['DOCUMENT_ROOT']."/class/Cache.php";

$ACES = new \ACES2\DB;
//$ARMOR = new Armor;

//if(empty($password) || !preg_match('/^[a-zA-Z0-9-]+$/',$password) ||  $ARMOR->isBlock('iptv-account') || empty($username) || !preg_match('/^[a-zA-Z0-9-]+$/',$username) )  die;
if(empty($password) || !preg_match('/^[a-zA-Z0-9-]+$/',$password) ||  empty($username) || !preg_match('/^[a-zA-Z0-9-]+$/',$username) )  die;


$r = $ACES->query("SELECT id,user_id,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expiration,bouquets,adults FROM iptv_devices WHERE username = '$username' AND token = '$password' ");

if(!$device=mysqli_fetch_array($r)) {
    //$ARMOR->action('iptv-account');
    sleep(2);die; }
if($device['expiration'] < 1 ) die; 

$device['bouquets'] = join("','", unserialize($device['bouquets']) );

$SQL_ADULTS = '';
if ($device['adults'] == 0) $SQL_ADULTS = ' AND cat.adults = 0 ';


if($_GET['type'] == 'get_live_categories') {

    $r = $ACES->query("SELECT cat.name as name,cat.id as id FROM iptv_channels_in_bouquet p 
            LEFT JOIN iptv_channels chan on chan.id = p.chan_id
            RIGHT JOIN iptv_stream_categories cat on cat.id = chan.category_id
            WHERE p.bouquet_id IN ('{$device['bouquets']}') AND chan.enable = 1 $SQL_ADULTS
            GROUP BY cat.id ORDER BY cat.ordering,cat.name
    ");

    echo "Live [ My_Bouquet_Name ]";
    echo base64_encode('ALL Live') . "0$PRTC//$ADDR/enigma2.php?username=$username&password=$password&type=get_live_streams&cat_id=0TGl2ZSBTdHJlYW1zIENhdGVnb3J5";

    while ($row = mysqli_fetch_assoc($r)) {

        echo base64_encode($row['name']) . "{$row['id']}$PRTC//$ADDR/enigma2.php?username=$username&password=$password&type=get_live_streams&cat_id={$row['id']}";

    }


} else if($_GET['type'] == 'get_vod_categories') {

    header('Content-Type: application/xml');
    header('Connection: close');

    $xml = "<?xml version='1.0'?>\n";
    $xml .= "<items><playlist_name>ACES [ Movies ]</playlist_name>";

    if($config['iptv']['api_cache']) {

        $cache_filename = str_replace("'", '', $device['bouquets']);
        $cache_filename = explode(",",$cache_filename);
        sort($cache_filename);
        $cache_filename = "engima_vod_stream_cats_".implode("_",$cache_filename);

        $Cache = new Cache("/home/aces/cache/$cache_filename",$config['iptv']['api_cache'], $config['iptv']['api_cache_compressed']);
        if ($data = $Cache->get(true)) {
            $data=str_replace("%{username}",$username,$data);
            $data=str_replace("%{password}",$password,$data);
            echo $data;
            fastcgi_finish_request();
            if(!$Cache->isExpired()) die; //NO MORE TO DO...

        } else {
            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/".$cache_filename)) {
                exit;
            }
            $building_cache = 1;
            touch("/home/aces/run/build_cache_enigma_" . $cache_filename);

        }

    }


    $r = $ACES->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 AND o.type = 'movies' $SQL_ADULTS AND p.bouquet_id IN ('{$device['bouquets']}')
        GROUP BY cat.id ORDER BY cat.ordering
    ");


    while ($row = mysqli_fetch_assoc($r)) {

        //$url = urlencode("$PRTC//$ADDR/enigma2.php?username=$username%26password=$password%26type=get_vod_streams%26cat_id={$row['id']}");

        $xml .= "<channel><title>" . base64_encode($row['name']) . "</title>\n";
        $xml .= "<description>".  base64_encode('Movie Category '.$row['name']) ."</description>\n";
        $xml .= "<category_id>{$row['id']}</category_id>\n";
        $xml .= "<playlist_url><![CDATA[$PRTC//$ADDR/enigma2.php?username=%{username}&password=%{password}&type=get_vod_streams&cat_id={$row['id']}]]></playlist_url>\n";
        $xml .= "</channel>\n";

    }

    $xml .= '</items>';



    if($building_cache) {
        unlink("/home/aces/run/build_cache_enigma_" . $cache_filename);
        $Cache->saveit($xml);
    }

    $xml=str_replace("%{username}",$username,$xml);
    $xml=str_replace("%{password}",$password,$xml);
    echo $xml ;

} else if($_GET['type'] == 'get_series_categories') {

    header('Content-Type: application/xml');
    header('Connection: close');

    $xml = "<?xml version='1.0'?>\n";
    $xml .= "<items><playlist_name>ACES [ Movies ]</playlist_name>";

    if($config['iptv']['api_cache']) {

        $cache_filename = str_replace("'", '', $device['bouquets']);
        $cache_filename = explode(",",$cache_filename);
        sort($cache_filename);
        $cache_filename = "engima_series_stream_cats_".implode("_",$cache_filename);

        $Cache = new Cache("/home/aces/cache/$cache_filename",$config['iptv']['api_cache'],$config['iptv']['api_cache_compressed']);
        if ($data = $Cache->get(true)) {
            $data=str_replace("%{username}",$username,$data);
            $data=str_replace("%{password}",$password,$data);
            echo $data;
            fastcgi_finish_request();
            if(!$Cache->isExpired()) die; //NO MORE TO DO...

        } else {
            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/".$cache_filename)) {
                exit;
            }
            $building_cache = 1;
            touch("/home/aces/run/build_cache_enigma_" . $cache_filename);

        }

    }

    $r = $ACES->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS AND p.bouquet_id IN ('{$device['bouquets']}')
        GROUP BY cat.id ORDER BY cat.ordering
    ");

    while ($row = mysqli_fetch_assoc($r)) {

        $xml .= "<channel><title>" . base64_encode($row['name']) . "</title>\n";
        $xml .= "<description>".  base64_encode('Series Category '.$row['name']) ."</description>\n";
        $xml .= "<category_id>{$row['id']}</category_id>\n";
        $xml .= "<playlist_url><![CDATA[$PRTC//$ADDR/enigma2.php?username=$username&password=$password&type=get_series&cat_id={$row['id']}]]></playlist_url>\n";
        $xml .= "</channel>\n";


    }

    $xml .= "</items>";



    if($building_cache) {
        unlink("/home/aces/run/build_cache_enigma_" . $cache_filename);
        $Cache->saveit($xml);
    }

    $xml=str_replace("%{username}",$username,$xml);
    $xml=str_replace("%{password}",$password,$xml);
    echo $xml;

} else if($_GET['type'] == 'get_live_streams') { 
    
    header('Content-Type: application/xml');
    header('Connection: close');
    
    $cat_id = (int)$_GET['cat_id'];
    if($cat_id>0) $sql = " AND c.category_id = $cat_id ";
    
    
    $xml = "<?xml version='1.0'?>\n";
    $xml .= "<items><playlist_name>Live [ My_Bouquet_Name ]</playlist_name>";
    $xml .= "<category><category_id>$cat_id</category_id><category_title>Category $cat_id</category_title></category>\n";
    
    echo $xml;
    
    $r = $ACES->query("SELECT c.id, c.name, c.logo, c.tvg_id, c.category_id,c.catchup_expire_days,c.ordering FROM iptv_channels_in_bouquet p 
    INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )
    LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id
    WHERE c.enable = 1 AND p.bouquet_id IN ('{$device['bouquets']}') $sql 
    GROUP BY c.id ORDER BY c.ordering ");
    
    while($row=mysqli_fetch_assoc($r)) { 
        
        echo "<channel><title>".base64_encode($row['name'])."</title>";
        echo "<description></description>";
        echo "<desc_image>$PRTC//$ADDR/logos/{$row['logo']}</desc_image>";
        echo "<category_id>$cat_id</category_id>";
        echo "<stream_url>$PRTC//$ADDR/live/$username/$password/{$row['id']}.mpegts</stream_url>";
        echo "</channel>\n";

    }
    
    
    echo "</items>";

} else if($_GET['type'] == 'get_vod_streams')  {

    header('Content-Type: application/xml');
    header('Connection: close');

    $sql_cat = '';
    $cat_id = (int)$_GET['cat_id'];
    if($cat_id>0) {
        $sql_cat = " AND cat.id = $cat_id ";
        $rc = $ACES->query("SELECT name FROM iptv_stream_categories WHERE id = $cat_id ");
        $cat_name = $rc->fetch_assoc()['name'];
        $cat_name = str_replace("&",'and',$cat_name);
        $cat_name = str_replace(str_split( '\\"<>\''), '', $cat_name);
    } else $cat_id = '';

    if($config['iptv']['api_cache']) {

        $cache_filename = str_replace("'", '', $device['bouquets']);
        $cache_filename = explode(",",$cache_filename);
        sort($cache_filename);
        $cache_filename = "engima_vod_streams_cat_id_{$cat_id}_".implode("_",$cache_filename);

        $Cache = new Cache("/home/aces/cache/$cache_filename",$config['iptv']['api_cache'],$config['iptv']['api_cache_compressed']);
        if ($data = $Cache->get(true)) {
            $data=str_replace("%{username}",$username,$data);
            $data=str_replace("%{password}",$password,$data);
            echo $data;
            fastcgi_finish_request();
            if(!$Cache->isExpired()) die; //NO MORE TO DO...

        } else {
            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/".$cache_filename)) {
                exit;
            }
            $building_cache = 1;
            touch("/home/aces/run/build_cache_enigma_" . $cache_filename);

        }

    }


    $r=$ACES->query("SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i 
        RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
        WHERE o.status = 1 AND o.type != 'series' $SQL_ADULTS AND p.bouquet_id IN ('{$device['bouquets']}') 
        GROUP BY i.i ORDER BY p.i ");


    $xml = "<?xml version='1.0'?>\n";
    $xml .= "<items><playlist_name> ACES IPTV Movies </playlist_name>\n";
    $xml .= "<category>\n";
    $xml .= "<category_id>$cat_id</category_id>\n";
    $xml .= "<category_title>$cat_name</category_title>\n";
    $xml .= "</category>\n";



    while($row=mysqli_fetch_assoc($r)) {

        $xml .= "<channel><title>".base64_encode($row['name'])."</title>";
        $xml .= "<description>".base64_encode($row['about'])."</description>";
        $xml .= "<desc_image>$PRTC//$ADDR/logos/{$row['logo']}</desc_image>";
        $xml .= "<category_id>{$row['category_id']}</category_id>";
        $xml .= "<stream_url>$PRTC//$ADDR/movie/$username/$password/{$row['id']}.mp4</stream_url>";
        $xml .= "</channel>\n";


    }

    $xml .= "</items>";



    if($building_cache) {
        unlink("/home/aces/run/build_cache_enigma_" . $cache_filename);
        $Cache->saveit($xml);
    }

    $xml=str_replace("%{username}",$username,$xml);
    $xml=str_replace("%{password}",$password,$xml);
    echo $xml ;

} else if($_GET['type'] == 'get_series') {

    header('Content-Type: application/xml');
    header('Connection: close');

    $sql_cat = '';
    $cat_id = (int)$_GET['cat_id'];
    $cat_name = "";
    if($cat_id>0) {
        $sql_cat = " AND cat.id = $cat_id ";
        $rc = $ACES->query("SELECT name FROM iptv_stream_categories WHERE id = '$cat_id' ");
        $cat_name = $rc->fetch_assoc()['name'];
        $cat_name = str_replace("&",'and',$cat_name);
        $cat_name = str_replace(str_split( '\\"<>\''), '', $cat_name);
    } $cat_id = '';

    if($config['iptv']['api_cache']) {

        $cache_filename = str_replace("'", '', $device['bouquets']);
        $cache_filename = explode(",",$cache_filename);
        sort($cache_filename);
        $cache_filename = "engima_series_streams_cat_id_{$cat_id}_".implode("_",$cache_filename);

        $Cache = new Cache("/home/aces/cache/$cache_filename",$config['iptv']['api_cache'],$config['iptv']['api_cache_compressed']);
        if ($data = $Cache->get(true)) {
            $data=str_replace("%{username}",$username,$data);
            $data=str_replace("%{password}",$password,$data);
            echo $data;
            fastcgi_finish_request();
            if(!$Cache->isExpired()) die; //NO MORE TO DO...

        } else {
            //BUILDING CACHE ALREADY..
            if(is_file("/home/aces/run/".$cache_filename)) {
                exit;
            }
            $building_cache = 1;
            touch("/home/aces/run/build_cache_enigma_" . $cache_filename);

        }

    }

    $r=$ACES->query("SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i 
        RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id $sql_cat
        WHERE o.status = 1 AND o.type = 'series' $SQL_ADULTS AND p.bouquet_id IN ('{$device['bouquets']}') 
        GROUP BY i.i ORDER BY p.i ");

    $xml = "<?xml version='1.0'?>\n"; "<items><playlist_name> ACES IPTV Series </playlist_name>\n";
    $xml .= "<category>\n";
    $xml .= "<category_id>$cat_id</category_id>\n";
    $xml .= "<category_title>$cat_name</category_title>\n";
    $xml .= "</category>\n";

    while($row=mysqli_fetch_assoc($r)) {

        $xml .= "<channel><title>".base64_encode($row['name'])."</title>";
        $xml .= "<description>".base64_encode($row['about'])."</description>";
        $xml .= "<desc_image>$PRTC//$ADDR/logos/{$row['logo']}</desc_image>";
        $xml .= "<category_id>{$row['category_id']}</category_id>";
        $xml .= "<playlist_url><![CDATA[$PRTC//$ADDR/enigma2.php?username=$username&password=$password&type=get_seasons&series_id={$row['id']}]]></playlist_url>";
        $xml .= "</channel>\n";


    }

    $xml .= "</items>";
    $xml=str_replace("%{username}",$username,$xml);
    $xml=str_replace("%{password}",$password,$xml);
    echo $xml ;


    fastcgi_finish_request();

    if($building_cache) {
        unlink("/home/aces/run/build_cache_enigma_" . $cache_filename);
        $Cache->saveit($xml);
    }

    $xml=str_replace("%{username}",$username,$xml);
    $xml=str_replace("%{password}",$password,$xml);
    echo $xml ;


} else if($_GET['type'] == 'get_seasons') {

    header('Content-Type: application/xml');
    header('Connection: close');

    $series_id = (int)$_GET['series_id'];
    //$cat_id = (int)$_GET['cat_id'];

    if( !$series_id ) die;


    $rs = $ACES->query("SELECT logo,name FROM iptv_ondemand WHERE id = $series_id ");
    $logo = $rs->fetch_assoc()['logo'];



    $r=$ACES->query("SELECT s.id,s.number, count(e.id) as episode_count FROM iptv_series_seasons s "
        . " RIGHT JOIN iptv_series_season_episodes e ON e.season_id = s.id and e.status = 1  "
        . " WHERE s.series_id = $series_id  GROUP BY s.id ORDER BY s.number ");


    echo "<items><playlist_name> ACES IPTV Series $cat_name </playlist_name>\n";
    echo "<category>\n";
    echo "<category_id>$cat_id</category_id>\n";
    echo "<category_title>$cat_name</category_title>\n";
    echo "</category>\n";


    while($row=$r->fetch_assoc()) {

        echo "<channel>\n";
        echo "<title>".base64_encode("Season {$row['number']}")."</title>\n";
        echo "<desc_image>$PRTC//$ADDR/logos/{$logo}</desc_image>\n";
        echo "<description>".base64_encode("Season {$row['number']}")."</description><category_id>{$row['number']}</category_id>\n"; //CATEGORY ID IS SAME AS SEASON NUMBER?, TAKED FROM XC
        echo "<playlist_url><![CDATA[$PRTC//$ADDR/enigma2.php?username=$username&password=$password&type=get_series_streams&series_id={$row['id']}&season={$row['number']}&season_id={$row['id']}&series_id=$series_id]]></playlist_url>\n";
        echo "</channel>\n";


    }

    echo "</items>\n";


} else if($_GET['type'] == 'get_series_streams') {

    header('Content-Type: application/xml');
    header('Connection: close');

    $season_number = (int)$_GET['season'];
    $series_id = (int)$_GET['series_id'];
    if(!$season_id = (int)$_GET['season_id']) {
        AcesLogE("No season id set.");
        die;
    }

    $rs=$ACES->query("SELECT name FROM iptv_ondemand WHERE id = $series_id ");
    $cat_name = $rs->fetch_assoc()['name'];
    $cat_name = str_replace("&",'and',$cat_name);
    $cat_name = str_replace(str_split( '\\"<>\''), '', $cat_name);


    echo "<items><playlist_name> ACES IPTV Series $cat_name </playlist_name>\n";
    echo "<category>\n";
    echo "<category_id>$season_number</category_id>\n";
    echo "<category_title>$cat_name</category_title>\n";
    echo "</category>\n";

    $r=$ACES->query("SELECT e.*  FROM iptv_series_season_episodes e
        WHERE e.season_id = {$season_id} AND e.status = 1 ORDER BY e.number");

    while($row=$r->fetch_assoc()) {

        echo "<channel>\n";
        echo "<title>".base64_encode($row['title'])."</title>\n";
        echo "<desc_image>$PRTC//$ADDR/logos/{$row['logo']}</desc_image>\n";
        echo "<description>".base64_encode($row['about'])."</description>\n";
        echo "<category_id></category_id>\n";
        echo "</channel>\n";
        echo "<stream_url>$PRTC//$ADDR/series/$username/$password/{$row['id']}.mp4</stream_url>\n";

    }

    echo "</items>\n";

}

