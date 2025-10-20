<?php

$SS = time();
#include_once '/home/aces/panel/class/EpgParser.php';

function set_error($err_msg='') {

    error_log($err_msg);
    echo '{"js":[]}';
    die;
    
}

//try {
//
//    $epg = new EgpParser('/home/aces/guide/guide.xml');
//
//} catch (Exception $ex) {
//    set_error('Fail to initialize EpgParser');
//    echo '{"js":[]}';
//    die;
//}

#$epg->max_programme = 4;

if(!$chan_id = (int)$_GET['ch_id'])
    set_error("'{$_GET['ch_id']}' Not a valid channel id");

$r=$DB->query("SELECT id FROM iptv_channels WHERE ordering = {$chan_id}  ");
if(!$tvg_id = $r->fetch_assoc()['id'])
    set_error("Channel {$chan_id} could not be found.");


date_default_timezone_set($SERVER_TIMEZONE);
$server_offset = date('Z');

if($TIMEZONE) date_default_timezone_set($TIMEZONE);

//if($TIMEZONE) $epg->setClientTimezone($TIMEZONE);
//if(!$epg = $epg->getProgramme($tvg_id)) { echo '{"js":[]}'; die;  }

$data = array();
//$r=$DB->query("SELECT start_date as start,end_date as end,title,description FROM iptv_epg WHERE chan_id = '$tvg_id' AND end_date > NOW() ORDER BY start_date LIMIT 4  ");
$r=$DB->query("SELECT start_date as start,end_date as end,title,description FROM iptv_epg WHERE chan_id = '$tvg_id' AND end_date > NOW() LIMIT 4  ");


//foreach($epg as $e) {
while($e=mysqli_fetch_array($r)) { 

    $offset = date('Z') - $server_offset;
    
    $stime=strtotime($e['start']) + $offset ;
    $etime=strtotime($e['end']) + $offset ;
    
    $d = array();
    $d['id'] = 0;
    $d['ch_id'] = $chan_id;
    $d['correct'] = date('Y-m-d H:i:s',$stime);
    $d['time'] = date('Y-m-d H:i:s',$stime);
    $d['time_to'] = date('Y-m-d H:i:s',$etime);
    $d['duration'] = $etime - $stime;
    $d['name'] = $e['title'];
    $d['descr'] = $e['description'];
    $d['real_id'] = 0;
    $d['category'] = '';
    $d['directot'] = '';
    $d['actor'] = '';
    $d['start_timestamp'] = $stime;
    $d['stop_timestamp'] = $etime;
    $d['t_time'] = date("H:i",$stime);
    $d['t_time_to'] = date("H:i",$etime);
    $d['mark_memo'] = 0;
    $d['mark_archive'] = 0;
    $data[] = $d;
    
}
//AcesLogD(print_r($epg,1));
$json['js'] = $data;
echo json_encode($json);

die;
