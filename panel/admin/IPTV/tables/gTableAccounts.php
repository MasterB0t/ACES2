<?php

header('Content-Type: application/json');
header('Connection: close');

use \ACES2\IPTV\Account;

$ADMIN = new \ACES2\ADMIN();
$DB = new \ACES2\DB();

if (!adminIsLogged(false)) {
    echo json_encode(array(
        'not_logged' => 1,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;

} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT) ) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}

$FULL_ADMIN = $ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_ACCOUNTS) || $ADMIN->hasPermission();

$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";

if(isset($_GET['filter_account_id']))
    $filters[] = " d.id = ". (int)$_GET['filter_account_id'];

if(isset($_GET['filter_owner']))
    $filters[] = " d.user_id = ". (int)$_GET['filter_owner'];

if(isset($_GET['filter_created_user']))
    $filters[] = " cu.id = ". (int)$_GET['filter_created_user'];

if(isset($_GET['filter_created_admin']))
    $filters[] = " ca.id = ". (int)$_GET['filter_created_admin'];

if(isset($_GET['filter_bouquet_package'])) {
    if($_GET['filter_bouquet_package'] == 'no_bouquet_package')
        $filters[] = " d.package_id = 0 ";
    else
        $filters[] = " b.id = ". (int)$_GET['filter_bouquet_package'];
}


if(isset($_GET['filter_status'])) {
    $filters[] = match($_GET['filter_status']) {
        'active' => "d.status = " . Account::STATUS_ACTIVE . " AND  TIMESTAMPDIFF(DAY, NOW(), d.subcription) > 0 ",
        'disabled' => "d.status = ".Account::STATUS_DISABLED,
        'blocked' => "d.status = ".Account::STATUS_BLOCKED,
        'expired' => " TIMESTAMPDIFF(DAY, NOW(), d.subcription) < 1 ",
        'expire_in_week' => " TIMESTAMPDIFF(DAY, NOW(), d.subcription) < 7 AND TIMESTAMPDIFF(DAY, NOW(), d.subcription) > 0 ",
        'expire_in_month' => " TIMESTAMPDIFF(DAY, NOW(), d.subcription) < 30 AND TIMESTAMPDIFF(DAY, NOW(), d.subcription) > 0",
        default => ''
    };
}

if(@$_GET['filter_device'] == 'mag')
    $filters[] = " d.mag != '' ";
else if(@$_GET['filter_device'] == 'android')
    $filters[] = " d.only_mag = 0 ";

$filter_sql = implode(" AND ", $filters);


$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " d.name LIKE '%$s%' AND $filter_sql ";
    $search .= " OR u.username LIKE '%$s%' AND $filter_sql ";
    $search .= " OR d.username LIKE '%$s%' AND $filter_sql ";
    $search .= " OR d.token LIKE '%$s%' AND $filter_sql ";
    $search .= " OR d.mag LIKE '%$s%' AND $filter_sql ";
    $search .= " OR d.ip_address LIKE '%$s%' AND $filter_sql ";
    $search .= " OR cu.username LIKE '%$s%' AND $filter_sql ";
    $search .= " OR ca.username LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR d.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY d.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY d.name $order_dir ";
else if($_GET['order'][0]['column'] == 2 ) $Order = " ORDER BY u.username $order_dir ";
else if($_GET['order'][0]['column'] == 3 ) $Order = " ORDER BY COUNT(a.id) $order_dir ";
else if($_GET['order'][0]['column'] == 4 ) $Order = " ORDER BY d.username $order_dir ";
else if($_GET['order'][0]['column'] == 5 ) $Order = " ORDER BY d.token $order_dir ";
else if($_GET['order'][0]['column'] == 7 ) $Order = " ORDER BY expire_in $order_dir ";
else if($_GET['order'][0]['column'] == 8 ) $Order = " ORDER BY d.ip_address $order_dir ";
else if($_GET['order'][0]['column'] == 9 ) $Order = " ORDER BY last_activity_minute_ago $order_dir ";
else if($_GET['order'][0]['column'] == 10 ) $Order = " ORDER BY d.add_date $order_dir ";
else if($_GET['order'][0]['column'] == 11 ) $Order = " ORDER BY cu.username $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT d.id FROM iptv_devices d
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT d.id,cu.username as created_username, ca.username as created_admin FROM iptv_devices d
 LEFT JOIN users u ON d.user_id = u.id
 LEFT JOIN iptv_bouquet_packages b ON b.id = u.id 
 LEFT JOIN iptv_access a ON a.device_id = d.id AND a.limit_time > NOW()
 LEFT JOIN users cu ON cu.id = d.add_by_user 
 LEFT JOIN admins ca ON ca.id = d.add_by_admin
 $Where GROUP BY d.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT d.* ,d.username as acc_username, cu.username as created_username, ca.username as created_admin, count(a.id) as connections, 
       d.subcription as expire_date,
       u.email, u.username, TIMESTAMPDIFF(MINUTE, d.last_activity, now()) as last_activity_minute_ago, 
       c.name as channel_name, a.vod_id, b.name as package_name, a.playing, TIMESTAMPDIFF(SECOND, NOW(), d.subcription) as expire_in 

    FROM iptv_devices d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN iptv_bouquet_packages b ON b.id = d.package_id 
    LEFT JOIN iptv_access a ON a.device_id = d.id AND a.limit_time > NOW()
    LEFT JOIN iptv_channels c ON c.id = a.chan_id
    LEFT JOIN users cu ON cu.id = d.add_by_user 
    LEFT JOIN admins ca ON ca.id = d.add_by_admin
