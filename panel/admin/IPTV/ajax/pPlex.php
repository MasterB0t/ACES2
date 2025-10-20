<?php

$ACES = new \ACES2\ADMIN();
$DB = new \ACES2\DB;
$json = array();

if (!$ACES->isLogged()) {
    $json['not_logged'] = 1;
    echo json_encode($json);
    exit;
} else if (!$ACES->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError("You don't have privileges to perform this action.");
}

switch (strtoupper(@$_REQUEST['action'])) {

    case 'ADD_ACCOUNT':
        $host = $_REQUEST['host'];
        //$port = (int)$_REQUEST['port'];
        $token = $_REQUEST['plex_token'];

        $curl = curl_init("{$host}/activities?X-Plex-Token={$token}");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);
        $errno = curl_errno($curl);
        $curl_response_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($errno == 28)
            setAjaxError("Timeout Error. Make sure the plex server address and port are correctly.");
        else if ($curl_response_code == 401)
            setAjaxError("The plex token entered is invalid.");


        $DB->query("INSERT INTO iptv_plex_accounts (host, plex_token) VALUES ('$host',  '$token')");


        break;

    case 'REMOVE_ACCOUNT':
        $account_id = (int)$_REQUEST['account_id'];
        $DB->query("DELETE FROM iptv_plex_accounts WHERE id = $account_id");
        break;

    case 'START_IMPORT':

        $account_id = (int)$_REQUEST['account_id'];

        $r = $DB->query("SELECT id,host,port,plex_token FROM iptv_plex_accounts WHERE id = $account_id ");
        if (!$plex_account = $r->fetch_assoc()) {
            setAjaxError("System Error.");
        }

        $ServerID = (int)$_REQUEST['server_id'];
        $Server = new \ACES\IPTV\SERVER($ServerID);

        $args['transcoding'] = ($_POST['transcoding']) ? $_POST['transcoding'] : 'copy';

        $bouquets = [];
        if (is_array($_REQUEST['bouquets']))
            foreach ($_REQUEST['bouquets'] as $b) $bouquets[] = (int)$b;


        $categories = array();
        if (!is_array($_POST['categories']))
            setAjaxError("At least one category must be selected.");
        else
            foreach ($_POST['categories'] as $ii => $c) {
                $categories[] = (int)$c;
            }

        $args['host'] = $plex_account['host'];
        $args['plex_token'] = $plex_account['plex_token'];
        $args['parallel_download'] = (int)$_POST['parallel_downloads'];
        $args['server_id'] = $ServerID;
        $args['import_from_category'] = (int)$_POST['import_from_category'];
        $args['import_movies'] = empty($_POST['import_movies']) ? 0 : 1;
        $args['import_series'] = empty($_POST['import_series']) ? 0 : 1;
        $args['force_import'] = (!empty($_POST['force_import'])) ? 1 : 0;
        $args['get_info_from'] = empty($_POST['get_info_from']) ? 'tmdb' : $_POST['get_info_from'];
        $args['tmdb_lang'] = $_POST['tmdb_lang'];

        if (!$args['import_from_category'])
            setAjaxError("Select a category to import from");

        $args['bouquets'] = $bouquets;
        $args['categories'] = $categories;

        $params = json_encode($args);

        if (isset($_REQUEST['add_to_watch'])) {

            $DB->query("INSERT INTO iptv_folder_watch ( is_plex, enabled, server_id, last_run,  params) 
                VALUES (1, 1, 1, 0, '$params')  ");

        } else {

            $DB->query("INSERT INTO iptv_proccess (name,args) VALUES('plex_importer','$params') ");
            $pid = $DB->insert_id;

            session_write_close();

            exec("php /home/aces/bin/plex_import.php $pid > /dev/null & ");
        }

        setAjaxComplete();
        break;

    case 'ADD_TO_WATCH':

        break;


    default:
        setAjaxError("Unknown action.");

}

setAjaxComplete();