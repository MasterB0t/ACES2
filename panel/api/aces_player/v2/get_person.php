<?php

unset($_GET['action']);
require_once 'index.php';

$PersonID = (int)$_GET['person_id'];
$from_vod_id = (int)$_GET['from_vod_id'];

$TMDB = new \Tmdb();
$info = $TMDB->getPerson($PersonID);

$person = $info['person'];

if($person['gender'] == 1 )
    $gender = "Female";
else if($person['gender'] == 2 )
    $gender = "Male";
else if($person['gender'] == 3 )
    $gender = "Non-Binary";
else
    $gender = "Not Specified";

$bod = date("F j, Y",strtotime($person['birthday'])) ;
$born_on = $person['place_of_birth'] ? $bod . " - " . $person['place_of_birth'] : $bod;
$die_on = $person['deathday'] ? date("F j, Y",strtotime($person['deathday'])) : null;

$data['person'] = array(
    'name' => $person['name'],
    'gender' => $person['gender'],
    'biography' => $person['biography'],
    'born_on' => $born_on,
    'die_on' => $die_on,
    'character' => null,
    'profile_path' => $person['profile_path'] ,
);

$data['vods'] = [];
foreach ($info['vods']['cast'] as $movie) {

    $r=$DB->query("SELECT id,logo as cover FROM iptv_ondemand WHERE tmdb_id = '{$movie['id']}' AND id != $from_vod_id ") ;
    if($row=$r->fetch_assoc()) {
        $logo='';
        if(!filter_var($row['cover'], FILTER_VALIDATE_URL))  {
            $row['cover'] = $row['cover'] ? "$HOST/logos/{$row['cover']}" : null;
        }

        $data['vods'][] = $row;
    }

}



$data['status'] = true;
$data['error_message'] = "";
echo json_encode($data);