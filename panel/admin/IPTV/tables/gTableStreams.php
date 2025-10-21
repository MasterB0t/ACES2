<?php

use ACES2\IPTV\Streaming;

header('Content-Type: application/json');
header('Connection: close');

$PRO = 'http:';
if( isset($_SERVER['HTTPS'] ) ) $PRO = 'https:';


$ACES = new \ACES2\ADMIN();
$DB = new \ACES2\DB();
if(!adminIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

} else if(!$ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS) && !$ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}

$Can_Edit = $ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS) ? 1 : 0;

$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";
if(isset($_GET['filter_status'])) {

    if($_GET['filter_status'] == 'connecting' ) $filters[] = "  s.status = 0 ";
    else if($_GET['filter_status'] == 'ondemand') $filters[] = " s.status = 2 ";
    else if($_GET['filter_status'] == 'catchup') $filters[] = " a.catchup = 1 ";
    else if($_GET['filter_status'] == 'stopped') $filters[] = "  s.id is NULL AND a.stream = 1 ";
    else if($_GET['filter_status'] == 'streaming') $filters[] = "  s.status = 1 ";
    else if($_GET['filter_status'] == 'connected_backup') $filters[] = "  src.is_backup = 1 ";
    else if($_GET['filter_status'] == 'disabled') $filters[] = " a.enable = 0 ";
    else if($_GET['filter_status'] == 'redirect') $filters[] = "s.id is NULL AND a.stream = 0 ";

}

$detail_connection = (bool)isset($_REQUEST['filter_conns']);


if((int)@$_GET['filter_stream_id'])
    $filters[] = " a.id = {$_GET['filter_stream_id']} ";

if((int)@$_GET['filter_server'])
    $filters[] = " a.stream_server = {$_GET['filter_server']} ";

if((int)@$_GET['filter_category'])
    $filters[] = " a.category_id = {$_GET['filter_category']} ";

if((int)@$_GET['filter_stream_profile'])
    $filters[] = " a.stream_profile = {$_GET['filter_stream_profile']} ";


$filter = implode(" AND ", $filters);

$j_bouquet = '';
if((int)@$_GET['filter_bouquets'] ) {
    $j_bouquet = " INNER JOIN iptv_channels_in_bouquet pkg ON pkg.chan_id = a.id AND pkg.bouquet_id = '{$_GET['filter_bouquets']}' ";
}


$where = '';
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $where = " WHERE a.name LIKE '%$s%' AND $filter  ";

    //DO NOT FILTER BY STREAM URL.. WE HIDING STREAMING SOURCE IF IT DONT HAVE EDIT PERMISSIONS.
    if($Can_Edit)
        $where .= " OR s.streaming LIKE '%$s%' AND $filter  ";

    if(is_numeric($_GET['search']['value']))
        $where .= " OR a.id LIKE '%{$_GET['search']['value']}%' AND $filter  ";

}
else if( $filter ) $where = " WHERE $filter  ";



$order = " ";
if($_GET['order'][0]['dir'] != 'asc' && $_GET['order'][0]['dir'] != 'desc' ) $_GET['order'][0]['dir'] = 'asc';

if($_GET['order'][0]['column'] == 0 ) $order = " ORDER BY a.id {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 1 ) $order = " ORDER BY a.name {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 2) $order = " ORDER BY s.status {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 3) $order = " ORDER BY s.cache_time {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 4) { $order = " ORDER BY TIMEDIFF(NOW(),s.connected_datetime) {$_GET['order'][0]['dir']} "; }
else if($_GET['order'][0]['column'] == 5) $order = " ORDER BY c.clients {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 6) $order = " ORDER BY s.reconnected {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 9) $order = " ORDER BY srv.name {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 10) $order = " ORDER BY b.name {$_GET['order'][0]['dir']} ";
else if($_GET['order'][0]['column'] == 11) $order = " ORDER BY cat.name {$_GET['order'][0]['dir']} ";
else $order = " ORDER BY a.id {$_GET['order'][0]['dir']} ";


$r=$DB->query("SELECT id FROM iptv_channels ");
$json['draw'] = $_GET['draw'];
$json['recordsTotal'] = $r->num_rows;


