<?php

use \ACES2\IPTV\AdminPermissions;

$db = new \ACES2\DB;
if(!$AdminID=adminIsLogged(false)){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}
$ADMIN = new \ACES2\Admin($AdminID);

$values = array();

$search = $db->escString($_REQUEST['search']);
if(strlen($search) < 3 ) {
    echo json_encode($values);
    exit;
}



if( $ADMIN->hasPermission(AdminPermissions::IPTV_FULL_STREAMS) ||
    $ADMIN->hasPermission(AdminPermissions::IPTV_VIEW_STREAMS)){

    $pages['/admin/IPTV/streams.php'] = 'streams';

    $r = $db->query("SELECT id,name,type FROM iptv_channels WHERE name LIKE '%$search%' LIMIT 10");
    while ($row = $r->fetch_assoc()) {

        $form = $row['type'] == 1 ? 'formChannel.php' : 'formStream.php';
        $name = $row['type'] == 1 ? 'Channel' : 'Stream';

        if($ADMIN->hasPermission(AdminPermissions::IPTV_VIEW_STREAMS))
        $values[] = array(
            'link' => "/admin/IPTV/form/streams/$form?stream_id=" . $row['id'],
            'title' => "Edit $name",
            'name' => $row['name'],
        );

        if($ADMIN->hasPermission(AdminPermissions::IPTV_FULL_STREAMS))
        $values[] = array(
            'link' => "/admin/IPTV/streams.php?filter_stream_id=" . $row['id'],
            'title' => "$name",
            'name' => $row['name'],
        );


    }
}

if($ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {

    $pages['/admin/IPTV/accounts.php'] = 'accounts';

    $r = $db->query("SELECT id,name,username FROM iptv_devices WHERE name like '%$search%' OR username like '%$search%' LIMIT 5");
    while ($row = $r->fetch_assoc()) {

        $values[] = array(
            'link' => "/admin/IPTV/form/formAccount.php?id=" . $row['id'],
            'title' => "Edit Account",
            'name' => $row['name'],
        );

        $values[] = array(
            'link' => "/admin/IPTV/accounts.php?filter_account_id=" . $row['id'],
            'title' => "Account $name",
            'name' => $row['name'],
        );

    }
}


if($ADMIN->hasPermission(AdminPermissions::IPTV_RESELLERS)) {

    $pages['/admin/IPTV/resellers.php'] = 'resellers';

    $r = $db->query("SELECT id,name from users WHERE name like '%$search%' or username like '%$search%' LIMIT 10 ");
    while ($row = $r->fetch_assoc()) {

        $values[] = array(
            'link' => "/admin/IPTV/resellers.php?filter_user_id=" . $row['id'],
            'title' => "Reseller",
            'name' => $row['name'],
        );

        $values[] = array(
            'link' => "/admin/IPTV/form/formReseller.php?id=" . $row['id'],
            'title' => "Edit Reseller",
            'name' => $row['name'],
        );
    }
}

if($ADMIN->hasPermission(AdminPermissions::IPTV_VOD) ||
    $ADMIN->hasPermission(AdminPermissions::IPTV_VOD_FULL)) {

    $pages['/admin/IPTV/videos.php'] = 'videos';
    $pages['/admin/IPTV/videos.php?filter_type=movies'] = 'movies';
    $pages['/admin/IPTV/videos.php?filter_type=series'] = 'series';

    if($ADMIN->hasPermission(AdminPermissions::IPTV_VOD_FULL))
        $pages['/admin/IPTV/providers.php'] = 'providers';

    $r = $db->query("SELECT id,name,type from iptv_ondemand WHERE name like '%$search%' LIMIT 10 ");
    while ($row = $r->fetch_assoc()) {

        $name = $row['type'] == 'series' ? 'Series' : 'Movie';

        if($ADMIN->hasPermission(AdminPermissions::IPTV_VOD_FULL))
        $values[] = array(
            'link' => "/admin/IPTV/form/formVideo.php?id=" . $row['id'],
            'title' => "Edit ".$name,
            'name' => $row['name'],
        );

        if($ADMIN->hasPermission(AdminPermissions::IPTV_VOD))
        $values[] = array(
            'link' => "/admin/IPTV/videos.php?filter_video_id=" . $row['id'],
            'title' => $name,
            'name' => $row['name'],
        );
    }
}

if($ADMIN->hasPermission(AdminPermissions::IPTV_MANAGE_BOUQUETS)) {
    $pages['/admin/IPTV/bouquets.php'] = 'bouquets';
    $pages['/admin/IPTV/bouquets.php'] = 'packages';
}

if($ADMIN->hasPermission(AdminPermissions::IPTV_CATEGORIES)) {
    $pages['/admin/IPTV/categories.php'] = 'categories';
}

if($ADMIN->hasPermission()) {
    $pages['/admin/IPTV/servers.php'] = 'servers';
    $pages['/admin/IPTV/admin_group.php'] = 'admins groups';
    $pages['/admin/IPTV/admins.php'] = 'admins';

    $pages['/admin/IPTV/settings.php'] = 'settings streams';
    $pages['/admin/IPTV/settings.php?tabVods'] = 'settings videos';
    $pages['/admin/IPTV/settings.php?tabVods'] = 'settings accounts';
    $pages['/admin/IPTV/settings.php?tabBackups'] = 'backups';
    $pages['/admin/IPTV/settings.php?tabBackupLocations'] = 'backups location';
    $pages['/admin/IPTV/settings.php?tabMaintenance'] = 'maintenance';


}


$found = preg_grep('~' . strtolower($search) . '~', $pages);
foreach ($found as $page => $n) {
    $values[] = array(
        'link' => $page,
        'title' => 'Page ',
        'name' => $n,
    );
}



echo json_encode($values);