<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged(false)){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

$db = new \ACES2\DB();

try {
    switch($_REQUEST['action']) {

        case 'get_progress':
            $r=$db->query("SELECT id,progress,description FROM iptv_proccess 
                               WHERE name like 'provider_add_content' OR name like 'provider_update_content' ");
            $progress = $r->fetch_all(MYSQLI_ASSOC);
            setAjaxComplete($progress);
            exit;

        case 'stop_process':
            $pid = (int)$_REQUEST['process_id'];
            $db->query("DELETE FROM  iptv_proccess WHERE id = '{$pid}'");
            break;

        case 'clear_content':
            set_time_limit(-1);
            session_write_close();
            ignore_user_abort(true);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();
            $provider_id = (int)$_REQUEST['provider_id'];
            $db->query("DELETE FROM iptv_provider_content_vod WHERE provider_id = '{$provider_id}'");
            $db->query("DELETE FROM iptv_provider_content_episodes WHERE provider_id = '{$provider_id}'");
            $db->query("DELETE FROM iptv_provider_content_streams WHERE provider_id = '{$provider_id}'");
            break;

        case 'remove_provider_account':
            //TODO MOVE THIS TO \ACES2\IPTV\XCAPI\XCAccount->remove()
            $account_id = (int)$_REQUEST['account_id'];
            $db = new \ACES2\DB();
            $db->query("DELETE FROM iptv_xc_videos_imp WHERE id = '$account_id'");
            $db->query("DELETE FROM iptv_provider_content_vod WHERE provider_id = '{$account_id}'");
            $db->query("DELETE FROM iptv_provider_content_episodes WHERE provider_id = '{$account_id}'");
            break;

        case 'get_seasons_of_series':
            $series_id = (int)$_REQUEST['series_id'];
            $db = new \ACES2\DB();
            $r=$db->query("SELECT season_number FROM iptv_provider_content_episodes 
                     WHERE content_vod_id = '{$series_id}'
                     GROUP BY season_number ORDER BY season_number");
            setAjaxComplete($r->fetch_all(MYSQLI_ASSOC));
            exit;

        case 'get_provider_categories':

            if($provider_id = (int)$_REQUEST['provider_id'])
                $where = " WHERE provider_id = '{$provider_id}' ";


            $r=$db->query("SELECT category_name as name,category_id as id FROM iptv_provider_content_vod 
                                               $where
                                               GROUP BY category_name ORDER BY category_name");

            setAjaxComplete($r->fetch_all(MYSQLI_ASSOC));

            break;


        case 'update_content':

            $db = new \ACES2\DB();

            $r=$db->query("SELECT id FROM iptv_proccess 
                WHERE name = 'provider_update_content' AND sid = '{$_REQUEST['id']}'");
            if($r->num_rows>0)
                throw new \Exception("A process is already running for this provider.");

            $xc_account = new \ACES2\IPTV\XCAPI\XCAccount($_REQUEST['id']);


            exec("php /home/aces/bin/update_provider_content.php {$_REQUEST['id']} > /dev/null & ");
            setAjaxComplete();
            break;



        case 'add_provider_content':

            $db = new \ACES2\DB;

            $main_category = (int)$_POST['categories'][0];
            if(!$main_category)
                throw new \Exception("At least one category is required.");

            $Server = new \ACES2\IPTV\Server((int)$_POST['server_id']);
            $Transcoding = $_POST['transcoding'];
            $Container = $_POST['container'];
            $GetTMDBInfo = $_POST['get_info_from'] === 'tmdb' ? true : false;
            $do_not_download_logos = (bool)$_POST['do_not_download_images'];
            $MaxParallelDownloads = (int)$_POST['parallel_downloads'] ? (int)$_POST['parallel_downloads'] : 1 ;
            $IgnoreMoviesByName = true;

            $ids = explode(",",$_POST['ids']);

            $Downloading = [];
            $Total = count($ids);
            $Current = 0;

            set_time_limit(-1);
            session_write_close();
            ignore_user_abort(true);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            $db->query("INSERT INTO iptv_proccess ( name, status, description, server_id  ) 
                VALUES('provider_add_content', 1, 'Adding to $Server->name', '$Server->id') ");
            $ProcessID = $db->insert_id;

            register_shutdown_function(function(){
                global $ProcessID,$db;
                $db->query("DELETE FROM iptv_proccess WHERE id = '$ProcessID'");
            });

            function progress() {

                //THIS SLEEP IS A QUICK HACK FOR PARALLEL DOWNLOAD.
                //sleep(2);
                global $db,$Total, $Current, $MaxParallelDownloads, $Downloading, $ProcessID;

                $sql = implode(",",$Downloading);

                while(true) {
                    $r=$db->query("SELECT id FROM iptv_video_files WHERE id in ($sql) AND is_processing = '1' ");
                    if($r->num_rows >= $MaxParallelDownloads ){
                        sleep(5);
                    } else {
                        $rp=$db->query("SELECT id FROM iptv_proccess WHERE id = '$ProcessID' ");
                        if(!$rp->fetch_assoc() )
                            exit;
                        $progress = (int) (($Current / $Total) * 100) ;
                        $db->query("UPDATE iptv_proccess SET progress = '$progress' WHERE id = '$ProcessID' ");
                        break;

                    }

                }

            }

            foreach($ids as $id) {
                $Current++;
                if((int)$id) {
                    $r=$db->query("SELECT id,provider_id,provider_movie_id,provider_series_id,is_series,added_id
                            FROM iptv_provider_content_vod 
                             WHERE id = '{$id}'");
                    $vod = $r->fetch_assoc();

                    if($vod['is_series']) {

                        $XCAccount = new \ACES2\IPTV\XCAPI\XCAccount($vod['provider_id']);
                        $XCSeries = \ACES2\IPTV\XCAPI\Series::fetchFromPortal($vod['provider_id'], $vod['provider_series_id']);

                        $youtube = $XCSeries->youtube_trailer ? 'https://www.youtube.com/watch?v=' . $XCSeries->youtube_trailer : '';
                        $backlogo = $XCSeries->backdrop_path[0];

                        $genres = explode(',',$XCSeries->genre);

                        $genre1 = $genres[0];
                        $genre2 = $genres[1];
                        $genre3 = $genres[2];

                        $plot = $db->escString($XCSeries->plot);
                        $cast = $db->escString($XCSeries->cast);
                        $director = $db->escString($XCSeries->director);
                        $name = $db->escString($XCSeries->name);


                        $r_is_series = $db->query("SELECT id FROM iptv_ondemand WHERE name like lower('$name') ");
                        //$r_is_series = $db->query("SELECT id FROM iptv_ondemand WHERE id = '{$vod['added_id']}' ");
                        if(!$new_series_id=$r_is_series->fetch_assoc()['id']) {

                            $post = array(
                                'name' => $XCSeries->name,
                                'type' => 'series',
                                'youtube_trailer' => $youtube,
                                'genre1' => $genre1,
                                'genre2' => $genre2,
                                'genre3' => $genre3,
                                'categories' => $_POST['categories'],
                                'bouquets' => $_POST['bouquets'],
                                'about' => $XCSeries->plot,
                                'director' => $XCSeries->director,
                                'rating' => $XCSeries->rating,
                                'release_date' => $XCSeries->release_date,
                                'tmdb_id' => (int)$XCSeries->tmdb_id,
                            );

                            $Series = \ACES2\IPTV\Video::add($post);
                            $new_series_id = $Series->id;

                            if($do_not_download_logos)
                                $Series->setLogos($XCSeries->cover, $XCSeries->backdrop_path[0]);
                            else
                                $Series->downloadLogos($XCSeries->cover, $XCSeries->backdrop_path[0]);

                            $Series->setBouquets($_POST['bouquets']);

                            //IF ADD PORTAL CATEGORY...
                            $categories = $_POST['categories'];
                            if(array_search("-2", $categories) !== false) {
                                $cat_name = $XCSeries->getCategoryName();
                                $r_cat = $db->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$cat_name')");
                                if(!$cat_id = $r_cat->fetch_assoc()['id']) {
                                    $NewCat = \ACES2\IPTV\Category::add($cat_name, false);
                                    $cat_id = $NewCat->id;
                                }
                                $index = array_search("-2", $categories);
                                $categories[$index] = $cat_id;
                            }

                            if($GetTMDBInfo) {
                                if(!$Series->tmdb_id) {
                                    if($Series->release_year)
                                        $results = \ACES2\IPTV\TMDB\Series::search($Series->name, $Series->release_year);
                                    else
                                        $results = \ACES2\IPTV\TMDB\Series::search($Series->name );

                                    if(count($results) > 0) {
                                        $Series->setTmdbID($results[0]['id']);
                                    }
                                }

                                $Series->updateFromTMDB(0);

                            }

                            //IF ADD GENRES AS CATEGORY
                            if(array_search("-1", $categories) !== false) {

                                $index = array_search("-1", $categories);
                                unset($categories[$index]);

                                $genress = explode("/","{$Series->genre1}/{$Series->genre2}/{$Series->genre3}/");
                                foreach(explode("/","{$Series->genre1}/{$Series->genre2}/{$Series->genre3}") as $genre) {
                                    if($genre) {
                                        $cat = \ACES2\IPTV\Category::getCategoryByName($genre);
                                        if($cat === false)
                                            $cat = \ACES2\IPTV\Category::add($genre, false);
                                        $categories[] = $cat->id;
                                    }
                                }
                            }

                            $Series->setCategories($categories);

                        }


                        $db->query("UPDATE iptv_provider_content_vod SET added_id = '$new_series_id'
                                 WHERE id = '{$id}'");

                        foreach($XCSeries->episodes as  $seasons) {

                            foreach($seasons as $episode) {

                                $XCEpisode = new \ACES2\IPTV\XCAPI\Episode($episode);
                                $XCEpisode->setAccount($XCAccount);
                                $XCSeason = $XCSeries->getSeason($XCEpisode->season);

                                //CHECK IF SEASON EXIST
                                $r_season = $db->query("SELECT id FROM iptv_series_seasons 
                                            WHERE series_id = '{$new_series_id}' 
                                            AND number = '$XCEpisode->season' ");

                                if(!$season_id = $r_season->fetch_assoc()['id']) {

                                    //ADDING SEASON
                                    $tmdb_season =null;
                                    if($GetTMDBInfo && $Series->tmdb_id ) {
                                        //INFO FROM TMDB.
                                        $tmdb_season = new \ACES2\IPTV\TMDB\Season($Series->tmdb_id, $XCEpisode->season);
                                        $NewSeason =  ACES2\IPTV\Season::add(
                                            $new_series_id, $XCEpisode->season, $tmdb_season->air_date, $tmdb_season->overview, $tmdb_season->id );
                                    } else
                                        $NewSeason =  ACES2\IPTV\Season::add(
                                            $new_series_id, $XCEpisode->season, $XCSeason->air_date, $XCSeason->overview );

                                    $e_img = $XCEpisode->cover_big == '' ? $XCEpisode->movie_image : $XCEpisode->cover_big;

                                    if($do_not_download_logos) {
                                        $NewSeason->setLogo( $tmdb_season ? $tmdb_season->getPosterPath() : $e_img );
                                    } else {
                                        $NewSeason->downloadLogo($tmdb_season ? $tmdb_season->getPosterPath() : $e_img);
                                    }


                                } else
                                    $NewSeason = new ACES2\IPTV\Season($season_id);


                                //CHECK IF EPISODE EXIST
                                $r_episode = $db->query("SELECT id FROM iptv_series_season_episodes 
                                    WHERE season_id = '{$season_id}' AND number = '$XCEpisode->episode_num' ");

                                //WE ARE NOT DUPLICATING EPISODE HERE. IF EPISODE NUMBER EXIST WE IGNORE IT.
                                if($r_episode->num_rows < 1) {

                                    if($GetTMDBInfo && $Series->tmdb_id ) {
                                        $tmdb_episode = new \ACES2\IPTV\TMDB\Episode($XCEpisode->episode_num, $XCEpisode->season, $Series->tmdb_id);
                                        $NewEpisode = \ACES2\IPTV\Episode::add2($tmdb_episode->name,$XCEpisode->episode_num,
                                            $new_series_id, $NewSeason->number, (string)$tmdb_episode->air_date, $XCEpisode->getStreamLink(),
                                            $Transcoding, $Server->id, $tmdb_episode->overview, $tmdb_episode->id, $XCEpisode->container_extension,
                                            $tmdb_episode->vote_average );

                                    } else {
                                        $NewEpisode = \ACES2\IPTV\Episode::add2($XCEpisode->title,$XCEpisode->episode_num,
                                            $new_series_id, $NewSeason->number, (string)$XCEpisode->release_date, $XCEpisode->getStreamLink(),
                                            $Transcoding, $Server->id, $XCEpisode->plot, 0, $XCEpisode->container_extension, $XCEpisode->rating );
                                    }


                                    if($do_not_download_logos)
                                        $NewEpisode->setLogo($XCEpisode->cover_big);
                                    else
                                        $NewEpisode->downloadLogo($XCEpisode->cover_big);

                                    $db->query("UPDATE iptv_provider_content_episodes SET added_id = '$NewEpisode->id' 
                                      WHERE provider_id = '$XCAccount->id' AND episode_id = '$XCEpisode->id' ");

                                    $Downloading[] = $NewEpisode->file_id;

                                    progress();

                                }

                            }
                        }

                    } else {

                        //MOVIES

                        $movie = \ACES2\IPTV\XCAPI\Movie::fetchFromPortal($vod['provider_id'], $vod['provider_movie_id']);
                        //$movie->add($_POST['categories'],$_POST['bouquets'], $_POST['transcoding'], $_POST['server_id']);

                        $youtube = $movie->youtube_trailer ?  'https://www.youtube.com/watch?v=' . $movie->youtube_trailer : '';
                        $release_year = (int)explode("-",$movie->release_date)[0];

                        $genres = explode(",",$movie->genre);

                        $post = array(
                            'name' => $movie->name,
                            'type' => 'movies',
                            'youtube_trailer' => $youtube,
                            'genre1' => $genres[0],
                            'genre2' => $genres[1],
                            'genre3' => $genres[2],
                            'about' => $movie->plot,
                            'director' => $movie->director,
                            'rating' => $movie->rating,
                            'release_date' => $movie->release_date,
                            'tmdb_id' => (int)$movie->tmdb_id,
                            'web_file' => '',
                            'categories' => $_POST['categories'],
                            'bouquets' => $_POST['bouquets'],
                            'container' => $movie->container_extension,
                            'transcoding' => $_POST['transcoding'],
                            'server_id' => $_POST['server_id'],
                            'file' => $movie->getStreamLink()
                        );


                        $vod = \ACES2\IPTV\Video::add($post);
                        $db->query("UPDATE iptv_provider_content_vod SET added_id = '$vod->id' WHERE id = '$id' ");
                        $Downloading[] = $vod->file_id;

                        if($do_not_download_logos)
                            $vod->setLogos($movie->movie_image, $movie->backdrop_path[0]);
                        else
                            $vod->downloadLogos($movie->movie_image, $movie->backdrop_path[0]);


                        //IF ADD PORTAL CATEGORY...
                        $categories = $_POST['categories'];
                        if(array_search("-2", $categories) !== false) {
                            $cat_name = $movie->getCategoryName();
                            $r_cat = $db->query("SELECT id FROM iptv_stream_categories WHERE lower(name) = lower('$cat_name')");
                            if(!$cat_id = $r_cat->fetch_assoc()['id']) {
                                $NewCat = \ACES2\IPTV\Category::add($cat_name, false);
                                $cat_id = $NewCat->id;
                            }
                            $index = array_search("-2", $categories);
                            $categories[$index] = $cat_id;
                        }

                        if( $GetTMDBInfo ) {
                            if(!$movie->tmdb_id) {
                                $results = \ACES2\IPTV\TMDB\Movie::search($movie->name, (int)$release_year);
                                if((int)$results[0]['id'] > 0 ) {
                                    $vod->setTmdbID($results[0]['id']);
                                    $vod->updateFromTMDB(true);
                                }

                            } else {
                                $vod->updateFromTMDB($movie->tmdb_id);
                            }
                        }

                        //IF ADD GENRES AS CATEGORY
                        if(array_search("-1", $categories) !== false) {

                            $index = array_search("-1", $categories);
                            unset($categories[$index]);

                            $genress = explode("/","{$vod->genre1}/{$vod->genre2}/{$vod->genre3}/");
                            foreach(explode("/","{$vod->genre1}/{$vod->genre2}/{$vod->genre3}") as $genre) {
                                if($genre) {
                                    $cat = \ACES2\IPTV\Category::getCategoryByName($genre);
                                    if($cat === false)
                                        $cat = \ACES2\IPTV\Category::add($genre, false);
                                    $categories[] = $cat->id;
                                }
                            }
                        }

                        $vod->setCategories($categories);
                        progress();

                    }

                }

            }

            break;

        case 'add_episodes' :

            $force_add = (bool)$_REQUEST['force_add'];

            $series_id = (int)$_REQUEST['series_id'];
            $Series = new \ACES2\IPTV\Video($series_id);
            $transcoding = $db->escString($_REQUEST['transcoding']);
            $Server = new \ACES2\IPTV\Server((int)$_REQUEST['server_id']);
            $file_container = !empty($_REQUEST['container']) ? $_REQUEST['container'] : 'mp4';
            $info_from = $_REQUEST['get_info_from'];
            $download_logos = (bool)$_REQUEST['download_logos'];

            $ids = explode(",",$_REQUEST['episodes']);

            set_time_limit(-1);
            session_write_close();
            ignore_user_abort(true);
            echo json_encode(array('complete' => 1, 'status' => 1));
            fastcgi_finish_request();

            foreach($ids as $id) {

                if((int)$id) {
                    $r=$db->query("SELECT e.content_vod_id ,e.provider_id,e.series_id FROM iptv_provider_content_episodes e
                                                WHERE episode_id = '$id' ");
                    $row = $r->fetch_assoc();

                    $xc_series = \ACES2\IPTV\XCAPI\Series::fetchFromPortal($row['provider_id'], $row['series_id']);
                    $xc_episode = $xc_series->getEpisodeByID($id);
                    $xc_episode->setAccount(new \ACES2\IPTV\XCAPI\XCAccount($row['provider_id']));

                    $r_season=$db->query("SELECT s.id as season_id, s.number as season_number,e.number AS episode_number 
                             FROM iptv_series_seasons s
                             LEFT JOIN iptv_series_season_episodes e ON e.number = '$xc_episode->episode_num'
                             WHERE series_id = '$series_id'  
                                     AND s.number = '$xc_episode->season' ");

                    $row =$r_season->fetch_assoc();

                    if(!$row['episode_number'] || $force_add) {
                        $b=10;

                        $Episode = \ACES2\IPTV\Episode::add2(
                            $xc_episode->title,
                            $xc_episode->episode_num,
                            $series_id,
                            $xc_episode->season,
                            $xc_episode->release_date,
                            $xc_episode->getStreamLink(),
                            $transcoding,
                            $Server->id,
                            $xc_episode->plot,
                            (int)$xc_episode->tmdb_id,
                            $file_container,
                        );

                        if($info_from == 'tmdb' && $xc_episode->tmdb_id)
                            $Episode->updateFromTMDB($xc_episode->tmdb_id);
                        else {
                            if(!$download_logos)
                                $Episode->setLogo($xc_episode->movie_image);
                            else
                                $Episode->downloadLogo($xc_episode->movie_image);

                            $Episode->release_date = $xc_episode->release_date;
                            $Episode->rate = $xc_episode->rating;
                            $Episode->runtime_seconds = $xc_episode->duration_secs;
                            $Episode->save();

                        }

                    }


                }

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