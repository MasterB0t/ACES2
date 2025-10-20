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

        case 'add_video':
            \ACES2\IPTV\Video::add($_REQUEST);
            break;

        case 'update_video':
            $video = new \ACES2\IPTV\Video((int)$_REQUEST['id']);
            $video->update($_REQUEST);
            break;

//        case 'remove_video':
//            $video = new \ACES2\IPTV\Video((int)$_REQUEST['id']);
//            $video->remove();
//            break;

        case 'get_seasons':
            $db = new \ACES2\DB();
            $series_id = (int)$_REQUEST['id'];
            $r_season = $db->query("SELECT id,number FROM iptv_series_seasons WHERE series_id = '$series_id' ORDER BY number ");
            $seasons = array();
            while($row=$r_season->fetch_assoc())
                $seasons[] = $row;
            setAjaxComplete($seasons);
            exit;

        case 'update_episode':
            $episode = new \ACES2\IPTV\Episode((int)$_REQUEST['id']);
            $episode->update($_REQUEST);
            break;

        case 'add_episode':
            $episode = \ACES2\IPTV\Episode::add($_REQUEST);
            break;


        case 'mass_edit_video':
            $ids = explode(',', $_REQUEST['ids']);
            foreach($ids as $id) {
                $video = new \ACES2\IPTV\Video((int) $id);
                if( is_array($_REQUEST['categories']) &&  count($_REQUEST['categories']) > 0)
                    $video->setCategories($_REQUEST['categories']);

                if((int)$_REQUEST['set_bouquets'])
                    $video->setBouquets($_REQUEST['bouquets']);
            }
            break;

        case 'remove_videos':
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            $ids = explode(',', $_REQUEST['ids']);
            foreach($ids as $id) {
                $video = new \ACES2\IPTV\Video((int) $id);
                $video->remove((bool)$_REQUEST['remove_source_file']);
            }
            break;

        case 'remove_episodes':
            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            $ids = explode(',', $_REQUEST['ids']);
            foreach($ids as $id) {
                $episode = new \ACES2\IPTV\Episode((int) $id);
                $episode->remove((bool)$_REQUEST['remove_source_file']);
            }
            break;

        case 'get_importers':
            $DB = new \ACES2\DB();
            $r=$DB->query("SELECT id,pid,progress,args FROM iptv_proccess WHERE name = 'xc_video_importer' ");
            $json = [];
            while($row=$r->fetch_assoc()) {
                $args = json_decode($row['args'],1);
                $server = new \ACES2\IPTV\Server($args['server_id']);

                $json[] = array(
                    'type' => 'xc',
                    "id" => $row['id'],
                    'pid' => $row['pid'],
                    'progress' => $row['progress'],
                    'server_name' => $server->name,
                    'host' => $args['host'],
                    'importing_movies' => (bool)$args['import_movies'],
                    'importing_series' => (bool)$args['import_series'],
                    'import_from_category'=> $args['import_from_category']
                );
            }

            $r = $DB->query("SELECT p.* FROM iptv_proccess p  WHERE p.name = 'm3u_vod_upload' ");
            while ($row = $r->fetch_assoc()) {
                $server = new \ACES2\IPTV\Server($row['server_id']);
                $json[] = array(
                    'type' => 'm3u',
                    "id" => $row['id'],
                    "pid" => $row['pid'],
                    'server_name' => $server->name,
                    'progress' => $row['progress'],
                    'description' => $row['description']
                );

            }

            ajaxSuccess($json);

            break;

        case 'stop_importer':
            $db = new \ACES2\DB();
            $importer_id = (int)$_REQUEST['importer_id'];
            $db->query("DELETE FROM iptv_proccess WHERE id = '$importer_id' ");
            break;

        case 'import_from_directory':
            $db = new \ACES2\DB();
            $Server = new \ACES2\IPTV\SERVER((int)$_REQUEST['server_id']);

            if(empty($_POST['directory'])) setAjaxError( 'Select a directory to import.');
            else $opts['directory'] = $db->escString($_POST['directory']);

            $opts['categories'] = [];
            if(is_array($_REQUEST['categories']))
                foreach($_REQUEST['categories'] as $category) {
                    if($category)
                        $opts['categories'][] = (int)$category;
                }

            if(count($opts['categories'])<1)
                setAjaxError("At lease one category is required");

            $opts['bouquets'] = [];
            if(is_array($_REQUEST['bouquets']))
                foreach($_REQUEST['bouquets'] as $bouquet) {
                    if((int)$bouquet)
                        $opts['bouquets'][] = (int)$bouquet;
                }

            $opts['do_not_download_images'] = (bool)$_POST['do_not_download_images'];
            $opts['no_recursive'] = !empty($_POST['series']) && !empty($_POST['no_recursive']);

            if(!empty($_POST['series']))  {
                $action = $Server::ACTION_SERIES_DIR_IMPORT; $opts['type'] = 'series'; }
            else {
                $action = $Server::ACTION_VIDEO_DIR_IMPORT; $opts['type'] = 'movies'; }

            $o = serialize($opts);

            $db->query("INSERT INTO iptv_proccess (name,args,server_id) 
                VALUES('video_directory_import','$o',$Server->id) ");
            $process_id=$db->insert_id;

            $Server->send_action($action,array('process_id' => $process_id));

            break;

        case 'update_tmdb_info':

            session_write_close();
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $download_logos = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::VOD_DONT_DOWNLOAD_LOGOS);

            foreach($_REQUEST['ids'] as $id) {
                $video = new \ACES2\IPTV\Video((int) $id);
                if($video->tmdb_id) {
                    $video->updateFromTMDB($download_logos);
                }


                if($video->is_series) {
                    $db = new \ACES2\DB;
                    $r_season = $db->query("SELECT id FROM iptv_series_seasons where series_id = '$video->id' ");
                    while($row=$r_season->fetch_assoc()) {

                        $season = new \ACES2\IPTV\Season($row['id']);
                        $season->updateFromTMDB(false);

                        $r_episodes = $db->query("SELECT id FROM iptv_series_season_episodes 
                            WHERE season_id = '$season->id' ");

                        while($episode_row=$r_episodes->fetch_assoc()) {
                            $episode = new \ACES2\IPTV\Episode($episode_row['id']);
                            $episode->updateFromTMDB($download_logos);
                        }

                    }

                }


            }

            break;

        case 'remove_video_report':

            $remove_vod = (bool)$_REQUEST['remove_vod'];
            $db = new \ACES2\DB;


            if(count(\ACES2\Process::getProcessByType('iptv.remove_video_reports')) > 0 )
                throw new \Exception("Another process is still running.");


            $Process = \ACES2\Process::add('iptv.remove_video_reports', 1,  0,
                'Removing video reports.');
            setAjaxComplete('',false);

            if($_REQUEST['ids'] == 'all' ) {
                //MAKE SURE WHE DONT GET REPORTS WHERE VIDEO FILE DO NOT EXIST

                if($remove_vod) {
                    $r=$db->query("SELECT r.file_id,f.movie_id,f.episode_id FROM iptv_video_reports r 
                        INNER JOIN iptv_video_files f ON r.file_id = f.id  ");
                    $total = $r->num_rows;
                    $progress = 0;
                    while($row = $r->fetch_assoc()) {
                        try {
                            if($row['movie_id']) {
                                $Movie = new \ACES2\IPTV\Video($row['movie_id']);
                                $Movie->remove();
                            } else {
                                $Episode = new \ACES2\IPTV\Episode($row['episode_id']);
                                $Episode->remove();
                            }
                        } catch(\Exception $e) {
                            $ignore=true;
                        }

                        //BELOW WE ARE TRUNCATE THE TABLE AND THIS DELETE MIGHT LOOK NOT NECESSARY BUT CLIENT WILL MOAN IF THEY
                        //DO NOT SEE TABLE CLEARING UP IN REAL TIME. SO WHILE WE REMOVE THE VOD WE REMOVE THE REPORT.
                        $db->query("DELETE FROM iptv_video_reports WHERE file_id = '{$row['file_id']}' ");
                        $progress++;
                        $Process->calculateProgress($progress ,$total);
                        if(!$Process->isAlive())
                            exit;

                        logd("Removing Vod #{$row['file_id']}");

                    }

                }

                $Process->remove();
                $db->query("DELETE FROM iptv_video_reports ");

            } else {

                $ids = explode(',', $_REQUEST['ids']);

                foreach($ids as $id ) {

                    $db->query("DELETE FROM iptv_video_reports WHERE file_id = '$id' ");
                    if($remove_vod) {
                        $r=$db->query("SELECT movie_id,episode_id FROM iptv_video_files WHERE id = '$id' ");
                        $row=$r->fetch_assoc();

                        try {

                            if($row['movie_id']) {
                                $Movie = new \ACES2\IPTV\Video($row['movie_id']);
                                $Movie->remove();
                            } else {
                                $Episode = new \ACES2\IPTV\Episode($row['episode_id']);
                                $Episode->remove();
                            }

                        } catch(\Exception $e) {
                            $ignore =1;
                        }

                    }
                }

            }
            break;

        case 'mass_edit_episodes':

            foreach($_REQUEST['title'] as $id => $title ) {

                $post = array(
                    'title' => $title,
                    'episode_number' => $_REQUEST['episode_number'][$id],
                    'season_number' => $_REQUEST['season_number'][$id],
                    'about' => $_REQUEST['about'][$id],
                    'runtime_minute' => $_REQUEST['runtime_minute'][$id],
                    'rate' => $_REQUEST['rate'][$id],
                    'release_date' => $_REQUEST['release_date'][$id],
                );

                $Episode = new \ACES2\IPTV\Episode($id);
                $Episode->update($post);
                if(!empty($_REQUEST['cover'][$id]))
                    $Episode->setLogo($_REQUEST['cover'][$id]);

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