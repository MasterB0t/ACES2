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


$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";


if($_GET['filter_status'] === "active" ) {
    $filters[] = "b.status = 1" ;
} else if($_GET['filter_status'] === "disabled" )
    $filters[] = "b.status = 2";
else if($_GET['filter_status'] === "blocked" )
    $filters[] = "{b}.status = 3";

$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " b.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR b.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY b.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY b.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT b.id FROM iptv_bouquets b
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT b.id FROM iptv_bouquets b
 $Where GROUP BY b.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT b.* 

    FROM iptv_bouquets b

$Where GROUP BY b.id $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    $links = "<a href='form/formBouquet.php?id={$row['id']}' title='Edit' > 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove ' onClick=\"remove('{$row['id']}');\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $links
    );

}

echo json_encode($json);