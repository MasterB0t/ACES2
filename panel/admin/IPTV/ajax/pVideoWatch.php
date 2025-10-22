<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

try {
    switch($_REQUEST['action']) {

        case 'folder_watch':
            $db = new \ACES2\DB();

            $EditID = (int)$_REQUEST['id'];
            $is_xc =0;

            $enabled = (bool)$_REQUEST['enabled'];
            $opts['do_not_download_images'] = (bool)$_REQUEST['do_not_download_images'];
            $opts['tmdb_lang'] = $_POST['tmdb_lang'];

            $interval = (int)$_REQUEST['interval'];
            if(empty($interval))
                setAjaxError("Interval is required.");

            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);

            $opts['categories'] = [];
            if(is_array($_REQUEST['categories']))
                foreach($_REQUEST['categories'] as $category) {
                    if((int) $category )
                        $opts['categories'][] = $category;
                }
            if(count($opts['categories']) < 1)
                setAjaxError("At least one category is required.");

            $opts['bouquets'] = [];
            if(is_array($_REQUEST['bouquets']))
                foreach($_REQUEST['bouquets'] as $bouquet)
                    if((int) $bouquet )
                        $opts['bouquets'][] = $bouquet;

            if($_REQUEST['xc_account']) {

                $is_xc = 1;

                $opts['xc_account'] = (int)$_REQUEST['xc_account'];
                $xc_account = new \ACES2\IPTV\XCAPI\XcAccount($opts['xc_account']);

                //REMOVING PORT FROM URL, FIX FOR aces_xc_video_importer.php SCRIPT.
                $opts['host'] = str_replace(":{$xc_account->port}","",$xc_account->url);
                $opts['port'] = $xc_account->port;
                $opts['username'] = $xc_account->username;
                $opts['password'] = $xc_account->password;
                $opts['parallel_download'] = (int)$_REQUEST['parallel_download'] ?: 1 ;
                $opts['server_id'] = $Server->id;

                $opts['import_from_category'] = !empty($_REQUEST['import_from_category']) ?
                    $_REQUEST['import_from_category'] : 0;

                $opts['import_from_category_name'] = !empty($_REQUEST['import_from_category_name'])
                    ? $db->escString($_REQUEST['import_from_category_name']) : "All Categories";

                $opts['import_movies'] = (!empty($_REQUEST['import_movies'])) ? 1 : 0;
                $opts['import_series'] = (!empty($_REQUEST['import_series'])) ? 1 : 0;
                $opts['transcoding'] = $db->escString($_REQUEST['transcoding']);
                $opts['get_info_from']  = empty($_POST['get_info_from']) ?  'tmdb' : $_POST['get_info_from'];

                if(!$opts['import_movies'] && !$opts['import_series'] )
                    setAjaxError("At least import movies or series must be selected.");

                $watch =  $opts['import_from_category_name'];

            } else {
                //IS FOLDER WATCH
                $type = $_POST['type'] == 'series' ? 'series' : 'movie';

                if(empty($_REQUEST['directory']))
                    setAjaxError( 'Select a directory.');
                $opts['directory'] = $db->escString($_REQUEST['directory']);

                $watch = urldecode($opts['directory']);
            }


            $o = json_encode($opts);


            if($EditID)
                $db->query("UPDATE iptv_folder_watch SET type = '$type', server_id = '$Server->id', params = '$o', 
                             enabled = '$enabled', interval_mins = '$interval', watch = '$watch',
                             is_xc = '$is_xc' 
                         WHERE id = '$EditID'");
            else
                $db->query("INSERT INTO iptv_folder_watch (type,server_id,params,enabled,interval_mins,watch,is_xc) 
                            VALUES('$type','$Server->id','$o','$enabled', '$interval', '$watch', '$is_xc' ) ");


            break;

        case 'start_watch':
            $WatchID = (int)$_REQUEST['id'];
            $db = new \ACES2\DB();
            $db->query("UPDATE iptv_folder_watch SET last_run = 0 WHERE id = '$WatchID'");
            break;

        case 'stop_watch':
            $pid = (int)$_REQUEST['pid'];
            $db = new \ACES2\DB();
            $db->query("DELETE FROM iptv_proccess WHERE id = '$pid'");
            setAjaxComplete();
            break;

        case 'mass_watch_edit' :

            $db = new \ACES2\DB();

            $update = [];
            if($_REQUEST['enabled'] != '')
                $update['enabled'] = $_REQUEST['enabled'] == 1 ? 1 : 0 ;

            if($_REQUEST['set_interval'] != '')
                $update['interval_mins'] = (int)$_REQUEST['interval_mins'];

            //VALIDATE BOUQUETS
            $bouquets =[];
            if($_REQUEST['set_bouquets'])
                foreach($_REQUEST['bouquets'] as $b) {
                    if((int) $b)
                        $bouquets[] = $b;
                }

            $sql = \ACES2\DB::arrayToSql($update);

            foreach( explode(",",$_REQUEST['ids']) as  $id ) {
                $id = (int)$id;
                if($sql)
                    $db->query("UPDATE iptv_folder_watch SET $sql WHERE id = '$id'");

                if($_REQUEST['set_bouquets']) {
                    $r = $db->query("SELECT params FROM iptv_folder_watch WHERE id = '$id'");
                    if($row=$r->fetch_assoc()) {
                        $params = json_decode($row['params'],1);
                        $params['bouquets'] = $bouquets;
                        $db->query("UPDATE iptv_folder_watch SET params = '".json_encode($params)."' WHERE id = '$id'");
                    }

                }

            }


            break;

        case 'remove_watch':
            $ids = explode(',',$_REQUEST['ids']);
            foreach($ids as $id) {
                $WatchID = (int)$id;
                $db = new \ACES2\DB();
                $db->query("DELETE FROM iptv_folder_watch WHERE id = '$WatchID'");
            }

            break;

        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();