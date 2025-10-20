<?php

set_time_limit(0);
//error_reporting(0);

ini_set("error_log", "/home/aces/logs/movies_dir_import.log");


include "/home/aces/stream/config.php";
include "/home/aces/panel/class/TMDB.php";

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

function logDebug($text){
    if(defined("DEBUG"))
        error_log("DEBUG: ".$text);
}


$ID = (int)$argv[1]; 
if(!$ID) die;

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }

$r=$DB->query("SELECT * FROM iptv_proccess WHERE id = $ID ");
if(!$row=$r->fetch_assoc()) die;

$args = unserialize($row['args']);
if($args == false )
    $args = json_decode($row['args'], true);


$do_not_download_images = (bool)$args['do_not_download_images'];
$do_not_download_images = true; //LOGOS CAN NOT BE DOWNLOADED ON LB.
$dir = urldecode($args['directory']);

$TMDB = new TMDB($do_not_download_images);

register_shutdown_function(function(){
    global $DB, $ID;
    $DB->query("DELETE FROM iptv_proccess WHERE id = $ID ");
});

if($args['tmdb_lang'])
    $TMDB->LANG = $args['tmdb_lang'];

$pid = getmypid();
$DB->query("UPDATE iptv_proccess SET pid = '$pid' WHERE id = $ID ");



switch($dir) {

    case '/':
    case '/home':
    case '/home/':
    case '/home/aces':
    case '/home/aces/':
    case '/home/aces/vods':
    case '/home/aces/vods/':
        die;

}


//$TMDB_API = null;
//$r2=$DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
//$TMDB_API=mysqli_fetch_array($r2)['value'];

//$category_id = $args['category'];

if(!empty($args['category'])) $args['categories'][0] = $args['category'];
$categories =  $args['categories']; 
$category_id = $args['categories'][0];


$server_id = $SERVER_ID;
$add_if_info = 0;
$MAX_DOWNLOAD = 10;


$r=$DB->query("SELECT * FROM iptv_servers WHERE id = '$server_id' ");
$server=$r->fetch_assoc();

$r=$DB->query("SELECT * FROM iptv_servers WHERE id = '1' ");
$main_servers = $r->fetch_assoc();

$scans=0;
logDebug("Scanning Dir ".$dir );
$CONTENTS = getDirContents("$dir");

error_log("SCANNING DIR " . $dir);

//foreach (rglob("$dir/*.{mp4,mkv,avi,flv,wmv,m4v}", GLOB_BRACE) as $filename) {
foreach( $CONTENTS as $filename)
  if( !is_dir($filename) && in_array( pathinfo($filename, PATHINFO_EXTENSION) , array('mp4','mkv','avi','flv','wmv','m4v')) ) {


    $r=$DB->query("SELECT id FROM iptv_video_files WHERE source_file = '".urlencode($filename)."' ");
    if(!mysqli_fetch_array($r)) {
    
        $file = pathinfo($filename);
        $ext = $file['extension'];

        $name = str_replace("(","",$file['filename']);
        $name = str_replace(")","",$name);
        $name = str_replace("."," ",$name);
        $name = str_replace("_"," ",$name);
        $name = str_replace("-"," ",$name);
        $name = str_replace("[","",$name);
        $name = str_replace("]","",$name);
        
        $remove = array('480p','480i','480','720p','720i','bluray','blueray','hdrip','hdtv','web','x264','264','h264');

        $id_tmdb=0;
        $found=0;
        $found_info = 0;
        $tmdb_category='';
        $tmdb_category1 = null;
        $tmdb_category2 = null;
        $tmdb_category3 = null;

        $q_name = mysqli_real_escape_string($DB,$name);
        if($id_tmdb = $TMDB->search_imdb($name,'movies')[0]['id'])
            $found_info = 1;


        if( $add_if_info && $found_info || !$add_if_info ) {

            //ALWAYS ADDING..
            //$r2=$DB->query("SELECT id FROM iptv_ondemand WHERE lower(name) = lower('{$name}') ");
            //if(mysqli_fetch_array($r2)) $found = 1;
            if(!$found) {

                if($r = $DB->query("INSERT INTO iptv_ondemand (name,enable,category_id,type,tmdb_id,add_date) 
                        VALUES('$q_name',1,'$category_id','movies','$id_tmdb',NOW()) ")) {
                    $vod_id = $file_id = $DB->insert_id;
                    $TMDB->update_ondemand($vod_id);
                    $m_tmdb = $TMDB->fetch_imdb_movie($id_tmdb);

                    foreach($categories as $cat_id ) {
                                                
                        //ADDING TMDB CATEGORY
                        if($cat_id < 0  ) { 
                            
                            $tmdb_category=null;
                            if($cat_id == -1  )  $tmdb_category = $m_tmdb['genres'][0]['name'];
                            elseif($cat_id == -2 )  $tmdb_category =  $m_tmdb['genres'][1]['name'];
                            elseif($cat_id == -3 ) $tmdb_category =  $m_tmdb['genres'][2]['name'];

                            if(!is_null($tmdb_category)) {
                                $rcat=$DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$tmdb_category')");
                                if($rcat_row=$rcat->fetch_assoc()) { $cat_id = $rcat_row['id']; }
                                else { 
                                    $DB->query("INSERT INTO iptv_stream_categories (name) VALUES('$tmdb_category') ");
                                    $cat_id = $DB->insert_id;
                                }

                                //BECAUSE ONLY THE TMDB GENRE HAVE BEEN SET AS CATEGORY LET SET IT AS MAIN.
                                if($category_id < 1 )
                                    $DB->query("UPDATE iptv_ondemand SET category_id = '$cat_id' WHERE id = '$vod_id'");
                            }
                            
                            
                        }
                        
                        if($cat_id) { 
                            $DB->query("INSERT INTO iptv_in_category (vod_id,category_id) VALUES ('$vod_id','$cat_id') ");
                        }
                        
                    }


                    $trans = 'symlink';
                    $container = $ext;
                    $filename = urlencode($filename);

                    $r=$DB->query("INSERT INTO iptv_video_files (movie_id,type,transcoding,container,server_id,source_file) 
                        VALUES($vod_id,'movie','$trans','$container','{$server['id']}','$filename')");
                    $file_id = $DB->insert_id;

                    $p['api_token'] = $server['api_token'];
                    $p['action'] = 'process_vod';
                    //$p['version'] = ACES_VER;
                    $p = array_merge($p, array('vod_id' => $file_id ) );
                    $j = urlencode(json_encode($p));

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://{$server['address']}:{$server['port']}/stream/api.php?data=$j");
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_exec($ch);

                    foreach($args['bouquets'] as $b ) $DB->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) VALUES('$b','$vod_id') ");

                    //sleep(2);

                }

            }

        }
 
    }
    $scans++;
    $progress = ($scans / count($CONTENTS)) * 100;
    $DB->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = $ID ");
    $r2=$DB->query("SELECT id FROM iptv_proccess WHERE id = $ID");
    if(!$r2->fetch_assoc()) die;
}

