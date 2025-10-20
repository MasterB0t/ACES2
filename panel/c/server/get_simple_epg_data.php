<?php
header('Content-Type: application/json');
header('Connection: close');


//include_once '/home/aces/panel/class/EpgParser.php';

function set_error($err_msg) {  
    
    AcesLogE($err_msg);
    echo '{"js":[]}';
    die;
    
}

//try {
//
//    $epg = new EgpParser('/home/aces/guide/guide.xml');
//
//} catch (Exception $ex) {
//    set_error('Fail to initialize EpgParser');
//    $js=array (
//        'js' => 
//        array (
//          'cur_page' => 1,
//          'selected_item' => 1,
//          'total_items' => 1,
//          'max_page_items' => 1,
//          'data' => array()
//        ),
//      );
//    echo json_encode($js);die;
//    die;
//} 


$p = 0;
if(is_numeric($_GET['p']) && $_GET['p'] > 0 ) { 
    if($_GET['p'] > 1 ) $p = (($_GET['p']-1) * 10 );
    else $p = 0;  
} else $_GET['p'] = 1;

error_log("ID :".$_GET['ch_id']);

if(empty($_GET['ch_id']) || !is_numeric($_GET['ch_id']) || $_GET['ch_id'] < 1 ) { set_error('Not a valid channel id.'); die; }
$r=$DB->query("SELECT id FROM iptv_channels WHERE ordering = '{$_GET['ch_id']}'  ");
if(!$chan_id=mysqli_fetch_array($r)['id']) set_error('Channel could not be found.'); 

date_default_timezone_set($SERVER_TIMEZONE);
$server_offset = date('Z');

if($TIMEZONE) date_default_timezone_set($TIMEZONE);

$data=array();
//GETTING TOTAL.
//$r=$DB->query("SELECT title,description,start_date,end_date FROM iptv_epg WHERE chan_id = '{$_GET['ch_id']}' AND start_date >= date('{$_GET['date']}') AND end_date <= date_format(date('{$_GET['date']}') +INTERVAL 1 DAY,'%Y-%m-%d %H:%i:%s')  ");
//$r=$DB->query("SELECT title,description,start_date,end_date FROM iptv_epg WHERE chan_id = '{$_GET['ch_id']}' AND start_date >= date('{$_GET['date']}') ");
$r=$DB->query("SELECT title,description,start_date,end_date FROM iptv_epg WHERE chan_id = '$chan_id' AND start_date >= date('{$_GET['date']}') group by start_date  ");
$total_items=mysqli_num_rows($r);

//$r=$DB->query("SELECT title,description,date_format(start_date,'%Y-%m-%d %H:%i:%s') as start_date,date_format(end_date,'%Y-%m-%d %H:%i:%s') as end_date, UNIX_TIMESTAMP(start_date) as start_time  FROM iptv_epg WHERE chan_id = '{$_GET['ch_id']}' AND start_date >=  date('{$_GET['date']}') AND  end_date <= date_format(date('{$_GET['date']}') +INTERVAL 1 DAY,'%Y-%m-%d %H:%i:%s') LIMIT $p,10 ");
//$r=$DB->query("SELECT title,description,start_date,date_format(start_date,'%Y-%m-%d %H:%i:%s') as start,date_format(end_date,'%Y-%m-%d %H:%i:%s') as end, UNIX_TIMESTAMP(start_date) as start_time  FROM iptv_epg WHERE chan_id = '{$_GET['ch_id']}' AND start_date >=  date('{$_GET['date']}')  LIMIT $p,10 ");
$r=$DB->query("SELECT title,description,start_date,date_format(start_date,'%Y-%m-%d %H:%i:%s') as start,date_format(end_date,'%Y-%m-%d %H:%i:%s') as end, UNIX_TIMESTAMP(start_date) as start_time  FROM iptv_epg WHERE chan_id = '$chan_id' AND start_date >=  date('{$_GET['date']}') group by start_date LIMIT $p,10 ");

while($row=mysqli_fetch_array($r)) {  
    
    //$offset = date('Z') - $server_offset;
    
    $stime=strtotime($row['start']) + $offset ;
    $etime=strtotime($row['end']) + $offset ;
    
    $rec=0;$r_id=0;
    //$start_time = (strtotime($row['start_date'])+$server_offset);
    //$rr=$DB->query("SELECT id,chan_id,title,start_time FROM iptv_recording WHERE chan_id = '{$_GET['ch_id']}' AND start_time = FROM_UNIXTIME($start_time) AND status = 3 ");
    
    //$c_date = strtotime($row['start_date']) + $server_offset ;
    
    $rr=$DB->query("SELECT id,chan_id,title,start_time FROM iptv_recording WHERE chan_id = '$chan_id' AND start_time = '{$row['start_date']}' AND status = 3 ");
    if($rr_row=mysqli_fetch_array($rr)){ $rec=1; $r_id = $rr_row['id']; }
    
    $d = array();
    $d['id'] = $r_id;
    $d['ch_id'] = $_GET['ch_id'];
    $d['correct'] = $r_id;
    $d['time'] = date('Y-m-d H:i:s',$stime);
    $d['time_to'] = date('Y-m-d H:i:s',$etime);
    $d['duration'] = $etime - $stime;
    $d['name'] = $row['title'];
    $d['descr'] = $row['description'];
    $d['real_id'] = 0;
    $d['category'] = '';
    $d['directot'] = '';
    $d['actor'] = '';
    $d['start_timestamp'] = $stime;
    $d['stop_timestamp'] = $etime;
    $d['t_time'] = date("H:i",$stime);
    $d['t_time_to'] =  date("H:i",$etime);
    $d['open'] = 1;
    $d['mark_memo'] = 0;
    $d['mark_archive'] = $rec;
    $d['mark_rec'] =0;
    $data[] = $d;
    
}

$js=array (
  'js' => 
  array (
    'cur_page' => $_GET['p'],
    'selected_item' => 1,
    'total_items' => $total_items,
    'max_page_items' => 10,
    'data' => $data
  ),
);


echo json_encode($js);die;