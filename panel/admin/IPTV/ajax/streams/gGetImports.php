<?php


$ADMIN = new \ACES2\Admin();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS)) {
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES,403);
    die;
}

$DB= new \ACES2\DB();
$data = [];
$r=$DB->query("SELECT id,name,progress,description FROM iptv_proccess WHERE name LIKE 'm3u_import' ");
while($row=$r->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);