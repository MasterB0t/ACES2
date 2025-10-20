<?php

error_reporting(0);

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$RED  = "\033[31m";
$WHITE = "\033[0m";
$GREEN = "\033[32m";

$connected=0;

function l($msg) { error_log("$msg\n", 3, "/home/aces/logs/xtream_importer.log"); }

function logErr() { 
    global $sql,$DB;
    echo "MYSQL FAIL WHEN EXECUTING '$sql' MYSQL ERROR ".mysqli_error($DB);
    l("MYSQL FAIL WHEN EXECUTING '$sql' MYSQL ERROR ".mysqli_error($DB));
    unlink('/home/aces/run/aces_import_from_xc.pid');
    die;
}



function get_string_between($string, $start, $end){
    //$string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

if(is_file("/home/aces/run/aces_import_from_xc.pid")) { 
    
    $PID = file_get_contents("/home/aces/run/aces_import_from_xc.pid");
    if(posix_getpgid($PID)) die("Another importer proccess is running please wait until it finished.");
} else {
 
    if(!file_put_contents("/home/aces/run/aces_import_from_xc.pid",getmypid())) die('Could not write pid file.');
 
}

if(isset($argv[1]) && $argv[1] == '--auto' ) { 
    
    if(empty($argv[2])) $pass = '';
    else $pass = $argv[2]; 
    
    $DB = new mysqli('localhost','root',$pass,'aces_iptv');
    if($DB->connect_errno > 1) { Die ('Could not connect to database.'); } 
    
} else {

    echo "\n{$RED}NOTE!!!... This will delete all your ACES IPTV database and import with xtreamcodes one. Are you sure you want to continue ?\n\n{$WHITE}Type yes or no. ";
    while(true) {
        
        $a = trim(fgets(STDIN));
        
        if( $a == 'yes' || $a == 'Yes' || $a == 'YES' ) break;
        else if ( $a == 'no' || $a == 'No' || $a == 'NO' ) { die("Ok will not import anything exist now."); }
        else  echo "\nPlease answer yes or no. ";
        
    }


}


unlink('/home/aces/logs/xtream_importer.log');
l("PROCCESS START.\n");

$DB = new mysqli('localhost','root',$pass,'aces_iptv');
if($DB->connect_errno > 0){ die('Unable to connect to database.'); }

if(!$r=$DB->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA  WHERE SCHEMA_NAME = 'xtream_iptvpro'")) die("There is no xtreamcodes database installed nothing to import.");
if(!mysqli_fetch_array($r)) die("There is no xtreamcodes database installed nothing to import.");

$DB_XC = new mysqli('localhost','root',$pass,'xtream_iptvpro');
if($DB_XC->connect_errno > 0){ die('Unable to connect to xtreamcodes database.'); }



$DB->query("DELETE FROM iptv_stream_categories");
$DB->query("DELETE FROM iptv_channels");
$DB->query("DELETE FROM iptv_stream_options");
$DB->query("DELETE FROM iptv_channels_sources");
$DB->query("DELETE FROM iptv_bouquets");
$DB->query("DELETE FROM iptv_bouquet_packages ");
$DB->query("DELETE FROM iptv_devices");
$DB->query("DELETE FROM users ");
$DB->query("DELETE FROM iptv_user_info ");
//$DB->query("DELETE FROM iptv_ondemand");
//$DB->query("DELETE FROM iptv_video_files");
//$DB->query("DELETE FROM iptv_series_seasons ");
//$DB->query("DELETE FROM iptv_series_season_episodes ");

echo "\n\n{$RED}This proccess could take several minutes. Please wait until it finished.\n{$WHITE}";



echo "\n\nImporting catergories...\n";
$r=$DB_XC->query("SELECT * FROM stream_categories ");
while($row=mysqli_fetch_array($r)) { 
    $name = stripslashes($row['category_name']);
    $name = mysqli_real_escape_string($DB,$name);
    $name = mb_convert_encoding( utf8_encode($name), "UTF-8", "UTF-8");
    
    $sql = "INSERT INTO iptv_stream_categories (id,name) VALUES({$row['id']},'$name') ";
    
    if(!$DB->query($sql)) { echo "\n{$RED}Fail importing categories.\n{$WHITE}"; logErr(); } 
    
}

echo "\nImporting channels...\n";

$DB->query("INSERT INTO iptv_stream_options (id,name,video_codec,audio_codec,segment_time,segment_list_files,segment_wrap) VALUES(1,'Restream','copy','copy','10','5','5')  ");
$r=$DB_XC->query("SELECT * FROM streams WHERE type = 1 ");
$order = 0;
while($row=mysqli_fetch_array($r)) { 
    $sources = json_decode($row['stream_source'],1);
    
    if($row['redirect_stream'] == 1 ) $stream=0;
    else $stream=1;
    $stream=0;
    
    $r2=$DB_XC->query("SELECT on_demand FROM streams_sys WHERE stream_id = {$row['id']}");
    if(!$ondemand=mysqli_fetch_array($r2)['on_demand'])$ondemand=0;
    
//    $logo = '';
//    $img = get_string_between($info['stream_icon']."%{end}%", "images/", "%{end}%");
//    if(is_file("/home/xtreamcodes/iptv_xtream_codes/wwwdir/images/$img")) { copy("/home/xtreamcodes/iptv_xtream_codes/wwwdir/images/$img", "/home/aces/panel/logos/$img"); $logo = $img; }
//    else l("Ignoring images file '$img' could not be found on server.\n");
    
//    $m=null;$logo='';
//    preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $row['stream_icon'], $m);
//    $img =  basename($m[0][0]);
//    if(file_put_contents("/home/aces/panel/logos/$img", file_get_contents($m[0][0]))) $logo = "$img";
    
    $logo=$row['stream_icon'];
    $name=mysqli_real_escape_string($DB,mb_convert_encoding( utf8_encode($row['stream_display_name']), "UTF-8", "UTF-8"));
    $tvg_id=mysqli_real_escape_string($DB,mb_convert_encoding( utf8_encode($row['channel_id']), "UTF-8", "UTF-8"));
    $order++;
    $sql = "INSERT INTO iptv_channels (id,name,category_id,tvg_id,logo,stream,ondemand,stream_server,stream_profile,enable,ordering,number) 
        VALUES({$row['id']},'{$name}','{$row['category_id']}','{$tvg_id}','$logo','$stream','$ondemand',1,1,1,$order,$order) ";

    if(!$DB->query($sql)) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); } ;
    
    $p=0;
    foreach($sources as $s ) { 
        $p++;
        $s = mysqli_real_escape_string($DB,$s);
        $sql = "INSERT INTO iptv_channels_sources (chan_id,priority,enable,url) VALUES({$row['id']},$p,1,'$s') ";
        if(!$DB->query($sql) ) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
    }
    
}



