<?php
header('Content-Type: application/json');
header('Connection: close');

$Admin = new \ACES2\ADMIN();
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

} else if(!$Admin->hasPermission('iptv.streams') && !$Admin->hasPermission('iptv.streams_full')) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}

$StreamID = (int)@$_GET['stream_id'];
$DeviceID = (int)@$_GET['device_id'];
$ServerID = (int)@$_GET['server_id'];

$sql_query  = " 1 = 1" ;
if($StreamID)
    $sql_query = " a.chan_id = '$StreamID' ";
else if($DeviceID)
    $sql_query = " a.device_id = '$DeviceID' ";
else if($ServerID)
    $sql_query = " a.server_id = '$ServerID' ";

if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " a.ip_address LIKE '%$s%' AND $sql_query ";
    $search .= " OR a.user_agent LIKE '%$s%' AND $sql_query ";
    if(is_numeric($s)) {
        $search .= " a.device_id = '$s' AND $sql_query ";
    }

    //$search .= " OR d.name LIKE '%$s%' AND $sql_query ";


    $sql_query = $search;
}



$start = (int)$_GET['start'];
$limit = (int)$_GET['length'] < 10001 ? $_GET['length'] : 10  ; //AT THE MOMENT TABLE IS SHOWING ALL ROWS...


$order = " ";
if($_GET['order'][0]['dir'] != 'asc' && $_GET['order'][0]['dir'] != 'desc' ) $_GET['order'][0]['dir'] = 'asc';


$r=$DB->query("SELECT id FROM iptv_access a WHERE $sql_query AND limit_time > NOW() ");
$json['draw'] = $_GET['draw'];
$json['recordsTotal'] = $r->num_rows;



$r = $DB->query("SELECT a.id

    FROM iptv_access a
    LEFT JOIN iptv_channels c ON c.id = a.chan_id
    LEFT JOIN iptv_devices d ON d.id = a.device_id

    WHERE $sql_query AND limit_time > NOW()
    GROUP BY a.id

");

$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT a.*,d.name as account_name,d.id as account_id ,s.name as server_name, lower(ip.country_code) as ip_country, 
    ip.country as ip_country_name, ip.org as ip_org,  TIMEDIFF(NOW(),a.add_date) as uptime, c.id as stream_id, c.name as stream_name
            
        FROM iptv_access a
        LEFT JOIN iptv_channels c ON c.id = a.chan_id
        LEFT JOIN iptv_servers s ON s.id = a.server_id
        LEFT JOIN iptv_devices d ON d.id  = a.device_id
        LEFT JOIN iptv_ip_info ip ON a.ip_address = ip.ip_address 
        WHERE $sql_query AND limit_time > NOW() 
        GROUP BY a.id ORDER BY a.id DESC LIMIT $start,$limit  ");

if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc())  {


    if($row['stream_id']) {
        $r2=$DB->query("SELECT name FROM iptv_channels WHERE id = '{$row['chan_id']}'");
        $watching = "Stream : <b>". $r2->fetch_assoc()['name'] . "</b> #{$row['stream_id']}";
    } else {
        $r2=$DB->query("SELECT  o.type,
                CASE 
                 WHEN f.movie_id != 0 THEN o.name 
                 WHEN f.episode_id != 0 THEN CONCAT(o.name, ' S',s.number, 'E',e.number, ' ',e.title )
                END as playing

                FROM iptv_video_files f 

                LEFT JOIN iptv_series_season_episodes e ON e.id = f.episode_id
                LEFT JOIN iptv_series_seasons s ON s.id = e.season_id
                LEFT JOIN iptv_ondemand o on f.movie_id = o.id or s.series_id = o.id 

                WHERE f.id = '{$row['vod_id']}' ");

        $row_vod = $r2->fetch_assoc();
        $watching = $row_vod['type'] == 'series'
            ? "Series: <b>{$row_vod['playing']}</b>"
            : "Movie: <b>{$row_vod['playing']}</b>";

    }


    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        "<b>{$row['account_name']}</b> #{$row['account_id']}",
        $watching,
        "<a href='#!' onclick='stopConnection({$row['id']})'>{$row['ip_address']}</a><br/>
            <i title='{$row['ip_country_name']}' class='flag-icon flag-icon-{$row['ip_country']}'></i>",
        $row['server_name'],
        $row['uptime'],
        $row['user_agent'],
        $row['stream_format'],
        $row['add_date'],
        "<a href='#!' onclick='stopConnection({$row['id']})'>Stop</a>"

    );
}



echo json_encode($json);