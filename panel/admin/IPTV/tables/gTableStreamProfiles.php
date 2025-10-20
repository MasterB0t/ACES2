<?php

header('Content-Type: application/json');
header('Connection: close');


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

} else if (!$ADMIN->hasPermission('') ) {
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

$filter = " p.only_chan_id = 0 ";

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = "  p.name LIKE '%$s%' AND $filter ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR p.id LIKE '%$s%' AND $filter ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY p.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY p.name $order_dir ";

$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT id FROM iptv_stream_options p WHERE only_chan_id = 0 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT id FROM iptv_stream_options p $Where ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT p.id,p.name,p.video_codec,p.audio_codec FROM iptv_stream_options p
    $Where $Order LIMIT $start,$limit");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {


    $links = "<a href='form/formStreamProfile.php?id={$row['id']}' title='Edit Profile' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Profile' onClick=\"MODAL( 'modals/streams/mRemoveStreamProfile.php?id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        "{$row['video_codec']} / {$row['audio_codec']}",
        $links
    );

}

echo json_encode($json);