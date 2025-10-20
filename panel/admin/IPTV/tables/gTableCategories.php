<?php
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

} else if(!$ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_CATEGORIES)
    && !$ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_CATEGORIES_FULL)) {
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

$where = '';
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $where = " WHERE c.name LIKE '%$s%' ";

    if(is_numeric($_GET['search']['value']))
        $where .= " OR c.id LIKE '%{$s}%' ";

}

$r=$DB->query("SELECT id FROM iptv_stream_categories ");
$json['draw'] = $_GET['draw'];
$json['recordsTotal'] = mysqli_num_rows($r);

$r=$DB->query("SELECT id FROM iptv_stream_categories c $where GROUP BY c.id ");
$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT c.id, c.name,c.adults FROM iptv_stream_categories c $where LIMIT $start, $limit ");
if($r->num_rows < 1)  $json['data'] = array();
else while($row=$r->fetch_assoc()) {

    $r_streams=$DB->query("SELECT id FROM iptv_channels WHERE category_id = '{$row['id']}' ");

    $r_movies = $DB->query("SELECT i.i as total FROM iptv_in_category i
    RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id 
    WHERE o.type = 'movies'AND i.category_id = '{$row['id']}' GROUP BY i.i ");

    $r_series = $DB->query("SELECT i.i as total FROM iptv_in_category i
    RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id 
    WHERE o.type = 'movies'AND i.category_id = '{$row['id']}' GROUP BY i.i ");

    $links = "<a href='#!' title='Edit Category' onClick=\"MODAL( 'modals/category/mCategory.php?edit={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-edit fa-lg'></i> </a>";
    $links .= "<a href='#!' title='Remove Category' onClick=\"MODAL( 'modals/category/mRemoveCategory.php?id={$row['id']}' );\"> 
            <i style='margin:5px;' class='fa fa-trash fa-lg'></i> </a>";
    
    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name'],
        $r_streams->num_rows,
        $r_movies->num_rows,
        $r_series->num_rows,
        $row['adults'] == 1 ? "Yes" : "No",
        $links
    );
}

echo json_encode($json);