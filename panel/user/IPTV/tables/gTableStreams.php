<?php

header('Content-Type: application/json');
header('Connection: close');


$DB = new \ACES2\DB();


if (!$UserID=userIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

}

$User = new \ACES2\IPTV\Reseller2($UserID);

if (!$User->allow_channel_list ) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}


$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";

if(@!empty($_GET['filter_status']))
$filters[] = match($_GET['filter_status']) {
    'streaming' => 's.status = 1',
    'connecting' => 's.status = 0',
    'stopped' => 's.id is NULL AND a.stream = 1',
};

if(@(int)$_GET['filter_category']) {
    $filters[] = 'a.category_id = ' . (int)$_GET['filter_category'];
}

if((int)@$_GET['filter_category'])
    $filters[] = " a.category_id = ".(int)@$_GET['filter_category'];




$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " a.name LIKE '%$s%' AND $filter_sql ";
    $search .= " OR cat.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR a.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY a.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY a.name $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY TIMEDIFF(NOW(),s.connected_datetime) $order_dir ";
else if($_GET['order'][0]['column'] == 5 ) $Order = " ORDER BY cat.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT a.id FROM iptv_channels a
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT a.id FROM iptv_channels a
    LEFT JOIN iptv_streaming s ON s.chan_id = a.id AND s.server_id = a.stream_server
    LEFT JOIN iptv_stream_categories cat ON cat.id = a.category_id
 $Where GROUP BY a.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT s.status,a.name, a.stream, cat.name as category_name, a.logo, a.id, s.info, s.video_codec, s.audio_codec, 
       s.status as streaming_status,  s.start_time as stream_start_time, s.total_down_seconds, s.reconnected, s.id as streaming_id,
       s.connected_datetime, s.bitrate,

CONCAT(
'D',FLOOR(HOUR(TIMEDIFF(NOW(), s.connected_datetime)) / 24), '  ',
DATE_FORMAT(TIMEDIFF(NOW(),  TIMESTAMPADD(DAY,FLOOR(HOUR(TIMEDIFF(NOW(), s.connected_datetime)) / 24),s.connected_datetime)  )  , '%H'), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.connected_datetime), '%i'  ), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.connected_datetime),'%s'), '') as streaming_started ,
    
CONCAT(
'D',FLOOR(HOUR(TIMEDIFF(NOW(), s.disconnected_datetime)) / 24), '  ',
DATE_FORMAT(TIMEDIFF(NOW(),  TIMESTAMPADD(DAY,FLOOR(HOUR(TIMEDIFF(NOW(), s.disconnected_datetime)) / 24),s.disconnected_datetime)  )  , '%H'), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.disconnected_datetime),'%i' ), ':',
DATE_FORMAT(TIMEDIFF(NOW(),  s.disconnected_datetime),'%s'), '') as down_time

    FROM iptv_channels a
    LEFT JOIN iptv_streaming s ON s.chan_id = a.id AND s.server_id = a.stream_server
    LEFT JOIN iptv_stream_categories cat ON a.category_id = cat.id
   
$Where GROUP BY a.id $Order LIMIT $start,$limit ");



if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href=\"#!\" title=\"Restart Stream\" 
        onClick=\"restartStream({$row['id']} );\"> <i style=\"margin:5px;\" class=\" fa fa-play fa-lg\"></i>  </a>";

    $row['stream_start_time'] = time() -  $row['stream_start_time']  ;
    if( !$row['total_down_seconds'] || $row['stream_start_time'] <  $row['total_down_seconds']) $total_uptime =0;
    else $total_uptime =  round ((( $row['stream_start_time']  - $row['total_down_seconds']) / $row['stream_start_time']) * 100) ;

    $status = "";$stime = 0; $s_info = ''; $bitrate = '';
    if($row['streaming_id']) {
        if ($row['streaming_status'] == 1 ) {
            $status = "<p style='color:green'> CONNECTED </p>";
            $stime = "<b title='Up Time' style='color:green'> {$row['streaming_started']} <p title='Total up time'> $total_uptime</p> </b>";
            $s_info = unserialize($row['info']);
            $s_info = "{$row['video_codec']}/{$row['audio_codec']} {$s_info['resolution']} fps:{$s_info['frames']} ";
            $bitrate = round($row['bitrate'] / 1000) . "k";
        } else if($row['streaming_status'] == 0) {
            $status = "<p style='color:orange'> CONNECTING </p>";
        } else if($row['streaming_status'] == 2 ) {
            $status = "<p style='color:orange'> STAND BY 2</p> ";
        }
    } else {
        if($row['stream'] == 0 ) $status = "<p style='color:orange'> STAND BY </p> ";
        else $status = "<p style=\"color:red\">Stopped </p>";
        $stime = 0;
    }

    $logo = '';
    if (filter_var($row['logo'], FILTER_VALIDATE_URL))
        $logo = "<br/><br/><img width=80 src='{$row['logo']}'/>";
    else if($row['logo'])
        $logo = "<br/><br/><img width=80 src='".HOST."/logos/{$row['logo']}'/>";

    $EPG = new \ACES2\IPTV\ChannelEpg($row['id']);
    $epgs = $EPG->getEpg();

    $html_epg = '';

    if(is_array($epgs))
        for($i=0;count($epgs) > $i; $i++) {
            $e = $epgs[$i];
            if($i == 0 ) { $html_epg = "<details><summary><p>{$e['start_date']}:{$e['end_date']} - {$e['title']} <b>Click To Expand</b></p></summary>"; }
            else $html_epg .= "<p>{$e['start_date']} : {$e['end_date']} - {$e['title']}</p>";
        }
    $html_epg .= "</details>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'].$logo,
        $status."</br>".$html_epg,
        $stime,
        $s_info,
        $row['category_name'],
        $links

    );

}

echo json_encode($json);