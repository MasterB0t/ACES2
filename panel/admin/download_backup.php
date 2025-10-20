<?php
ini_set('memory_limit', '-1');

//require '/home/aces/panel/ACES1/init.php';
require_once '/home/aces/panel/ACES2/init.php';

$ADMIN = new \ACES2\ADMIN();
if(!$ADMIN->isLogged() OR !$ADMIN->hasPermission('')) {
    http_response_code(403);
    die;
}

clearstatcache();
$filename = urldecode($_GET['filename']);


if(!file_exists("/home/aces/backups/$filename")) {
    http_response_code(404);
    die;
}



header("Content-Disposition: attachment; filename=$filename");
header('Content-type: application/tar-gz' );
header("Connection: close");

@$fp = fopen("/home/aces/backups/$filename", 'rb');
@fpassthru($fp);
@fclose($fp);

