<?php
header('Content-Type: application/json');
header('Connection: close');

use \ACES2\IPTV\Account;


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

$USER = new \ACES2\IPTV\USER($UserID);
$DB = new \ACES2\DB();

$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$sub_resellers = $USER->getResellers();
$sub_resellers[] = $USER->id;
$filters[] =  " a.user_id in (" .  implode(",", $sub_resellers)  . ") ";

if(!empty($_GET['filter_owner']))
    $filters[] = " a.user_id = '" . (int)$_GET['filter_owner'] . "' ";

if(!empty($_GET['filter_device']))
    $filters[] = match($_GET['filter_device']) {
        'mag' => " a.allow_mag = 1 AND a.mag != '' ",
        'android' => 'a.no_allow_xc_apps = 0'
    };

if(isset($_GET['filter_status']))
    $filters[] = match($_GET['filter_status']) {
        'active' => " a.status = " . Account::STATUS_ACTIVE . " AND TIMESTAMPDIFF(DAY, NOW(), a.subcription) > 0 ",
        'disabled' =>  " a.status = ".Account::STATUS_DISABLED ,
        'blocked' =>  " a.status = ".Account::STATUS_BLOCKED ,
        'expired' => " TIMESTAMPDIFF(DAY, NOW(), a.subcription) < 1 ",
        'expire_in_week' => " TIMESTAMPDIFF(DAY, NOW(), a.subcription) < 7 AND TIMESTAMPDIFF(DAY, NOW(), a.subcription) > 0 ",
        'expire_in_month' => " TIMESTAMPDIFF(DAY, NOW(), a.subcription) < 30 AND TIMESTAMPDIFF(DAY, NOW(), a.subcription) > 0 "
    };


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " a.name LIKE '%$s%' AND $filter_sql ";
    $search .= " OR a.username LIKE '%$s%' AND $filter_sql ";
    $search .= " OR a.token LIKE '%$s%' AND $filter_sql ";
    $search .= " OR a.mag LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR a.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY a.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY a.name $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY u.name $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY a.limit_connections $order_dir ";
else if($_GET['order'][0]['column'] == 7 ) $Order = " ORDER BY a.subcription $order_dir ";



$json['draw'] = $_GET['draw'];


