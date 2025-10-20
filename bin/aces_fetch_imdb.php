<?php

set_time_limit(0);

require_once "/home/aces/stream/config.php";
require "/home/aces/panel/class/TMDB.php";

function arrayToSql($array) {

    if(!is_array($array) || count($array) < 1 ) return '';

    foreach ($array as $field => $data)
        $update_sqls[] = "$field = '" . $data . "' ";
    return implode(', ', $update_sqls);

}

function logError($message) {
    error_log($message);
}

function print_help() {
    echo "NO HELP";
    exit;
}


$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }

$TMDB = new TMDB;
$sql = '';
$MODE = $argv[1];
$FROM_ID = (int)$argv[2];

switch($MODE) {

    case '--missing':
        $MODE = 'missing';
        $sql = ' WHERE tmdb_id == 0 ';
        break;

    case '--force':
        $MODE = 'force';
        break;

    case '--update':
        $MODE = 'update';
        $sql = ' WHERE tmdb_id != 0 ';
        break;

    case '--update-cover';
        $MODE = 'update_cover';
        $sql = ' WHERE tmdb_id != 0 ';
        break;

    default :
        echo "$MODE\n";
        print_help();

}


if($FROM_ID) $sql .= " AND id >= $FROM_ID";

$r_ondemand = $DB->query("SELECT id,name,release_date,tmdb_id,type FROM iptv_ondemand $sql ");
while($row = $r_ondemand->fetch_assoc()) {

    $id_tmdb = $row['tmdb_id'];

//    $year = '';$results = false;
//    if($row['release_date'] != '0000-00-00') {
//
//        $year = explode('-',$row['release_date'])[0];
//        $results = $TMDB->search_imdb($row['name'],$row['type'],$year);
//
//    } else {
//
//        preg_match_all('!\d+!', $row['name'], $matches);
//        if(count($matches[0])>0)
//            foreach ($matches[0] as $year) {
//                $row['name'] = str_replace($year,'',$row['name']);
//                if (strlen($year) == 4) $results = $TMDB->search_imdb($row['name'], $row['type'], $year);
//            }
//        if($results == false && $MODE == 'force' ) {
//            $results = $TMDB->search_imdb($row['name'], $row['type'], $year);
//        }
//
//    }
//
//    //UPDATING
//    if( $results != false || $MODE == 'force' ) {
//        $DB->query("UPDATE iptv_ondemand SET tmdb_id = '{$results[0]['id']}' WHERE id = '{$row['id']}'");
//        $TMDB->update_ondemand($row['id']);
//    }

    if($MODE == 'missing') {
        if($results = $TMDB->search_imdb($row['name'], $row['type'])) {
            $DB->query("UPDATE iptv_ondemand SET tmdb_id = '{$results[0]['id']}' WHERE id = '{$row['id']}'");
            $TMDB->update_ondemand($row['id']);
        }

    } else {
        // --update mode

        if($MODE == 'update_cover') {

            $TMDB->update_cover($row['id']);

        } else {

            $TMDB->update_ondemand($row['id']);

            if( $row['type'] == 'series' ) {

                $r_seasons = $DB->query("SELECT * FROM iptv_series_seasons WHERE series_id = {$row['id']} ");
                while ($row_season = $r_seasons->fetch_assoc()) {

                    $TMDB->update_season($row_season['id']);

                    $r_episode = $DB->query("SELECT * FROM iptv_series_season_episodes WHERE season_id = {$row_season['id']}");
                    while ($row_episode = $r_episode->fetch_assoc()) {

                        $TMDB->update_episode($row_episode['id']);

                    }

                }
            }


        }

    }



}