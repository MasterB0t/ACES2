<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}


$server_id = (int)$_POST['get_server_content'];
$Server = new \ACES2\IPTV\SERVER($server_id);

if ( !in_array($_POST['type'], array('vods', 'subs', 'directories')) )
    setAjaxComplete();

$data = array('path' => $_POST['path'], 'type' => $_POST['type']);
$resp = $Server->send_action($Server::ACTION_GET_CONTENT, $data);


$htm = "";
if ($_POST['type'] == 'directories') $htm .= "<option value='{$_POST['path']}'> " . urldecode($_POST['path']) . "</option>";
$htm .= '<option value="/">/</option>';

if ($_POST['path'] != '/') {
    $paths = explode("/", urldecode($_POST['path']));
    $c = count($paths);
    unset($paths[$c - 1]);
    $go_back = implode("/", $paths);
    $htm .= "<option value='$go_back'>..</option>";
}

if (isset($_POST['get_as_array'])) {

    $htm = $resp['content'];

} else foreach ($resp['content'] as $i) {

    $name = str_replace(urldecode($_POST['path']) . "/", '', $i);
    $i = urlencode($i);
    $htm .= "<option value='$i'>" . $name . "</option>";

}




$json['content'] = $htm;
echo json_encode($json);