$Where GROUP BY d.id $Order LIMIT $start,$limit ");




if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formAccount.php?id={$row['id']}' title='Edit Account' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";


    if(!empty($row['mag'])) {

        $links .= "<a href='#!' title='Mag Event' onClick=\"MODAL('modals/mAccountMagEvent.php?id={$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-envelope fa-lg'></i> </a>";
    }


    $links .= "<a href='#!' title='Print account id or a message to all clients on this channel.' 
onClick=\"MODAL('modals/streams/mFingerPrint.php?account_id={$row['id']}');\"> <i style='margin:5px;' class=' fa fa-barcode fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Account Info.' 
onClick=\"MODAL('modals/mAccountInfo.php?account_id={$row['id']}');\"> <i style='margin:5px;' class='fa fa-info-circle fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Download Playlist' 
onClick=\"MODAL('modals/mAccountPlaylist.php?id={$row['id']}');\"> <i style='margin:5px;' class='fa fa-download fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Reset MAG/Stb Device' 
onClick=\"resetMag('{$row['id']}');\"> <i style='margin:5px;' class='fa fa-circle-minus fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Connect With AcesPlayer App' 
onClick=\"MODAL('modals/mAccountAcesPlayer.php?account_id={$row['id']}');\"> <i style='margin:5px;' class='fa fa-mobile-screen-button fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Connections' 
        onclick=\" window.open('".HOST."/admin/IPTV/tb_stream_clients.php?device_id={$row['id']}','Account Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"  > <i style='margin:5px;' class='fa fa-plug fa-lg'></i> </a>";

    $links .= "<a href='/admin/IPTV/account_logs.php?filter_account_id={$row['id']}' title='Account Logs' > 
            <i style='margin:5px;' class='fa fa-book fa-lg'></i> </a>";

    $links .= "<a href='#!' title='Remove Account' onClick=\"removeAccount('{$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

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
        onclick=\" window.open('".HOST."/admin/IPTV/tb_stream_clients.php?device_id={$row['id']}','Device Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\">View All</a> ";
            break;
        }
    }


    //FIX GLITCH OF SHOWING MORE CONNECTION THAN ALLOWED.
    $connections = $active_conns > $row['limit_connections'] ? $row['limit_connections'] : $active_conns;
    $link_connections = "<a onclick=\" window.open('".HOST."/admin/IPTV/tb_stream_clients.php?device_id={$row['id']}','Device Connections',
    'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"> 
    {$connections}/{$row['limit_connections']}</a>";

    $trial = $row['demo'] ? "Trial" : '';

    $status  = "";
    if( $row['status'] == Account::STATUS_DISABLED )  $status = " <span class='font-weight-bold badge badge-warning'> Disabled </span> ";
    else if( $row['status'] == Account::STATUS_BLOCKED )  $status =  " <span class='badge badge-danger'> Blocked </span> ";
    else $status =  " <span class='badge badge-success'> Active </span> ";

    if($row['expire_in'] < 1 )
        $expire_on = "<span class=\"badge badge-danger\">$trial Expired </span>";
    else {

        if($row['expire_in'] < 604800 ) $expire_on = "<span class=\"badge badge-warning\"> $trial ";
        else $expire_on = "<span class=\"badge badge-success\"> $trial";

        if($row['expire_in'] < 60 ) $expire_on .= $row['expire_in']." Seconds </span>";
        else if($row['expire_in'] < 3600 ) $expire_on .= round($row['expire_in']/60)." Minutes </span>";
        else if( $row['expire_in'] <  86400 ) $expire_on .= round($row['expire_in']/3600)." Hours </span>";
        else $expire_on .= round($row['expire_in']/86400)." Days </span>";

    }




    if($active_conns )
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

    if(!$FULL_ADMIN ) $added_by = '';
    else {
        if($row['add_by_admin'] != 0 ) $added_by = "{$row['created_admin']}<br/>(ADMIN) ";
        else if($row['add_by_user'] != 0 ) $added_by = "{$row['created_username']}<br/>(RESELLER) ";
        else if($row['user_id'] != 0 ) $added_by = "{$row['username']}<br/>(RESELLER)";
        else $added_by = "ADMINS";
    }

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $status,
        $row['username'],
        $link_connections,
        $row['acc_username'],
        !empty($row['mag']) ? $row['token']."<br>{$row['mag']}" : $row['token'],
        $row['package_name'] ? $row['package_name'] : "[NO BOUQUET PACKAGE]",
        $row['expire_date'] . "<br>" .$expire_on,
        $row['ip_address'],
        $state,
        $row['add_date'],
        $added_by,
        $links
    );

}

echo json_encode($json);