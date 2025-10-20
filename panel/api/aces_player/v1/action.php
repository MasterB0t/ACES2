<?php

//header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
//header('Connection: close');

ini_set('memory_limit','500M');
set_time_limit ( 0 );

include_once '/home/aces/panel/functions/logs.php';
include_once '/home/aces/stream/config.php';
include_once '/home/aces/panel/includes/init.php';

$AUTH = 0 ;
$TMDB_API_KEY='';

function set_error($msg='',$error_code=400) {

    GLOBAL $AUTH;
    http_response_code($error_code);
    echo json_encode( array('status'=>0, 'error_message' => $msg )  ); die;

}

function set_completed($data='') {

    $json = array('status' => 1 );
    if(!is_array($data)) $json['data'] = $data;

    echo json_encode($json);
}


function _LOG($msg) {
    if(is_array($msg)) $msg = print_r($msg,1);
    error_log($msg."\n", 3, "/home/aces/logs/aces_api.log");
}

$DB = new \ACES\DB;
if($DB->connect_errno > 0){ die; }


if(empty($_GET['token']) || empty($_GET['username']) || empty($_GET['password']) )
{ _LOG("EMPTY"); echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }

//$ARMOR = new \ACES\Armor();
//if( $ARMOR->isBlock('iptv-player_api') )
//{ _LOG("ARMOR"); echo json_encode(  array('status'=> 0, 'auth' => 0 )  ); die; }


$USERNAME = $DB->escape_string($_GET['username']);
$PASSWORD = $DB->escape_string($_GET['password']);
$TOKEN = $DB->escape_string($_GET['token']);


