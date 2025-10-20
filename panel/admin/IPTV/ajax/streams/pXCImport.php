<?php

use ACES2\IPTV\XCAPI\XCAccount;

if (!adminIsLogged()) {
    http_response_code(401);
    die;
}

$ADMIN = new \ACES2\ADMIN ();
if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}
$XC_CATS = [];
$DB = new \ACES2\DB();

register_shutdown_function(function() {

    global $PID, $DB;

    if($PID)
        $DB->query("DELETE FROM iptv_proccess WHERE id = '$PID'");

});

if(!\ACES2\Armor\Armor::isToken('iptv.import_xc', $_REQUEST['token']))
    setAjaxError(\ACES2\ERRORS::SESSION_EXPIRED);

if(!$stream_profile = (int)$_REQUEST['stream_profile'])
    setAjaxError("Select Stream Profile");

$XCAccountID = (int)$_REQUEST['xc_account_id'];
$r=$DB->query("SELECT id,host,username,password,port FROM iptv_xc_videos_imp 
                                      WHERE id = '$XCAccountID'");

$XCAccount = new XCAccount($XCAccountID);


$url = $XCAccount->url;


$xc_portal = json_decode(file_get_contents("$url/player_api.php?username={$xc_account['username']}&password={$xc_account['password']}"),true);


if(!$category_id = (int)$_REQUEST['category_id'])
    setAjaxError("Select A category");



$import_from_cat = (int)$_REQUEST['import_from_category'];
$ondemand = isset($_REQUEST['ondemand']) ? 1 : 0;
$stream_it = $_REQUEST['stream'] ? 1 : 0;
$protocol = $_REQUEST['protocol'] == 'm3u8' ? 'm3u8' : 'ts';


if(!$server_id = (int)$_REQUEST['server_id'])
    setAjaxError("Select a Stream Server");



$ro = $DB->query("SELECT ordering FROM iptv_channels ORDER BY ordering DESC  LIMIT 1 ");
$order = (int)$ro->fetch_assoc()['ordering'];

$ro = $DB->query("SELECT number FROM iptv_channels ORDER BY number DESC  LIMIT 1 ");
$number = (int)$ro->fetch_assoc()['number'];

$rco = $DB->query("SELECT ordering FROM iptv_stream_categories ORDER BY ordering DESC  LIMIT 1 ");
$category_ordering = $rco->fetch_assoc()['ordering'];

session_write_close();
echo json_encode(array('status' => 1 ));
fastcgi_finish_request();
set_time_limit(-1);

$query_from_cat = (int)$import_from_cat > 0 ? "&category_id=".$import_from_cat : '';

$xc_streams = json_decode(file_get_contents("$url/player_api.php?username={$XCAccount->username}&password={$XCAccount->password}&action=get_live_streams$query_from_cat"),true);
if(!is_array($xc_streams))
    exit;

$xc_categories = [];
if($category_id == -1 ) {
    $xc_cats = json_decode(file_get_contents("$url/player_api.php?username={$XCAccount->username}&password={$XCAccount->password}&action=get_live_categories"),true);
    foreach($xc_cats as $cat) $xc_categories[$cat['category_id']] = $cat['category_name'];
}

$DB->query("INSERT INTO iptv_proccess ( pid, name,  progress, description, server_id ) 
                VALUES('0','m3u_import', 0, 'Importing FROM XC {$XCAccount->host}', 1) ");
$PID = $DB->insert_id;

$progress = 0;
foreach($xc_streams as $stream ) {
    $progress++;
    //error_log(print_r($stream, true));

    $name = $DB->escString($stream['name']);
    $logo = $DB->escString($stream['stream_icon']);
    $tvg_id = $DB->escString($stream['epg_channel_id']);
    $source_url = "$url/live/{$XCAccount->username}/{$XCAccount->password}/{$stream['stream_id']}.$protocol";
    $is_added = false;

    if(!$category_id == -1 ) {
        $cat_id = $category_id;
    } else {
        //USING XC CATEGORY
        $cat_name = $xc_categories[$stream['category_id']];
        $r=$DB->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('{$xc_categories[$stream['category_id']]}') LIMIT 1");
        if(!$cat_id = $r->fetch_assoc()['id']) {
            $category_ordering++;
            $DB->query("INSERT INTO iptv_stream_categories ( name, ordering ) 
                    VALUES ('{$xc_categories[$stream['category_id']]}' , $category_ordering )");

            $cat_id = $DB->insert_id;
        }
    }

    $r=$DB->query("SELECT id FROM iptv_channels WHERE lower(name) = lower('{$name}') ");
    if(!$sid=$r->fetch_assoc()){
        $order++;
        $number++;

        $DB->query("INSERT INTO iptv_channels (type,name,tvg_id,category_id,logo,stream,ondemand,stream_server,
                           stream_profile,enable,`ordering`,number)  
                        VALUES(0,'$name','$tvg_id','$cat_id','$logo','$stream_it','$ondemand','$server_id', '$stream_profile',1,$order,$number)");

        $sid = $DB->insert_id;
        $source_prio = 0;
        $is_added = true;

        if (!empty($logo)) {
            $logo_ext = pathinfo(parse_url($logo, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (in_array($logo_ext, array('png', 'jpg', 'gif'))) {
                $logo_name = "s".$sid . "." . $logo_ext;
                if (file_put_contents('/home/aces/panel/logos/' . $logo_name, file_get_contents($logo))) {
                    $DB->query("UPDATE iptv_channels SET logo = '$logo_name' WHERE id = $sid ");
                }
            }
        }

    } else {
        $r=$DB->query("SELECT priority FROM iptv_channels_sources WHERE chan_id = '$sid' ");
        $source_prio = (int)$r->fetch_assoc()['priority'];
    }


    $DB->query("INSERT INTO iptv_channels_sources (chan_id, priority, enable , url) 
                VALUES('$sid','$source_prio',1,'$source_url') ");

    if($is_added)
        foreach ($_POST['bouquets'] as $b) {
            $bouquet = (int)$b;
            if ($bouquet)
                $DB->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id,chan_id) VALUES('$bouquet','$sid') ");
        }

    $p = (int)$progress / count($xc_streams) * 100;
    $rp=$DB->query("SELECT id FROM iptv_proccess WHERE id = $PID ");
    if($rp->num_rows < 1) exit;
    $DB->query("UPDATE iptv_proccess SET progress = '$p' WHERE id = '$PID'");

}