//WITHOUT LIMIT TO GET RECORDS FILTERED.
$r = $DB->query ( "SELECT a.*, cat.name as category_name, srv.name as stream_server_name, b.name as stream_profile_name , 
       b.only_chan_id as  stream_profile_unique, s.id as streaming_id, s.streaming as streaming_url, s.source_id as streaming_source,
       s.status as streaming_status,  TIMEDIFF(NOW(),s.connected_datetime) as streaming_started, TIMEDIFF(NOW(),s.disconnected_datetime) as down_time, src.is_backup
FROM iptv_channels a 
LEFT JOIN iptv_servers srv ON srv.id = a.stream_server
LEFT JOIN iptv_stream_options b ON a.stream_profile = b.id  
LEFT JOIN iptv_streaming s ON s.chan_id = a.id AND s.server_id = a.stream_server 
LEFT JOIN iptv_channels_sources src ON src.id = s.source_id
LEFT JOIN iptv_stream_categories cat ON a.category_id = cat.id
$j_bouquet


$where  
GROUP BY a.id 
");
$json['recordsFiltered'] = $r->num_rows;

$r = $DB->query ( "SELECT a.*, cat.name as category_name, srv.name as stream_server_name, c.clients, b.name as stream_profile_name , 
       b.only_chan_id as  stream_profile_unique, s.id as streaming_id, s.streaming as streaming_url, s.source_id as streaming_source, 
       s.status as streaming_status, s.connection_speed as streaming_speed, s.cache_time as streaming_cache, s.buffer as streaming_buffer,
       s.start_time as stream_start_time, s.total_down_seconds, s.reconnected, s.connected_datetime, 
       src.is_backup, s.video_codec, s.audio_codec, s.bitrate, s.info, s.action as stream_action, 
    
CONCAT(
'D',FLOOR(HOUR(TIMEDIFF(NOW(), s.connected_datetime)) / 24), '  ',
DATE_FORMAT(TIMEDIFF(NOW(),  TIMESTAMPADD(DAY,FLOOR(HOUR(TIMEDIFF(NOW(), s.connected_datetime)) / 24),s.connected_datetime)  )  , '%H'), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.connected_datetime), '%i'  ), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.connected_datetime),'%s'), '') as streaming_started ,
    
CONCAT(
'D',FLOOR(HOUR(TIMEDIFF(NOW(), s.disconnected_datetime)) / 24), '  ',
DATE_FORMAT(TIMEDIFF(NOW(),  TIMESTAMPADD(DAY,FLOOR(HOUR(TIMEDIFF(NOW(), s.disconnected_datetime)) / 24),s.disconnected_datetime)  )  , '%H'), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.disconnected_datetime),'%i' ), ':',
DATE_FORMAT(TIMEDIFF(NOW(), s.disconnected_datetime),'%s'), '') as down_time
    
FROM iptv_channels a 
INNER JOIN iptv_servers srv ON srv.id = a.stream_server
LEFT JOIN iptv_stream_options b ON a.stream_profile = b.id  
LEFT JOIN iptv_streaming s ON s.chan_id = a.id AND s.server_id = a.stream_server 
LEFT JOIN iptv_channels_sources src ON src.id = s.source_id
LEFT JOIN iptv_stream_categories cat ON a.category_id = cat.id
$j_bouquet
LEFT JOIN ( 
    SELECT chan_id, count( distinct id ) as clients FROM iptv_access WHERE device_id != 0 AND limit_time > NOW() GROUP BY chan_id  ) c ON c.chan_id = a.id

$where 
GROUP BY a.id 
$order
LIMIT $start,$limit

");


