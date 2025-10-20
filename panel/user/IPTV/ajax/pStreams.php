<?php
if(!$UserID=userIsLogged())
    setAjaxError("",403);

$USER = new \ACES2\IPTV\Reseller2($UserID);
if(!$USER->isAllowRestartStreams())
    exit;



$StreamID = (int)$_REQUEST['stream_ids'];
if(!is_array($_REQUEST['stream_ids'])) {
    $Stream = new \ACES2\IPTV\Stream((int)$_REQUEST['stream_ids']);
    if($Stream->enabled) {
        $Stream->restart();
    }
} else {
    foreach($_REQUEST['stream_ids'] as $id) {
        $Stream = new \ACES2\IPTV\Stream($id);
        if($Stream->enabled)
            $Stream->restart();
    }
}

setAjaxComplete();