echo "\nImporting bouquets...\n";
$r=$DB_XC->query("SELECT * FROM bouquets ");
while($row=mysqli_fetch_array($r)) { 
    $channels = json_decode($row['bouquet_channels'],1);
    $row['bouquet_name'] = stripslashes($row['bouquet_name']);
    $row['bouquet_name']=$DB_XC->real_escape_string(mb_convert_encoding( utf8_encode($row['bouquet_name']), "UTF-8", "UTF-8"));
    
    $sql = "INSERT INTO iptv_bouquets (id,name) VALUES('{$row['id']}','{$row['bouquet_name']}')";
    if(! $DB->query($sql) ) {  echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
    
    foreach($channels as $c ) {
        $sql = "INSERT INTO iptv_channels_in_bouquet (bouquet_id,chan_id) VALUES('{$row['id']}','$c') ";
        if(!$DB->query($sql)) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
    }
}

$r=$DB_XC->query("SELECT * FROM packages ");
while($row=mysqli_fetch_array($r))  {
    
    $s= serialize(json_decode($row['bouquets'],1));
    $name = mysqli_real_escape_string($DB,mb_convert_encoding( utf8_encode($row['package_name']), "UTF-8", "UTF-8")); 
    
    $t_credits = $row['trial_credits'];
    $o_credits = (int)$row['official_credits'];
    $max_c = (int)$row['max_connections'];
    
    if(strtolower($row['trial_duration_in']) == 'minutes') $row['trial_duration_in'] = 'MINUTE';
    else if(strtolower($row['trial_duration_in']) == 'hours') $row['trial_duration_in'] = 'HOUR';
    else if(strtolower($row['trial_duration_in']) == 'days') $row['trial_duration_in'] = 'DAY';
    else if(strtolower($row['trial_duration_in']) == 'months') $row['trial_duration_in'] = 'MONTH';
    else if(strtolower($row['trial_duration_in']) == 'years') $row['trial_duration_in'] = 'YEAR';
    
    if(strtolower($row['official_duration_in']) == 'minutes') $row['official_duration_in'] = 'MINUTE';
    else if(strtolower($row['official_duration_in']) == 'hours') $row['official_duration_in'] = 'HOUR';
    else if(strtolower($row['official_duration_in']) == 'days') $row['official_duration_in'] = 'DAY';
    else if(strtolower($row['official_duration_in']) == 'months') $row['official_duration_in'] = 'MONTH';
    else if(strtolower($row['official_duration_in']) == 'years') $row['official_duration_in'] = 'YEAR';
    
    $sql = "INSERT INTO iptv_bouquet_packages (id,name,bouquets,trial_credits,official_credits,trial_duration,trial_duration_in,official_duration,official_duration_in,can_gen_mag,only_mag,max_connections) VALUES('{$row['id']}','$name','$s','$t_credits','$o_credits','{$row['trial_duration']}','{$row['trial_duration_in']}','{$row['official_duration']}','{$row['official_duration_in']}','{$row['can_gen_mag']}','{$row['only_mag']}','$max_c') ";
        
    if( !$DB->query($sql) ) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
        
}




echo "\nImporting Users...\n";
$r=$DB_XC->query("SELECT * FROM reg_users ");
while($row=mysqli_fetch_array($r)) { 
    
    $email='';
    if(filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $email = $row['email'];
    
    $signup_date = time();
    if($row['date_registered']) $signup_date = $row['date_registered'];
    
    //$row['username'] = mb_convert_encoding( utf8_encode($row['username']), "UTF-8", "UTF-8");
    $row['username'] = trim(mysqli_real_escape_string($DB,$row['username']));
    $pass = md5($row['username']);
    
    $sql = "INSERT INTO users (id,name,username,password,email,status,signup_date) VALUES('{$row['id']}','{$row['username']}','{$row['username']}','$pass','$email',1,DATE_FORMAT(FROM_UNIXTIME($signup_date),'%Y-%m-%d %H:%i:%s')) ";
    if(! $DB->query($sql)) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
    
    $op = null;
    foreach(json_decode($row['override_packages'],1) as $id => $v ) { 
        
        if( $v['official_credits'] ) $op[$id]['official_credits'] = $v['official_credits'];
        if( $v['trial_credits'] ) $op[$id]['trial_credits'] = $v['trial_credits'];
    }
    
    if(is_array($op)) $op=json_encode($op);
    
    $sql = "INSERT INTO iptv_user_info (user_id,credits,override_packages,user_owner) VALUES('{$row['id']}','{$row['credits']}','$op','{$row['owner_id']}' )";
    if(! $DB->query($sql)) { echo "\n{$RED}Importer have fail.\n\n{$WHITE}"; logErr(); }
    
}

unlink('/home/aces/run/aces_import_from_xc.pid');
echo "{$GREEN}\n\nFinished...\n{$WHITE}";
die;












echo "\n\nImporting Series...\n";
$r=$DB_XC->query("SELECT * FROM series ");
while($row=mysqli_fetch_array($r)) {
    
    $name = mysqli_real_escape_string($DB,$row['title']);
    $desc = mysqli_real_escape_string($DB,$info['plot']);
    
    $year = date("Y",strtotime($info['releaseDate']));
    
    $genres = explode ('/',$info['genre']);
    $genre1 = trim($genres[0]);
    $genre2 = trim($genres[1]);
    $genre3 = trim($genres[2]);
    
    $tmdb_id = $info['tmdb_id'];
    
    
    $sql = "INSERT INTO iptv_ondemand (name,enable,category_id,type,genre1,genre2,genre3,year,about,tmdb_id) VALUES('$name',1,'{$row['category_id']}','series','$genre1','$genre2','$genre3','$year','$desc','$tmdb_id') ";
    if(!$DB->query($sql)) echo mysqli_error($DB);
    
    $r2=$DB_XC->query("SELECT stream_id FROM series_episodes WHERE series_id = {$row['id']}");
    while($row2=mysqli_fetch_array($r2)) { 
        $r3=$DB_XC->query("SELECT * FROM stream WHERE id = {$row2['stream_id']} ");
        if($row3=mysqli_fetch_array($r3)){ 
            if (preg_match("'^(.+)\.S([0-9]+)E([0-9]+).*$'i",$row3['stream_display_name'],$n)) {

                $name = preg_replace("'\.'"," ",$n[1]);
                $season = intval($n[2],10);
                $episode = intval($n[3],10);
                
                echo  "ADDING $name S$season E$episode";
                die;
            }
        }
        
    }
    
    die;
    
}


echo "\n\nImporting movies...\n";
$r=$DB_XC->query("SELECT * FROM streams WHERE type = 2 ");
while($row=mysqli_fetch_array($r)) { 
    
    $name=mysqli_real_escape_string($DB,$row['stream_display_name']);
        
    $info = json_decode($row['movie_propeties'],1);
    
    $desc = mysqli_real_escape_string($DB,$info['plot']);
    $genres = explode ('/',$info['genre']);
    $genre1 = trim($genres[0]);
    $genre2 = trim($genres[1]);
    $genre3 = trim($genres[2]);
    $tmdb_id = $info['tmdb_id'];
    
    $year = date("Y",strtotime($info['releasedate']));
    
    $img = get_string_between($info['movie_image']."%{end}%", "images/", "%{end}%");
    if(is_file("/home/xtreamcodes/iptv_xtream_codes/wwwdir/images/$img")) copy("/home/xtreamcodes/iptv_xtream_codes/wwwdir/images/$img", "/home/aces/logos/$img");
    else l("\ignoring images file '$img' could not be found on server.\n");
    
    $sql = "INSERT INTO iptv_ondemand (name,enable,category_id,type,genre1,genre2,genre3,year,about,tmdb_id) VALUES('$name',1,'{$row['category_id']}','movies','$genre1','$genre2','$genre3','$year','$desc','$tmdb_id') ";
    $DB->query($sql);
    die;
}

die;