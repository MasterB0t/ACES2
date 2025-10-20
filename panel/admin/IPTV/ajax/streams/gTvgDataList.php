<?php
$ADMIN = new \ACES2\Admin();
$json = [];
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}


session_write_close();

$db = new \ACES2\DB;
$search = $db->escString($_POST['get_tvg_datalist']);
if(strlen($search) > 2) {
    $r = $db->query("SELECT imp.channel_name,s.name as epg_name, imp.tvg_id 
        FROM iptv_imported_epg_names imp 
        RIGHT JOIN iptv_epg_sources s ON s.id = imp.epg_id
        WHERE lower(imp.channel_name) like lower('%$search%') AND s.status = 1
    ");

    $json = $r->fetch_all(MYSQLI_ASSOC);
}


//include_once '/home/aces/panel/class/EpgParser.php';
//foreach (glob("/home/aces/imported_guides/*.xml") as $filename) {
//
//    $EPG = new EgpParser($filename);
//    $r=$EPG->searchChannels($_POST['get_tvg_datalist']);
//    if(count($r) > 0 ) $json['db'][ basename($filename) ] = $r;
//
//}
//

echo json_encode($json);
exit;