<?php

ini_set('memory_limit','50000M');
set_time_limit(-1);
error_reporting(0);

//include_once 'ACES2/init.php';
//$db = new \ACES2\DB;

//$username = $db->escString($_GET['username']);
//$password = $db->escString($_GET['password']);


$host = isset($_SERVER['HTTPS']) ? "https://".$_SERVER['HTTP_HOST'] : "http://".$_SERVER['HTTP_HOST'];
define('HOST', $host);

include "/home/aces/stream/config.php";
$db = new mysqli( $DBHOST, $DBUSER, $DBPASS, $DATABASE);
if($db->connect_errno > 0){ die(); }


$username = $db->real_escape_string($_GET['username']);
$password = $db->real_escape_string($_GET['password']);

$type = strtoupper($_GET['type']);

function notFound() {

    sleep(2);

    header('Content-type: application/force-download');
    header('Content-Disposition: attachment; filename=playlist.m3u8');
    header("Content-type: application/vnd.apple.mpegurl");

    echo "#EXTM3U\r\n";
    echo "#EXTINF:0,NOT FOUND\r\n";
    echo "http://localhost/";

    exit;

}

$r = $db->query("SELECT id,name,TIMESTAMPDIFF(SECOND, NOW(), subcription) as expiration,
       bouquets,adults,hide_vods_from_playlist,no_m3u_playlist,username,token as password FROM iptv_devices 
       WHERE username = '$username' AND token = '$password' ");

if(!$account = $r->fetch_assoc() )
    notFound();

if($account['expiration'] < 1 || $account['no_m3u_playlist'] == 1 )
    notFound();

$b = unserialize($account['bouquets']);
$Bouquets = is_array($b) ? join("','", $b ) : '' ;
$Username = $account['username'];
$Password = $account['password'];

$sql_adutls = $account['adults'] == 0 ? " AND cat.adults = 0 " : '';

$r = $db->query("SELECT c.*, cat.name as category_name, cat.adults, opt.adaptive_opt  
        FROM iptv_channels_in_bouquet p INNER JOIN iptv_channels c ON ( c.id = p.chan_id  )  
        LEFT JOIN iptv_stream_categories cat ON c.category_id = cat.id 
        LEFT JOIN iptv_stream_options opt ON opt.id = c.stream_profile 
        WHERE c.enable = 1 AND p.bouquet_id IN ('{$Bouquets}')  $sql_adutls
        GROUP BY c.id
        ORDER BY c.ordering
");



if($account['hide_vods_from_playlist'] == 0 ) {
//    $r_vods = $db->query("SELECT o.id,o.name,o.logo,o.type, cat.name as category_name, cat.id as category_id, f.container
//        FROM iptv_in_category i
//        RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
//        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
//        RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id
//        RIGHT JOIN iptv_video_files f ON video_id = o.id
//        WHERE o.status = 1 AND p.bouquet_id IN ('{$Bouquets}')  $sql_adutls
//        GROUP BY o.id ORDER BY p.i ");

    $r_vods=$db->query("SELECT o.id,o.name,o.logo,o.type, cat.name as category_name, cat.id as category_id,f.container
       
        FROM iptv_ondemand_in_bouquet p 
            RIGHT JOIN iptv_ondemand o ON ( o.id = p.video_id )
            LEFT JOIN iptv_stream_categories cat ON o.category_id = cat.id 
            LEFT JOIN iptv_video_files f ON f.movie_id = o.id AND f.movie_id != 0
            
        WHERE o.enable = 1  AND o.status = 1 AND p.bouquet_id IN ('{$Bouquets}') 
        GROUP BY o.id ORDER BY p.i ");

}


switch($type) {

    case 'WEBTV-HLS':
    case 'WEBTV-MPEGTS':

        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"webtv list.txt\"");


        $format = $type == 'WEBTV-HLS' ? 'm3u8': 'mpegts' ;

        while($row=$r->fetch_assoc()) {
            echo "Channel name:{$row['name']}\r\n";
            echo "URL:".HOST."/live/$Username/$Password/{$row['id']}.$format\r\n";
        }

        if($account['hide_vods_from_playlist'] == 0 )
            while($row=$r_vods->fetch_assoc()) {
                if ($row['type'] != 'series'){
                    $container = $row['container'] ? $row['container'] : "mp4";

                    echo "Channel name:{$row['name']}\r\n";
                    echo "URL:" . HOST . "/movie/$Username/$Password/{$row['id']}.$container\r\n";

                } else {
                    $r_episodes = $db->query("SELECT e.id,e.title,e.logo,e.number as episode_number, s.number as season_number, f.container
                        FROM iptv_series_season_episodes e 
                        RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id 
                        RIGHT JOIN iptv_video_files f ON e.id = f.episode_id
                        WHERE s.series_id = {$row['id']}
                        GROUP BY e.id ORDER BY s.number,e.number  ");


                    while($episode=$r_episodes->fetch_assoc()) {
                        $container = $episode['container'] ? $episode['container'] : "mp4";
                        $name = "{$row['name']} S{$episode['season_number']} E{$episode['episode_number']} {$episode['title']}";
                        echo "Channel name: $name\r\n";
                        echo "URL:" . HOST . "/episode/$Username/$Password/{$episode['id']}.$container\r\n";

                    }

                }

            }



        echo "[Webtv channel END]";
        break;


    case 'SIMPLE-LIST-HLS':
    case 'SIMPLE-LIST':

        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=simple-{$Username}.txt");

        $format = $type == 'SIMPLE-LIST-HLS' ? 'm3u8': 'mpegts' ;

        while($row=$r->fetch_assoc()) {
            echo HOST."/live/$Username/$Password/{$row['id']}.$format #Name: {$row['name']}\r\n";
        }

        if($account['hide_vods_from_playlist'] == 0 )
            while($row=$r_vods->fetch_assoc()) {
                if ($row['type'] != 'series'){
                    $container = $row['container'] ? $row['container'] : "mp4";
                    echo HOST."/movie/$Username/$Password/{$row['id']}.$container #Name: {$row['name']}\r\n";


                } else {
                    $r_episodes = $db->query("SELECT e.id,e.title,e.logo,e.number as episode_number, s.number as season_number, f.container
                        FROM iptv_series_season_episodes e 
                        RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id 
                        RIGHT JOIN iptv_video_files f ON e.id = f.episode_id
                        WHERE s.series_id = {$row['id']}
                        GROUP BY e.id ORDER BY s.number,e.number  ");


                    while($episode=$r_episodes->fetch_assoc()) {
                        $name = "{$row['name']} S{$episode['season_number']} E{$episode['episode_number']} {$episode['title']}";
                        $container = $episode['container'] ? $episode['container'] : "mp4";
                        echo HOST."/episode/$Username/$Password/{$episode['id']}.$container #Name: $name\r\n";
                    }

                }

            }


        break;

    case 'M3U-HLS-SIMPLE':
    case 'M3U-HLS':
    case 'M3U-TS-SIMPLE':
    case 'M3U-TS':
    default :

        header('Content-type: application/force-download');
        header("Content-Disposition: attachment; filename=$Username.m3u8");
        header("Content-type: application/vnd.apple.mpegurl");

        echo "#EXTM3U\n";

        while($row=$r->fetch_assoc()) {

            $format = match($type) {
                'M3U-HLS-SIMPLE'=> 'm3u8',
                'M3U-HLS' => 'm3u8',
                default => 'ts'
            };

            //$format = $type == 'M3U-TS-SIMPLE' || $type == 'M3U-TS' ? 'mpegts' : 'm3u8';

            $logo = '';$tvg_id = '';$group = '';

            if($type == 'M3U-HLS' || $type == 'M3U-TS') {
                $logo = $row['logo'] ? " tvg-logo=\"".HOST."/logos/{$row['logo']}\" " : '';
                $tvg_id = $row['tvg_id'] ? " tvg-id=\"{$row['tvg_id']}\" " : '';
                $group = $row['category_name'] ? " group-title=\"{$row['category_name']}\" " : '';
            }

            echo "#EXTINF:-1{$logo}{$tvg_id}{$group},{$row['name']}\r\n" ;
            echo HOST."/live/$Username/$Password/{$row['id']}.$format\r\n";

        }

        if($account['hide_vods_from_playlist'] == 0 )
            while($row=$r_vods->fetch_assoc()) {

                if($type == 'M3U-HLS' || $type == 'M3U-TS') {

                    $logo = '';
                    if($row['logo'])
                        $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
                            ? " tvg-logo=\"{$row['logo']}\" "
                            : " tvg-logo=\"".HOST."/logos/{$row['logo']}\" ";


                    $tvg_id = "tvg-id=\"aces-movie-id-{$row['id']}\" ";
                    $group = $row['category_name'] ? " group-title=\"{$row['category_name']}\"" : '';

                }

                if ($row['type'] != 'series'){

                    $container = $row['container'] ? $row['container'] : "mp4";
                    echo "#EXTINF:-1 {$logo} {$tvg_id} {$group},{$row['name']}\r\n" ;
                    echo HOST."/movie/$Username/$Password/{$row['id']}.$container\r\n";

                } else {

                    $r_episodes = $db->query("SELECT e.id,e.title,e.logo,e.number as episode_number, s.number as season_number, f.container 
                        FROM iptv_series_season_episodes e 
                        RIGHT JOIN iptv_series_seasons s ON e.season_id = s.id 
                        LEFT JOIN iptv_video_files f ON e.id = f.episode_id
                        WHERE s.series_id = {$row['id']}
                        GROUP BY e.id ORDER BY s.number,e.number  ");


                    while($episode=$r_episodes->fetch_assoc()) {

                        if($type == 'M3U-HLS' || $type == 'M3U-TS') {
                            $logo = '';


                            if($episode['logo'])
                                $logo = filter_var($episode['logo'], FILTER_VALIDATE_URL) ?
                                    " tvg-logo=\"{$episode['logo']}\" " :
                                    " tvg-logo=\"".HOST."/logos/{$episode['logo']}\" ";

                            $tvg_id = '';
                            //$tvg_id = 'aces-movie-id-'.$row['id'];
                            $group = $row['category_name'] ? " group-title=\"{$row['category_name']}\"" : '';
                        }

                        $container = !$episode['container'] ?  "mp4" : $episode['container'] ;
                        $name = "{$row['name']} S{$episode['season_number']} E{$episode['episode_number']} {$episode['title']}";

                        echo "#EXTINF:-1{$logo}{$tvg_id}{$group},$name\r\n" ;
                        echo HOST."/episode/$Username/$Password/{$episode['id']}.$container\r\n";

                    }

                }

            }


        break;

}