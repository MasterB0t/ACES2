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

} else if (!$ADMIN->hasPermission() ) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}

$files = glob("/home/aces/backups/*" );



$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";


$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " b.name LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY b.create_time $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY b.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT b.create_time FROM backups b
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT b.create_time FROM backups b
 $Where GROUP BY b.create_time ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT b.* 

    FROM backups b

$Where GROUP BY b.create_time $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $name = urlencode($row['name']);

    $links = "<a target='_blank' href='/admin/download_backup.php?filename=$name' title='Downoad Backup' > 
            <i style='margin:5px;' class='fa fa-download fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Backup' onClick=\"removeBackup( '{$row['name']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $json['data'][] = array(
        'DT_RowId' => $row['create_time'],
        date("d M, Y",$row['create_time']),
        $row['name'],
        $row['filesize_kb'] . " KB",
        $links
    );

}

echo json_encode($json);