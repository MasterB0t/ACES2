<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    http_response_code(403);
    setAjaxError(\ACES2\ERRORS::NO_PRIVILEGES);
    die;
}



session_write_close();
if(!$ServerID = (int)$_POST['get_server_content'])
    setAjaxComplete();
else if(!in_array($_POST['type'], array( 'vods', 'subs', 'directories' ) ))
    setAjaxComplete();

$Server = new \ACES2\IPTV\Server($ServerID);
$resp = $Server->send_action(\ACES2\IPTV\Server::ACTION_GET_CONTENT, array('path'=>$_POST['path'],'type'=>$_POST['type']));

$htm="";
if($_POST['type'] == 'directories' ) $htm .= "<option value='{$_POST['path']}'> ".urldecode($_POST['path'])."</option>";
$htm.='<option value="/">/</option>';

if($_POST['path'] != '/') {
    $paths=explode("/",urldecode($_POST['path']));
    $c=count($paths);
    unset( $paths[$c-1] );
    $go_back=implode("/",$paths);
    $htm .= "<option value='$go_back'>..</option>";
}

if(isset($_POST['get_as_array'])) {

    $htm = $resp['content'];

} else foreach($resp['content'] as $i ) {

    $name = str_replace(urldecode($_POST['path'])."/", '', $i);
    $i=urlencode($i);
    $htm .= "<option value='$i'>".$name."</option>";

}

    $json['content'] = $htm;

echo json_encode($json);

