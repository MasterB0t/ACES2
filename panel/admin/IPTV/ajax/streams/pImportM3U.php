<?php

$ADMIN = new \ACES2\ADMIN;
$DB = new \ACES2\DB;

if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES,403);
    die;
}

if(!\ACES2\Armor\Armor::isToken('iptv.m3u_import', $_REQUEST['token']))
    setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

register_shutdown_function(function() {
    global $PID,$DB;
    if($PID)
        $DB->query("DELETE FROM iptv_proccess WHERE id = '{$PID}'");
});

$server = new \ACES2\IPTV\SERVER($_POST['server_id']);
$StreamProfile = new \ACES2\IPTV\StreamProfile($_POST['stream_profile']);

$server_id = $server->id;
$category_id = (int)$_POST['category_id'];
$stream_profile = $StreamProfile->id;

$ondemand = $_POST['ondemand'] ? 1 : 0;
$stream = $_POST['stream'] ? 1 : 0;

$force_group = $_POST['force_group'] ? 1 : 0;


if (empty($_FILES['m3u_file']['tmp_name'])) {
    ajaxError("Please select a playlist file to upload.");
}

if (!empty($_FILES['m3u_file']['tmp_name'])) {

    if ($_FILES['m3u_file']['size'] > 10000000)
        ajaxError('This file is too big.');

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    if (false === $logo_ext = array_search(
            $finfo->file($_FILES['m3u_file']['tmp_name']), array(
            'm3u8' => 'text/plain',
            'm3u' => 'text/plain',
            'text' => 'text/plain'
        ), true
        )) {
        ajaxError('Please enter a valid M3U playlist.' . $finfo->file($_FILES['m3u_file']['tmp_name']));
    }
}


$DB->query("INSERT INTO iptv_proccess ( pid, name,  progress, description, server_id ) 
                VALUES('0','m3u_import', 'Importing Playlist {$_FILES['m3u_file']['name']}', 0, 1) ");
$PID = $DB->insert_id;;

ignore_user_abort(true);
set_time_limit(0);
session_write_close();
echo json_encode(array('complete' => 1, 'status' => 1));
fastcgi_finish_request();

ini_set('default_socket_timeout', 3);

$ro = $DB->query("SELECT ordering FROM iptv_channels ORDER BY ordering DESC  LIMIT 1 ");
$order = (int)$ro->fetch_assoc()['ordering'];

$ro = $DB->query("SELECT number FROM iptv_channels ORDER BY number DESC  LIMIT 1 ");
$number = (int)$ro->fetch_assoc()['number'];

$rco = $DB->query("SELECT ordering FROM iptv_stream_categories ORDER BY ordering DESC  LIMIT 1 ");
$category_ordering = $rco->fetch_assoc()['ordering'];

$Progress = 0;
$TotalLines = intval(exec("wc -l '{$_FILES['m3u_file']['tmp_name']}'"));
$CurrentLine = 0;

if (!$handle = fopen($_FILES['m3u_file']['tmp_name'], "r")) exit;
while (($line = fgets($handle)) !== false) {
    $CurrentLine++;
    if (strpos($line, 'EXTINF:')) {

        if (preg_match('/group-title="(.*?)"/', $line, $g) === 1) {
            $c_group = mb_convert_encoding(utf8_encode($g[1]), "UTF-8", "auto");
        } else $c_group = '';

        $logo = '';
        if (preg_match('/tvg-logo="(.*?)"/', $line, $g) === 1) {
            $logo = $g[1];
        }

        $epg = '';
        if (preg_match('/tvg-id="(.*?)"/', $line, $g) === 1) {
            $epg = $DB->escape_string($g[1]);
        }

        $ex = explode(',', $line);
        $c_name = $DB->escape_string(trim($ex[count($ex) - 1]));
        //if(!$c_name=mb_convert_encoding( utf8_encode($c_name), "UTF-8", "auto")) AcesLogE("FAIL COVERT $c_name");
        $c_name = str_replace("'", '', $c_name);

        $CurrentLine++;
        $url = trim(fgets($handle));
        $p = pathinfo($url);
        $e = strtolower(trim($p['extension']));
        if ($url && $e != 'mp4' && $e != 'mkv' && $e != 'mp3' && $e != 'mp2') {
            //WE WILL ADD THE CHANNEL IF THERE IS A URL.

            $r = $DB->query("SELECT id FROM iptv_channels WHERE lower(name) = lower('$c_name') ");
            if ($row = mysqli_fetch_assoc($r)) {

                $r2 = $DB->query("SELECT priority FROM iptv_channels_sources WHERE chan_id = {$row['id']} ORDER BY priority DESC LIMIT 1 ");
                $prio = mysqli_fetch_assoc($r2)['priority'];

                $DB->query("INSERT INTO iptv_channels_sources (chan_id,priority,enable,url) VALUES('{$row['id']}','$prio','1','$url') ");

            } else {

                $c_id = 0;

                if ( $c_group && !$category_id ) {
                    $r = $DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$c_group')  ");
                    if ($row_g = mysqli_fetch_assoc($r)) {
                        $c_id = $row_g['id'];
                    } else {
                        $category_ordering++;
                        $DB->query("INSERT INTO iptv_stream_categories (name,ordering) VALUES('$c_group','$category_ordering')");
                        $c_id = $DB->insert_id;
                    }
                } else $c_id = $category_id;

                //ONLY ADD IF THERE IS A CATEGORY
                if($c_id) {

                    $order++;
                    $number++;
                    $DB->query("INSERT INTO iptv_channels (name,category_id,tvg_id,stream,ondemand,stream_server,stream_profile,enable,
                           ordering,number) VALUES('$c_name','$c_id','$epg',$stream,$ondemand,'$server_id',$stream_profile,1,$order,$number)  ");
                    $chan_id = $DB->insert_id;

                    $DB->query("INSERT INTO iptv_channels_sources (chan_id,priority,enable,url) VALUES('$chan_id','1','1','$url') ");

                    if (!empty($logo)) {
                        $logo_ext = pathinfo(parse_url($logo, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (in_array($logo_ext, array('png', 'jpg', 'gif'))) {
                            $logo_name = $chan_id . "." . $logo_ext;
                            if (file_put_contents('/home/aces/panel/logos/' . $logo_name, file_get_contents($logo))) {
                                $DB->query("UPDATE iptv_channels SET logo = '$logo_name' WHERE id = $chan_id ");
                            }
                        }
                    }

                    foreach ($_POST['bouquets'] as $b) {
                        $bouquet = (int)$b;
                        if ($bouquet)
                            $DB->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id,chan_id) VALUES('$bouquet','$chan_id') ");
                    }
                }


            }

        }

    }

    $r=$DB->query("SELECT id FROM iptv_proccess WHERE id =  '$PID'");
    if($r->num_rows<1)
        exit;
    $Progress = (int)$CurrentLine / $TotalLines * 100;
    $DB->query("UPDATE iptv_proccess SET progress = '$Progress' WHERE id =  '$PID'");

}

fclose($handle);