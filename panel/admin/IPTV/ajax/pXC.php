<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission()) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

try {
    switch($_REQUEST['action']) {

        case 'add_account':
            $account = \ACES2\IPTV\XCAPI\XCAccount::add($_REQUEST);
            break;

        case 'update_account':
            $account = new \ACES2\IPTV\XCAPI\XCAccount((int)$_REQUEST['id']);
            $account->update($_REQUEST);
            break;

        case 'remove_account':
            $account = new \ACES2\IPTV\XCAPI\XCAccount((int)$_REQUEST['id']);
            $account->remove();
            break;

        case 'force_refresh':
            $account_id = (int)$_REQUEST['id'];
            $db = new \ACES2\DB();
            $db->query("UPDATE iptv_xc_videos_imp SET  last_update_time = 0 WHERE id = $account_id");
            break;

        case 'import_vods':
            $account = new \ACES2\IPTV\XCAPI\XCAccount((int)$_REQUEST['id']);
            $server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);
            $args['transcoding'] = ($_REQUEST['transcoding']) ? $_POST['transcoding'] : 'copy';

            $bouquets = [];
            if(is_array($_REQUEST['bouquets']))
                foreach($_REQUEST['bouquets'] as $bouquet)
                    if((int)$bouquet)
                        $bouquets[] = $bouquet;

            $categories = [];
            if(is_array($_REQUEST['categories']))
                foreach($_REQUEST['categories'] as $category) {
                    if((int)$category)
                        $categories[] = $category;
                }

            $args['host'] = $account->url;
            $args['port'] = $account->port;
            $args['username'] = $account->username;
            $args['password'] = $account->password;
            $args['parallel_download'] = (int)$_POST['parallel_downloads'] ?: 1;
            $args['server_id'] = (int)$server->id;
            $args['import_from_category'] = (!empty($_POST['import_from_category'])) ? $_POST['import_from_category'] : 0;
            $args['import_movies'] = (!empty($_POST['import_movies'])) ? 1 : 0;
            $args['import_series'] = (!empty($_POST['import_series'])) ? 1 : 0;
            $args['force_import'] = (!empty($_POST['force_import'])) ? 1 : 0;
            $args['get_info_from']  = empty($_POST['get_info_from']) ?  'tmdb' : $_POST['get_info_from'];
            $args['tmdb_lang'] = $_POST['tmdb_lang'];
            $args['do_not_download_images'] = (bool)$_POST['do_not_download_images'];

            $args['bouquets'] = $bouquets;
            $args['categories'] = $categories;
            $server_id = $args['server_id'];

            $args = json_encode($args);

            $db= new \ACES2\DB;

            $db->query("INSERT INTO iptv_proccess (name,args,server_id) 
                VALUES('xc_video_importer','$args','{$server_id}') ");
            $pid = $db->insert_id;

            exec("php /home/aces/bin/aces_xc_video_importer.php $pid > /dev/null & ");

            break;





        default:

            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    logE($e->getMessage());
    setAjaxError($e->getMessage());
}

setAjaxComplete();