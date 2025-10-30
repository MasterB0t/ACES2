<?php

use ACES2\IPTV\Stream;
use ACES2\IPTV\StreamEvent;

if(!$AdminID=adminIsLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

$Events = array('MLB', 'NFL');
$db = new \ACES2\DB;
$json = [];

switch($_REQUEST['action']) {

    case 'add':

        $event = strtoupper($_REQUEST['event_type']);

        $Event = StreamEvent::add($event, $_REQUEST['providers'], (bool)$_REQUEST['active'],
            (bool)$_REQUEST['hide_streams_on_no_event']);

        $event_id = $db->insert_id;
        $type = \ACES2\IPTV\Stream::CHANNEL_TYPE_EVENT;

//        foreach($_REQUEST['streams'] as $s) {
//            $sid = (int)$s;
//            //$db->query("UPDATE iptv_channels SET event_type = '$s'  WHERE id = '$sid'");
//            $db->query("UPDATE iptv_channels SET event_id = '$event_id', type = '$type'  WHERE id = '$sid'");
//        }


        setAjaxComplete('', false);

        try {
            if(isset($_REQUEST['pre_stream'])) {
                $Event->setStreamFont($_REQUEST['stream_font']);
                $Event->setStreamImage($_REQUEST['stream_image']);
                $Event->setStreamFontColor($_REQUEST['stream_font_color']);
                $Event->setStreamFontSize($_REQUEST['stream_font_size']);
                $Event->setPreStream(true);
                $Event->save();
            }
        } catch(\Exception $e) {
            logE($e->getMessage());
            $ignore = 0;
        }


        for($i = 0; $i < $_REQUEST['add_streams']; $i++) {
            $name = "$event #".$i + 1;
            $stream = Stream::addStream2($name, $_REQUEST['server'], $_REQUEST['category'],
                $_REQUEST['stream_profile'], (bool)$_REQUEST['stream'], (bool)$_REQUEST['ondemand']);
            $stream->setEvent($Event->id);
            $stream->setBouquets($_REQUEST['bouquets']);
            if(!empty($_POST['logo_url']))
                $stream->setLogo($_POST['logo_url']);
            $stream->save();
        }



        break;

    case 'update':

//        $event_id = (int)$_REQUEST['event_id'];
//
//        $r=$db->query("SELECT type FROM iptv_dynamic_events WHERE id = '$event_id' ");
//        if(!$event = $r->fetch_assoc()['type'])
//            setAjaxError("Event does not exist.");
//
//        $active = (bool)$_REQUEST['active'];
//        $hide_streams = (bool)$_REQUEST['hide_streams_on_no_event'];
//
//        $providers = [];
//        foreach($_REQUEST['providers'] as $p) {
//            $providers[] = (int)$p;
//        }
//        $providers = json_encode($providers);
//
//        $db->query("UPDATE iptv_dynamic_events SET active = '$active', providers = '$providers',
//                               hide_streams_on_no_event = '$hide_streams'
//                           WHERE id = '$event_id'");

        $Event = new StreamEvent((int)$_REQUEST['event_id']);
        $Event->setActive((bool)$_REQUEST['active']);
        $Event->setHideStreamsOnNoEvent((bool)$_REQUEST['hide_streams_on_no_event']);
        $Event->setProviders((array)$_REQUEST['providers']);

        if(!isset($_REQUEST['pre_stream']))
            $Event->setPreStream(false);
        else {
            $Event->setStreamFont($_REQUEST['stream_font']);
            $Event->setStreamImage($_REQUEST['stream_image']);
            $Event->setStreamFontColor($_REQUEST['stream_font_color']);
            $Event->setStreamFontSize($_REQUEST['stream_font_size']);
            $Event->setPreStream(true);
        }

        $Event->save();

        $stream_type =  Stream::CHANNEL_TYPE_STREAM;
        $event_type = Stream::CHANNEL_TYPE_EVENT;

        //$db->query("UPDATE iptv_channels SET event_type = '' WHERE event_type = '$event'");
        $db->query("UPDATE iptv_channels SET event_id = 0,  type = '$stream_type' WHERE event_id = '$Event->id'");
        foreach($_REQUEST['streams'] as $s) {
            $sid = (int)$s;
            $db->query("UPDATE iptv_channels SET event_id = '$Event->id', type = '$event_type' WHERE id = '$sid'");
        }

        break;

    case 'remove' :
        $event_id = (int)$_REQUEST['event_id'];
        $r=$db->query("SELECT type FROM iptv_dynamic_events WHERE id = '$event_id' ");
        $event_type = $r->fetch_assoc()['type'];
        $db->query("DELETE FROM iptv_dynamic_events WHERE id = '$event_id'");
        $db->query("DELETE FROM iptv_stream_events WHERE type = '$event_type'");
        $db->query("UPDATE iptv_channels SET event_id = 0, type = '0', tvg_id = '' WHERE event_id = '$event_id'");
        break;

    case 'scan':
        $event_id = (int)$_REQUEST['event_id'];
        exec("php /home/aces/bin/iptv_automation_events.php $event_id > /dev/null &");
        break;


    default:
        logD("Unknown action");
        setAjaxError("System Error");

}

setAjaxComplete();