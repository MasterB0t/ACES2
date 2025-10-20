<?php
header('Content-Type: application/json');
header('Connection: close');




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

$USER = new \ACES2\IPTV\Reseller2($UserID);

if (!$USER->allow_vod_list ) {
    echo json_encode(array(
        'not_logged' => 0,
        'draw' => 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
    exit;
}



$DB = new \ACES2\DB();


$start = 0;
if(is_numeric($_GET['start']) && $_GET['start'] > 0 ) $start = $_GET['start'];

$limit = 10;
if(is_numeric($_GET['length']) && $_GET['length'] > 0 && $_GET['length'] < 10001 ) $limit = $_GET['length'];

$filters[] = " 1 = 1 ";

if(isset($_GET['filter_type'])) {
    if($_GET['filter_type'] == 'movies') $filters[] = "v.type = 'movies'";
    else if($_GET['filter_type'] == 'series') $filters[] = "v.type = 'series'";
}

if(isset($_GET['filter_category'] )) {
    $cat_id = (int)$_GET['filter_category'];
    $filter_cat_join = "INNER JOIN iptv_in_category incat ON 
	incat.category_id = $cat_id AND v.id = incat.vod_id " ;
}



$filter_sql = implode(" AND ", $filters);

$search = "";
if(!empty($_GET['search']['value'])) {

    $s = $DB->escString($_GET['search']['value']);

    $search = " v.name LIKE '%$s%' AND $filter_sql ";

    if(is_numeric($_GET['search']['value']))
        $search .= " OR v.id LIKE '%$s%' AND $filter_sql ";

    $Where = " WHERE $search ";

} else $Where = "WHERE $filter_sql ";


$order_dir = $_GET['order'][0]['dir'] == 'asc' || $_GET['order'][0]['dir'] == 'desc'
    ? $_GET['order'][0]['dir'] : "asc ";

$Order = " ";
if($_GET['order'][0]['column'] == 0 ) $Order = " ORDER BY v.id $order_dir ";
else if($_GET['order'][0]['column'] == 1 ) $Order = " ORDER BY v.name $order_dir ";


$json['draw'] = $_GET['draw'];

$r=$DB->query("SELECT v.id FROM iptv_ondemand v
 ");
$json['recordsTotal'] = $r->num_rows;

$r=$DB->query("SELECT v.id "
    . " FROM iptv_ondemand v  "
    . " LEFT JOIN iptv_stream_categories cat ON cat.id = v.category_id  "
    . @$filter_cat_join
    . " $Where  GROUP BY v.id  ");

$json['recordsFiltered'] = $r->num_rows;

$r=$DB->query("SELECT v.*,cat.name as category_name "
    . " FROM iptv_ondemand v  "
    . " LEFT JOIN iptv_stream_categories cat ON cat.id = v.category_id   "
    . @$filter_cat_join
    . "$Where  GROUP BY v.id  $Order LIMIT $start,$limit ");


if($r->num_rows < 1)  $json['data'] = array();
else while($row = $r->fetch_assoc()) {

    if($row['type'] == 'series')
        $links = "<a href='episodes.php?filter_series={$row['id']}' title='Episodes' > 
            <i style='margin:5px;' class='fa fa-list fa-lg'></i> </a>";

    $type = $row['type'] == 'seireis' ? 'Series' : 'Movie';

    $quality = '';$c='';
    if(!empty($row2['resolution']) ) {
        $res = explode('x',$row2['resolution']);
        $c = "({$row2['video_codec']}/{$row2['audio_codec']})";
        if($res[0] >= 1920 && $res[1] >= 1080 ) $quality = "FHD ({$row2['resolution']})";
        else if($res[0] >= 1280 && $res[1] >= 720 ) $quality = "HD ({$row2['resolution']})";
        else $quality = "SD ({$row2['resolution']})";

        $quality .= " <br/>$c";
    }

    $logo = '';
    if($row['logo']) {
        $logo = filter_var($row['logo'], FILTER_VALIDATE_URL)
            ? $logo = "<img class='pt-1' width=80 src='{$row['logo']}'/>"
            : "<img width=80 src='".HOST."/logos/{$row['logo']}'/>";
    }

    $json['data'][] = array(
        'DT_RowId' => $row['id'],
        $row['id'],
        $row['name']."<br>".$logo,
        $type,
        $row['category_name'],
        $quality,
        $row['release_date'],
        $row['add_date'],
        $links

    );

}

echo json_encode($json);