$r=$DB->query("SELECT a.id,a.limit_connections,a.subcription,a.status,a.bouquets,a.adults,a.only_mag,d.id as device_id, d.last_profile_id as profile_id, TIMESTAMPDIFF(SECOND, NOW(), a.subcription) as expire_in
    FROM  iptv_app_devices d
    RIGHT JOIN iptv_devices a ON a.id = d.account_id
    WHERE d.token = '$TOKEN' AND a.username = '$USERNAME' AND a.token = '$PASSWORD'
");


if(!$ACCOUNT=$r->fetch_assoc() ) {
    _LOG("NO DB");
    //$ARMOR->action('iptv-player_api');
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die;
}


if($ACCOUNT['only_mag'] != 0 ) { _LOG("ONLY MAG");
    echo json_encode(  array('status'=> 0, 'auth' => 0 )  );  die; }

if($ACCOUNT['status'] != 1 || $ACCOUNT['expire_in'] < 1 ) {

    if ($ACCOUNT['expire_in'] < 1) $status = 'expired';
    else if ($ACCOUNT['status'] != 1) $status = 'blocked';
    else $status = 'active';

    $account_info = array(
        'status' => $status,
        'expire_time' => (string)strtotime($ACCOUNT['subcription']),
        'max_connections' => $ACCOUNT['limit_connections'],
        'active_connections' => 0

    );

    echo json_encode($account_info);
    exit;

}

$ACCOUNT['bouquets'] = join("','", unserialize($ACCOUNT['bouquets']) );

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

$data = [];
$response = json_decode(base64_decode($_GET['data']),1);
switch(strtoupper($_GET['action'])) {

    case 'INFO':

        $r=$DB->query("SELECT COUNT(id) as connections FROM iptv_access WHERE limit_time > NOW() AND device_id = {$ACCOUNT['id']}  ");
        $active_conns = $r->fetch_assoc()['connections'];

        //IF THERE ARE NO PROFILE ID CREATE A DEFAULT ONE..
        if(!$ACCOUNT['profile_id']) {
            $r = $DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}' LIMIT 1 ");
            if (!$ACCOUNT['profile_id'] = $r->fetch_assoc()['id']) {
                $DB->query("INSERT INTO iptv_app_profiles (account_id,name) VALUES('{$ACCOUNT['id']}','Default')");
                $ACCOUNT['profile_id'] = $r->num_rows;
            }
            $DB->query("UPDATE iptv_app_devices SET last_profile_id = '{$ACCOUNT['profile_id']}' WHERE id = {$ACCOUNT['device_id']}");
        }


        if($ACCOUNT['expire_in'] < 1 ) $status = 'expired';
        else if($ACCOUNT['status'] != 1 ) $status = 'blocked';
        else $status = 'active';

        $account_info = array(
            'status' => $status,
            'expire_time' => (string)strtotime($ACCOUNT['subcription']),
            'max_connections' =>  $ACCOUNT['limit_connections'],
            'active_connections' => $active_conns,
            'profile_id' => $ACCOUNT['profile_id'],

        );

        echo json_encode(array("auth"=>1, "status" => 1, 'account_info'=> $account_info ));
        exit;

        break;



    case 'GET_PROFILES':

        $data = [];
        $r = $DB->query("SELECT id,name,avatar FROM iptv_app_profiles WHERE account_id = {$ACCOUNT['id']}");
        while($row=$r->fetch_assoc()) $data[] = $row;
        break;


    case 'UPDATE_PROFILE':
        $data = json_decode(base64_decode($_GET['data']),1);
        $profile_id = (int)$data['profile_id'];
        $profile_name = $DB->escape_string($data['name']);
        $avatar = $DB->escape_string($data['avatar']);

        if($profile_id) {

            $DB->query("UPDATE iptv_app_profiles SET name = '$profile_name', avatar = '$avatar' WHERE id = $profile_id AND account_id = '{$ACCOUNT['id']}' ");

        } else {
            //INSERT
            $r=$DB->query("SELECT id FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}'");
            if($r->num_rows >= $ACCOUNT['limit_connections'] )
                set_error("You cannot add more profiles.");

            $DB->query("INSERT INTO iptv_app_profiles (account_id,name,avatar) VALUES ('{$ACCOUNT['id']}','$profile_name','$avatar')");
        }
        $data = [];
        break;

    case 'REMOVE_PROFILE':

        #$response = json_decode(base64_decode($_GET['data']),1);
        $profile_id = (int)$response['profile_id'];
        if(!$profile_id)
            set_error("System Error");


        $DB->query("DELETE FROM iptv_app_profiles WHERE account_id = '{$ACCOUNT['id']}' AND id = '$profile_id'");

        break;

    case 'SET_PROFILE' :
        $response = json_decode(base64_decode($_GET['data']),1);
        $profile_id = (int)$response['profile_id'];
        if(!$profile_id)
            set_error("No Profile id.");

        $DB->query("UPDATE iptv_app_devices SET last_profile_id = '$profile_id' WHERE id = {$ACCOUNT['device_id']}");
        break;

    case 'SET_ONDEMAND_POSITION' :



        break;

    case 'GET_ONDEMAND_CATEGORIES':
        $categories = array();

        $SQL = '';$SQL .= ' AND cat.adults = 0 ';

        if(isset($response['type'])) {

            if($response['type'] == 'movies' ) $SQL .= "AND o.type = 'movies' ";
            else if($response['type'] == 'series' ) $SQL .= "AND o.type = 'series' ";

        }

        $r=$DB->query("SELECT cat.name as name,cat.id as id FROM iptv_ondemand o 
        RIGHT JOIN iptv_in_category i ON i.vod_id = o.id 
        RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
        RIGHT JOIN iptv_stream_categories cat on cat.id = i.category_id
        WHERE o.status = 1 $SQL AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}')
        GROUP BY cat.id ORDER BY cat.ordering
        ");

        while($row=mysqli_fetch_array($r)) { $categories[] = array('id' => $row['id'], 'name' => $row['name']  ) ; }

        echo json_encode($categories);
        exit;

    case 'GET_ONDEMAND_CONTENT':


        $sql = " AND p.bouquet_id IN ('{$ACCOUNT['bouquets']}') ";


        $limit = 10;
        if (is_numeric($response['limit']) && $response['limit'] < 101)
            $limit = $response['limit'];


        if (isset($response['type'])) {

            if ($response['type'] == 'movies') $sql .= "AND o.type = 'movies' ";
            else if ($response['type'] == 'series') $sql .= "AND o.type = 'series' ";

        }

        $order_by = "p.i DESC";
        if ($response['sort_by'] == 'recent') $order_by = " o.id DESC ";


        $page = (int)$response['page'];
        if ($page > 0) $page--; //BECAUSE SQL START FROM 0 NOT 1

        if ($ACCOUNT['adults'] == 0) $sql .= ' AND cat.adults = 0 ';


        $sql_cat = '';

        if (is_numeric($response['category_id'])) {

            $query = "SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_in_category i
                RIGHT JOIN iptv_ondemand o ON o.id = i.vod_id
                RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
                RIGHT JOIN iptv_stream_categories cat ON cat.id = i.category_id AND i.category_id = {$response['category_id']}
                WHERE o.status = 1  $sql 
                GROUP BY o.id ORDER BY p.i DESC";


        } else if ($_GET['category_id'] == 'favorites') {

            $query = "SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_app_favorites f
                RIGHT JOIN iptv_ondemand o ON o.id = f.vod_id
                RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
                RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
                WHERE o.status = 1 AND f.profile_id = '{$ACCOUNT['profile_id']}'  $sql
                GROUP BY o.id ORDER BY f.add_time  DESC";

        } else if ($_GET['category_id'] == 'watching') {

            $query = "SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_app_watching w
                RIGHT JOIN iptv_ondemand o ON o.id = w.vod_id
                RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
                RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
                WHERE o.status = 1 AND w.profile_id = '{$ACCOUNT['profile_id']}'  $sql
                GROUP BY o.id ORDER BY w.add_time DESC";

        } else {

            //RECENTs
            $query = "SELECT o.*,cat.name as category_name, cat.id as category_id FROM iptv_ondemand o 
                RIGHT JOIN iptv_ondemand_in_bouquet p ON p.video_id = o.id
                RIGHT JOIN iptv_stream_categories cat ON cat.id = o.category_id 
                WHERE o.status = 1  $sql
                GROUP BY o.id ORDER BY o.id DESC";


        }


        $rt = $DB->query($query);


        if ($rt->num_rows < 1) {

            $json = array(
                "total_items" => 0,
                "current_page" => 1,
                "total_pages" => 0,
                "items" => []
            );

            echo json_encode($json);
            exit;

        }

        $total = $rt->num_rows;
        if ($limit == 0) $limit = $total;

        $total_pages = $total / $limit;
        if ($total_pages < 1) {
            $sql_limit = "LIMIT 0,$limit";
            $total_pages = 1;
            $page = 0;
        } else {
            if (!is_int($total_pages)) $total_pages = (int)$total_pages + 1; //FIX DECIMAL
            if ($page > $total / $limit) $page = ($total / $limit) - 1;
            $sql_limit = "LIMIT " . ($limit * $page) . ",$limit";
        }

        $r = $DB->query($query . "  $sql_limit ");


        $items = [];
        while ($row = mysqli_fetch_assoc($r)) {

            if (strtotime($row['add_date']) < 1) $add_date = 0;
            else $add_date = strtotime($row['add_date']);

            $logo = '';
            if ($row['logo']) $logo = "http://{$_SERVER['HTTP_HOST']}/logos/{$row['logo']}";

            $items[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'logo' => $logo,
                'rating' => $row['rating'],
                'rating_5based' => round(($row['rating'] * .5), 1),
                'add_time' => "$add_date",
                'add_date' => $row['add_date'],
                'category_id' => $response['category_id'],
                'category_name' => $row['category_name'],
                'container_extension' => 'mp4',
            );

        }


        $json = array(
            "total_items" => $total,
            "current_page" => $page + 1,
            "total_pages" => $total_pages,
            "limit" => $limit,
            "items" => $items
        );


        echo json_encode($json);
        exit;


    default:
        set_error("Unknown Error");


}
echo json_encode(array("auth"=>1, "status" => 1, 'data' => $data ));
exit;