if($r->num_rows < 1)  $json['data'] = array();
else while($row=$r->fetch_assoc()) {

    $EPG = new \ACES2\IPTV\ChannelEpg($row['id']);
    $epgs = $EPG->getEpg();

    $html_epg = '';

    //FIX FOR PHP8
    if(is_array($epgs))
        for($i=0;count($epgs) > $i; $i++) {
            $e = $epgs[$i];
            if($i == 0 ) { $html_epg = "<details><summary><p>{$e['start_date']}:{$e['end_date']} - {$e['title']} <b>Click To Expand</b></p></summary>"; }
            else $html_epg .= "<p>{$e['start_date']} : {$e['end_date']} - {$e['title']}</p>";
        }
    $html_epg .= "</details>";

    $lbs='';
    $r_lb = $DB->query("SELECT destination_server as destination, source_server as source FROM iptv_channels_in_lb WHERE channel_id = '{$row['id']}'");
    while($LB = $r_lb->fetch_assoc()) {

        $dest = $LB['destination'];
        $source = $LB['source'];

        $rlb = $DB->query("SELECT i.name,s.status FROM iptv_servers i LEFT JOIN iptv_streaming s ON s.server_id = $dest AND s.chan_id = '{$row['id']}' WHERE i.id = $dest ");
        if ($row_lb = mysqli_fetch_array($rlb)) {
            if ($row_lb['status'] == 1) $lbs .= "</br><i style='color:green' title='connected' class='fa fa-circle'></i> {$row_lb['name']}";
            else if ($row_lb['status'] === 0) $lbs .= "</br><i style='color:orange' title='connecting' class='fa fa-circle'></i> {$row_lb['name']}";
            else $lbs .= "</br><i style='color:red' class='fa fa-circle'></i> {$row_lb['name']}";
        }
    }


    $links = "";
    //$links .= "<a href=\"#!\" title=\"Edit Stream\" onClick=\"modalQuickEdit($(this).parent().parent(),{$row['id']});\"> <i style=\"margin:5px;\" class=\" fa fa-edit fa-lg\"></i></a>";
    if($row['type'] == 1 ) {
        $links .= "<a href=\"form/streams/formChannel.php?stream_id={$row['id']}\" title=\"Edit Stream\" > <i style=\"margin:5px;\" class=\" fa fa-edit fa-lg\"></i></a>";
        //$links .= "<a  title=\"Stream Sources\" href=\"form/streams/formChannel.php?stream_id={$row['id']}&sources\"> <i style=\"margin:5px;\" class=\" fa fa-database fa-lg\"></i></a>";
    } else {
        $links .= "<a  title=\"Edit Stream\" href=\"form/streams/formStream.php?stream_id={$row['id']}\"> <i style=\"margin:5px;\" class=\" fa fa-edit fa-lg\"></i></a>";
        $links .= "<a  title=\"Stream Sources\" href=\"form/streams/formStream.php?stream_id={$row['id']}&sources\"> <i style=\"margin:5px;\" class=\" fa fa-database fa-lg\"></i></a>";
    }

    $links .= "<a href=\"#!\" title=\"Stop Stream\" onClick=\"stopStream( {$row['id']} );\"> <i style=\"margin:5px;\" class=\" fa fa-stop fa-lg\"></i> </a>";
    $links .= "<a href=\"#!\" title=\"Start/Restart Stream\" onClick=\"restartStream({$row['id']} );\"> <i style=\"margin:5px;\" class=\" fa fa-play fa-lg\"></i>  </a>";
    $links .= "<a href='#!'  title='Watch Stream' onClick=\" window.open('".HOST."/admin/IPTV/tb_player.php?id={$row['id']}','Player','width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"> <i style='margin:5px;' class='fa fa-desktop fa-lg'></i> </a>";
    //$links .= "<a href='#!' title='Refresh Stream' onClick='reloadTable()'> <i style='margin:5x;'  class='fa fa-refresh fa-lg'></i> </a>";
    $links .= "<a href='#!' title='View Streams Logs' onClick=\" window.open('".HOST."/admin/IPTV/stream_logs.php?stream_id={$row['id']}' ,'Stream Log','width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"> <i style='margin:5px;'  class=' fa fa-book fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Print account id or a message to all clients on this channel.' onClick=\"MODAL('modals/streams/mFingerPrint.php?channel_id={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-barcode fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Delete Stream and Sources' onClick=\"removeStream( {$row['id']} );\"> <i style='margin:5px;' class=' fa fa-remove fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Manage Load Balances' onClick=\"MODAL('modals/mStreamLoadBalance.php?ids={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-server fa-lg'></i> </a>";
    //$links .= "<a href='#!' title='Manage Load Balances' onClick=\"MODAL('modals/streams/mLoadBalance.php?channel_id={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-server fa-lg'></i> </a>";
    //$links .= "<a href='#!' title='Manage Load Balances' onClick=\"MODAL('modals/mChannelLoadBalance.php?channel_id={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-server fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Catchup/Recordings' onClick=\"MODAL('modals/streams/mCatchup.php?stream_id={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-record-vinyl fa-lg'></i> </a>";

    if($row['type'] == 1 )
        $links .= "<a href='#!' title='Move Channel' onClick=\"MODAL('modals/channels/mMoveChannel.php?stream_id={$row['id']}');\"> <i style='margin:5px;' class='fa fa-exchange fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Connections' 
        onclick=\" window.open('".HOST."/admin/IPTV/tb_stream_clients.php?stream_id={$row['id']}',
'Stream Clients','width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"   >
         <i style='margin:5px;' class='fa fa-plug fa-lg'></i> </a>";


//    $links .= "<a target='_blank' href='/admin/IPTV/qplaylist.php?stream_id={$row['id']}' title='Play in player' >
//            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Play in player' onClick='PlayInPlayer( \"stream\", {$row['id']} );' >
            <i style='margin:5px;' class='fa fa-tv fa-lg'></i> </a>";


    $links .= "<a target='' href='/admin/IPTV/stream_stats.php?filter_stream_id={$row['id']}' title='Streams Stats' >
            <i style='margin:5px;' class='fa fa-chart-pie fa-lg'></i> </a>";



    if(!isset($row['clients'])) $row['clients'] = 0;
    if($row['reconnected'] < 1 ) $row['reconnected'] = 0;

    $D = " ";
    if( $row['enable'] == 0 ) $D = " (Disabled) ";

    $B = " ";
    if( $row['is_backup'] == 1 ) $B = "<b>[BACKUP SOURCE]</b>";

    $row['stream_start_time'] = time() -  $row['stream_start_time']  ;
    if( !$row['total_down_seconds'] || $row['stream_start_time'] <  $row['total_down_seconds']) $total_uptime =0;
    else $total_uptime =  round ((( $row['stream_start_time']  - $row['total_down_seconds']) / $row['stream_start_time']) * 100) ;


    $status = " ";$stime = 0; $s_info = ''; $bitrate = '';

    if($row['streaming_id']) {
        if($row['streaming_status'] == 1 && $row['stream_action'] == 0 ) {

//            $dbtime = $row['connected_datetime'];
//            $now = date("d/m/Y h:i:s");
//            $difference = $dbtime-$row['now'];
//            $row['streaming_started'] = "D " . date("d", $difference) . " "  . date("h", $difference) . ":" . date("i", $difference) . ":" . date("s", $difference);


            //WE WILL SHOW STREAMING SOURCE ONLY IF IT HAS PERMISSIONS.
            if($Can_Edit)
                $status = "<p class='td-green'>{$row['streaming_url']}$D $B</p>";

            else
                $status = "<p class='td-green'> CONNECTED $D $B</p>";

            //$stime = "<b title='Up Time' class='td-green'> {$row['streaming_started']} <p title='Total up time'> $total_uptime</p> </b>";
            $stime = "<b title='Up Time' class='td-green'> {$row['streaming_started']}</b>";
            $s_info = unserialize($row['info']);
            $s_info = "{$row['video_codec']}/{$row['audio_codec']} {$s_info['resolution']} fps:{$s_info['frames']} ";
            $bitrate = round($row['bitrate']/1000)."k";

            if($row['type'] == \ACES2\IPTV\Stream::CHANNEL_TYPE_247  ) { 
                $rcf = $DB->query("SELECT c.status,movie_id,episode_id FROM iptv_channel_files c 
                                        RIGHT JOIN iptv_video_files ivf on c.file_id = ivf.id 
                                        WHERE c.channel_id = {$row['id']} AND c.status = 1 ORDER BY c.id ASC LIMIT 1 ");

                $status = "<p style='color:green'>STREAMING</p>";
                if ($row_bfile = $rcf->fetch_assoc()) {
                    if ($row_bfile['episode_id']) {
                        $episode = new \ACES\IPTV\Episode($row_bfile['episode_id']);
                        $vod = new \ACES\IPTV\VOD($episode->series_id);
                        $status .= "<b style=\"color:orange\">" . strtoupper("Building  $vod->name S{$episode->season_number} E{$episode->episode_number} {$episode->title}") . "</b>";
                    } else {
                        $vod = new \ACES\IPTV\VOD($row_bfile['movie_id']);
                        $status .= "<b style=\"color:orange\">" . strtoupper("Building {$vod->name}") . "</b>";
                    }
                }

                $r_files_fail = $DB->query("SELECT id FROM iptv_channel_files WHERE status = -1 and channel_id = '{$row['id']}' ");
                if ($r_files_fail->num_rows > 0) $status .= "<br/><b style=\"color:red\">ONE OR MORE FILES FAIL TO BUILD</b>";

            }

        } else if( $row['streaming_status'] == 2 ) {

            $status = "<p style=\"color:orange\">Ondemand</p>";
            $stime = "<b title='Time' style='color:orange'> {$row['down_time']}</b> ";

        } else if( $row['streaming_status'] == 3 ) {

            $status = "<p style=\"color:red\">Shutting Down $D</p>";
            $stime = '';

        } else if($row['streaming_status'] == \ACES2\IPTV\Stream::STATUS_MOVING ) {

            $stime = '';


            $r_file_moving = $DB->query("SELECT c.id,f.movie_id,f.episode_id FROM iptv_channel_files c RIGHT JOIN iptv_video_files f on c.file_id = f.id  WHERE c.status = 3 and c.channel_id = '{$row['id']}' ");
            if ($moving_file = $r_file_moving->fetch_assoc()) {
                if ($moving_file['episode_id']) {
                    $episode = new \ACES\IPTV\Episode($moving_file['episode_id']);
                    $vod = new \ACES\IPTV\VOD($episode->series_id);
                    $status .= "<b style=\"color:orange\">" . strtoupper("Moving  $vod->name S{$episode->season_number} E{$episode->episode_number} {$episode->title}") . "</b>";
                } else {
                    $vod = new \ACES\IPTV\VOD($moving_file['movie_id']);
                    $status .= "<b style=\"color:orange\">" . strtoupper("Moving {$vod->name}") . "</b>";
                }
            }



        } else {
            if($row['type'] == 1 ) $status = "<p style=\"color:orange\">BUILDING CHANNEL</p>";
            else $status = "<p style=\"color:orange\">Connecting $D</p>";
            //$stime = "<b title='Down Time' style='color:red'> {$row['down_time']} <p title='Total down time'> $total_uptime</p> ";
            $stime = "<b title='Down Time' style='color:red'> {$row['down_time']}  ";
        }

    } else {

        if($row['stream'] == 0 ) $status = "<p style='color:orange'> Redirecting $D</p> ";
        else $status = "<p style=\"color:red\">Stopped $D </p>";
        $stime = 0;

    }

    $logo = '';
    if($limit < 99 ) {
        if (filter_var($row['logo'], FILTER_VALIDATE_URL))
            $logo = "<br/><br/><img width=80 src='{$row['logo']}'/>";
        else if ($row['logo'])
            $logo = "<br/><br/><img width=80 src='http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}'/>";
    }


    if($row['stream_profile_unique'] > 0 )
        $row['stream_profile_name'] = 'CUSTOM';



    $progress = '';$buff_progress = '';
    if($row['streaming_status'] == 1 ) {


        //DETAILS BARS
        $conn_color = ''; $conn_percent = 0; $conn_title = '';
        switch((int)$row['streaming_speed']) {
            case Streaming::SPEED_EXCELENT:
                $conn_color = 'bg-success';
                $conn_percent = 100;
                $conn_title = 'Excellent';
                break;
            case Streaming::SPEED_FAST:
                $conn_color = 'bg-success';
                $conn_percent = 80;
                $conn_title = 'Good';
                break;
            case Streaming::SPEED_SLOW:
                $conn_color = 'bg-warning';
                $conn_percent = 65;
                $conn_title = 'Weak';
                break;

            case Streaming::SPEED_VERY_SLOW:
                $conn_color = 'bg-danger';
                $conn_percent = 30;
                $conn_title = 'Bad';
                break;

            default:
                $conn_color = 'bg-danger';
                $conn_percent = 10;
                $conn_title = 'Very Bad';
                break;
        }

        $progress .= "<br /><div title='Connection $conn_title' style='width:100%; border-radius:5px;' class='progress progress-sm mt-1'>
              <div class='progress-bar $conn_color' role='progressbar' aria-valuenow='$conn_percent' aria-valuemin='0'
                   aria-valuemax='100' style='width: $conn_percent%'>
              </div>
            </div>";

        if($detail_connection ) {
            $progress .= "<p class='m-0 mt-3'>Cache {$row['streaming_cache']}s<br/>Buffer {$row['streaming_buffer']}</p>";
        }

    }


    $json['data'][]=array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'].$logo,
        $status."</br>".$html_epg,
        $progress,
        $stime,
        "<a onclick=\" window.open('".HOST."/admin/IPTV/tb_stream_clients.php?stream_id={$row['id']}',
'Stream Clients','width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\">{$row['clients']}</a>",
        $row['reconnected'],
        $s_info,
        $bitrate,
        $row['stream_server_name'].$lbs,
        $row['stream_profile_name'],
        $row['category_name'],
        $links
    );

}


//$json['recordsFiltered'] = mysqli_num_rows($r);

echo json_encode($json);