$r=$DB->query("SELECT a.id FROM iptv_devices a
WHERE  a.user_id in (" .  implode(",", $sub_resellers)  . ") ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT a.id FROM iptv_devices a

    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN iptv_bouquet_packages b ON b.id = u.id 
    LEFT JOIN iptv_access ac ON ac.device_id = a.id AND ac.limit_time > NOW()
    LEFT JOIN users cu ON cu.id = a.add_by_user 

 $Where GROUP BY a.id ");
$json['recordsFiltered'] = $r->num_rows;


$r=$DB->query("SELECT a.*, u.name as user_name ,a.username as acc_username, cu.username as created_username, cu.name as created_name, count(ac.id) as connections,
       u.email, u.name as user_name,  u.username, TIMESTAMPDIFF(MINUTE, a.last_activity, now()) as last_activity_minute_ago, 
       c.name as channel_name, ac.vod_id, b.name as package_name, ac.playing, TIMESTAMPDIFF(SECOND, NOW(), a.subcription) as expire_in

    FROM iptv_devices a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN iptv_bouquet_packages b ON b.id = a.package_id 
    LEFT JOIN iptv_access ac ON ac.device_id = a.id AND ac.limit_time > NOW()
    LEFT JOIN iptv_channels c ON c.id = ac.chan_id
    LEFT JOIN users cu ON cu.id = a.add_by_user

$Where GROUP BY a.id $Order LIMIT $start,$limit ");

if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formAccount.php?id={$row['id']}' title='Edit Account' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Update Package' onClick=\"MODAL( 'modals/mAccountPackage.php?account_id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-box-open fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Remove Account' onClick=\"massRemoveAccounts( '{$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    if(!empty($row['mag'])) {
        $links .= "<a href='#!' title='Mag Event' onClick=\"MODAL('modals/mAccountMagEvent.php?id={$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-envelope fa-lg'></i> </a>";

        $links .= "<a href='#!' title='Reset MAG/Stb Device' 
    onClick=\"resetMag('{$row['id']}');\"> <i style='margin:5px;' class='fa fa-circle-minus fa-lg'></i> </a>";

    }

    $links .= "<a href='#!' title='Download Playlist' 
onClick=\"MODAL('modals/mAccountPlaylist.php?id={$row['id']}');\"> <i style='margin:5px;' class='fa fa-download fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Connections' 
        onclick=\" window.open('".HOST."/user/IPTV/connections.php?device_id={$row['id']}','Account Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"  > <i style='margin:5px;' class='fa fa-plug fa-lg'></i> </a>";


    $r_conns = $DB->query("SELECT count(id) as connections FROM iptv_access 
                                WHERE device_id = {$row['id']} AND limit_time > NOW()");
    $row_conns = (int)$r_conns->fetch_assoc()['connections'];

    $connections = $row_conns > $row['limit_connections'] ? $row['limit_connections'] : $row_conns;
    $link_connections = "<a onclick=\" window.open('".HOST."/user/IPTV/connections.php?device_id={$row['id']}','Device Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"> 
    {$connections}/{$row['limit_connections']}</a>";



    $trial = $row['demo'] ? "Trial" : '';

    $status  = "";
    if( $row['status'] == Account::STATUS_DISABLED )  $status = " <span class='font-weight-bold badge badge-warning'> Disabled </span> ";
    else if( $row['status'] == Account::STATUS_BLOCKED )  $status =  " <span class='badge badge-danger'> Blocked </span> ";
    else $status =  " <span class='badge badge-success'> Active </span> ";

    if($row['expire_in'] < 1 )
        $expire_on = "<span class=\"badge badge-danger\">$trial Expired  </span>";
    else {

//        if($row['status'] == Account::STATUS_DISABLED ) $expire_on = "<span class=\"label label-warning\"> DISABLED  $trial ";
//        else if($row['status'] == Account::STATUS_BLOCKED )  $expire_on = "<span class=\"label label-danger\"> BLOCKED  $trial ";

        if($row['expire_in'] < 604800 ) $expire_on = "<span class=\"badge badge-success\"> $trial ";
        else $expire_on = "<span class=\"badge badge-success\"> $trial ";

        if($row['expire_in'] < 60 ) $expire_on .= $row['expire_in']." Seconds </span>";
        else if($row['expire_in'] < 3600 ) $expire_on .= round($row['expire_in']/60)." Minutes </span>";
        else if( $row['expire_in'] <  86400 ) $expire_on .= round($row['expire_in']/3600)." Hours </span>";
        else $expire_on .= round($row['expire_in']/86400)." Days </span>";

    }



    //PLAYING...
    $r_conns = $DB->query("SELECT chan_id,vod_id FROM iptv_access 
                                WHERE device_id = {$row['id']} AND limit_time > NOW() ");
    $active_conns = (int)$r_conns->num_rows;

    $playing='';$i=0;
    while($row_p = $r_conns->fetch_assoc()) {
        if($row_p['chan_id']) {
            $ra = $DB->query("SELECT name FROM iptv_channels WHERE id = '{$row_p['chan_id']}'");
            $playing .=  "<b style='color:green'>Stream: ".$ra->fetch_assoc()['name'] ."</b><br />";
        } else {
            $ra = $DB->query("SELECT  o.type,
                    CASE 
                     WHEN f.movie_id != 0 THEN o.name 
                     WHEN f.episode_id != 0 THEN CONCAT(o.name, ' S',s.number, 'E',e.number, ' ',e.title )
                    END as playing

                    FROM iptv_video_files f 

                    LEFT JOIN iptv_series_season_episodes e ON e.id = f.episode_id
                    LEFT JOIN iptv_series_seasons s ON s.id = e.season_id
                    LEFT JOIN iptv_ondemand o on f.movie_id = o.id or s.series_id = o.id 

                    WHERE f.id = '{$row_p['vod_id']}' ");

            $row_vod = $ra->fetch_assoc();
            if($row_vod['type'] == 'series')
                $playing .= "<b style='color:green'>Series: {$row_vod['playing']}</b><br/>";
            else $playing = "<b style='color:green'>Movie: {$row_vod['playing']}</b><br />";
        }


        $i++;
        if($i > 3 ) {
            $playing .= "<a href='#!' title='Connections' 
        onclick=\" window.open('".HOST."/user/IPTV/connections.php?device_id={$row['id']}','Device Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\">View All</a> ";
            break;
        }
    }


    if($row_conns )
        $state = "<b style='color:green'>$playing</b>";
    else if( $row['last_activity'] != NULL ) {
        if($row['last_activity_minute_ago'] < 30 ) $activity = "Few seconds ago";
        else if($row['last_activity_minute_ago'] < 61 ) $activity = "One minute ago";
        else if($row['last_activity_minute_ago'] < 120 ) $activity = "One hour ago";
        else if($row['last_activity_minute_ago'] < 1440 ) $activity = round($row['last_activity_minute_ago']/60)." Hours ago ";
        else if($row['last_activity_minute_ago'] < 43200 ) $activity = round($row['last_activity_minute_ago']/1440)." Days ago";
        else if($row['last_activity_minute_ago'] < 86400 ) $activity = "Last month";
        else $activity = $row['last_activity'];

        $state = "<p title='{$row['last_activity']}'>Last Activity<br><b>$activity</b></p>";
    } else
        $state = "<b>Never Used.</b>";



    if(@$row['add_by_admin'] != 0 ) $added_by = "{$row['created_admin']}<br/>(ADMIN) ";
    else if($row['add_by_user'] != 0 ) {
        $create_name = $row['created_username'] == $USER->id ? '(YOU)' : $row['created_name'];
        $added_by = "{$row['created_username']}<br/> $create_name ";
    } else $added_by = "(ADMIN1)";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $status,
        $row['username']."<br /> ". $row['user_name'],
        $link_connections,
        $row['acc_username'],
        !empty($row['mag']) ? $row['token']."<br>{$row['mag']}" : $row['token'],
        $row['package_name'] ? $row['package_name'] : "[NO BOUQUET PACKAGE]",
        $expire_on,
        $row['ip_address'],
        $state,
        $row['add_date'],
        $added_by,
        $links
    );

}

echo json_encode($json);