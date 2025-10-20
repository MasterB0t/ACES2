<?php

if(!$AdminID=adminIsLogged()){
    Redirect("/admin/login.php");
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    Redirect("/admin/profile.php");
}

$StreamID = (int)$_REQUEST['stream_id'];
$Stream = new \ACES2\IPTV\Stream($StreamID);
$Server = new \ACES2\IPTV\Server($Stream->server_id);
$data = $Server->getStreamLogs($StreamID);

?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Stream <?=$Stream->name?> Logs</title>
    <style>
        pre {
            overflow-x:auto; margin:25px auto; border:1px solid; }
    </style>

</head>
<body>
    <h2>Stream '<?=$Stream->name?>' Logs</h2>
    <pre>
        <?=base64_decode($data)?>
    </pre>
</body>
