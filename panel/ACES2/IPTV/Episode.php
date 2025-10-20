<?php

namespace ACES2\IPTV;

class Episode {

    public $id = '';
    public $title = '';
    public $about = '';
    public $episode_number = 0;
    public $season_id = 0;
    public $file_id = '';
    public $server_id = 0;
    public $logo = '';
    public $source_file = '';
    public $release_date = '';
    public $runtime_seconds = 0;
    public $rate = 0.0;
    public $file_container = '';
    public $transcoding = '';
    public $series_id = 0;
    public $tmdb_id = 0;
    public $tmdb_lang = '';

    public function __construct(int $episode_id = null) {

        if(!is_null($episode_id)) {

            $db = new \ACES2\DB;

            $r=$db->query("SELECT e.*,f.id as file_id, f.server_id, f.source_file, f.transcoding, f.container
                        FROM iptv_series_season_episodes e 
                        LEFT JOIN  iptv_video_files f ON f.episode_id = e.id 
                        WHERE e.id = '$episode_id'");

            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to get episode from database.");

            $this->id = $episode_id;
            $this->title = $row['title'];
            $this->file_id = (int)$row['file_id'];
            $this->server_id = (int)$row['server_id'];
            $this->logo =  $row['logo'];
            $this->source_file = urldecode($row['source_file']);
            $this->transcoding = $row['transcoding'];
            $this->file_container = $row['container'];
            $this->episode_number = $row['number'];
            $this->about = $row['about'];
            $this->season_id = $row['season_id'];
            $this->tmdb_id = (int)$row['tmdb_id'];
            $this->release_date = $row['release_date'];
            $this->rate = (float)$row['rate'];
            $this->runtime_seconds = (int)$row['runtime_seconds'];

            $r_season = $db->query("SELECT * FROM iptv_series_seasons  WHERE id = '$this->season_id'");
            $season = $r_season->fetch_assoc();

            $this->season_number = (int)$season['number'];
            $this->series_id = (int)$season['series_id'];

            $r_series = $db->query("SELECT tmdb_lang FROM iptv_ondemand WHERE id = '$this->series_id'");
            $series = $r_series->fetch_assoc();

            $this->tmdb_lang = $series['tmdb_lang'] ? $series['tmdb_lang'] : Settings::get(Settings::TMDB_LANGUAGE);

        }

    }

    public function set($POST) {

        $db = new \ACES2\DB;
        $this->title = $db->escString($POST['title']);
        $this->episode_number = (int)$POST['episode_number'];
        $this->about = $db->escString($POST['about']);
        $this->runtime_seconds = (int)$POST['runtime_minute'] * 60;
        $this->rate = (float)$POST['rate'];

        if(!$season_number = (int)$POST['season_number'])
            throw new \Exception("Select a season for episode.");

        $r=$db->query("SELECT id FROM iptv_series_seasons WHERE number = '$season_number' 
                                     AND series_id = '$this->series_id' ");
        if(!$this->season_id = $r->fetch_assoc()['id']) {
            $db->query("INSERT INTO iptv_series_seasons (series_id, number )  
                VALUES('$this->series_id', '$season_number')");
            $this->season_id = (int)$db->insert_id;
        }

        if(strtotime($POST['release_date']) )
            $this->release_date = $POST['release_date'];

        if((int)$POST['tmdb_id'])
            $this->tmdb_id = $POST['tmdb_id'];

    }
//    public function setFile(array $File) {
//
//        $db = new \ACES2\DB;
//
//        $Server = new \ACES2\IPTV\Server($File['server_id']);
//        $this->file_container =  $File['container']  ? $File['container'] : 'mp4';
//        $this->file_transcoding = $File['transcoding'] ? $File['transcoding'] : 'copy';
//        $this->source_file = $File['file'];
//
//        if(filter_var($this->source_file, FILTER_VALIDATE_URL) === false  && $this->file_transcoding == 'redirect')
//            $this->file_transcoding = 'symlink';
//
//        $subtitles=array();
//        if(is_array($File['subtitle_files']))
//            foreach($File['subtitle_files'] as $i => $v) {
//                $lang = $this->escString($File['subtitle_langs'][$i]);
//                $subtitles[] = array('file'=>$File['subtitle_files'][$i],'lang'=>$lang);
//            }
//
//        $subtitles = count($subtitles) > 0 ? serialize($subtitles) : '';
//
//        $r = $db->query("SELECT id FROM iptv_video_files WHERE episode_id = '$this->id'");
//        if($r->num_rows > 0) {
//            $db->query("UPDATE iptv_video_files SET subtitles = '$subtitles', transcoding = '$this->file_transcoding',
//                            container = '$this->file_container', server_id = '$Server->id', source_file = '$this->source_file'
//                            WHERE episode_id = '$this->id' ");
//        } else {
//            $db->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,container,server_id,source_file,subtitles)
//                VALUES($this->id,'movie','$this->file_transcoding','$this->file_container','$Server->id','$this->source_file','$subtitles') ");
//            $this->file_id = $db->insert_id;
//        }
//
//        $Server->send_action($Server::ACTION_PROCESS_VOD,
//            array('file_id'=> $this->file_id));
//
//    }

    public function setFile(int $server_id, string $container, string $transcoding, string $file,  $subtitles = '' ) {

        $db = new \ACES2\DB;

        $Server = new \ACES2\IPTV\Server($server_id);
        $this->file_container =  $container  ? $container : 'mp4';
        $this->transcoding = $transcoding ? $transcoding : 'copy';
        $this->source_file = $file;

        if(filter_var($this->source_file, FILTER_VALIDATE_URL) === false  && $this->transcoding == 'redirect')
            $this->file_transcoding = 'symlink';

        $subtitles=array();
        if(is_array($subtitles))
            foreach($subtitles as $i => $v) {
                $lang = $this->escString($subtitles['subtitle_langs'][$i]);
                $subtitles[] = array('file'=>$subtitles['subtitle_files'][$i],'lang'=>$lang);
            }

        $subtitles = count($subtitles) > 0 ? serialize($subtitles) : '';

        $r = $db->query("SELECT id FROM iptv_video_files WHERE episode_id = '$this->id'");
        if($r->num_rows > 0) {
            $db->query("UPDATE iptv_video_files SET subtitles = '$subtitles', transcoding = '$this->transcoding', 
                            container = '$this->file_container', server_id = '$Server->id', source_file = '$this->source_file' 
                            WHERE episode_id = '$this->id' ");
        } else {
            $db->query("INSERT INTO iptv_video_files (episode_id,type,transcoding,container,server_id,source_file,subtitles) 
                VALUES($this->id,'movie','$this->transcoding','$this->file_container','$Server->id','$this->source_file','$subtitles') ");
            $this->file_id = $db->insert_id;
        }

        $Server->send_action($Server::ACTION_PROCESS_VOD,
            array('file_id'=> $this->file_id));

    }

    public function downloadLogo(String $logo_url) {
        $ext = pathinfo($logo_url, PATHINFO_EXTENSION);
        $filename = "vse-{$this->id}.".time().".$ext";
        file_put_contents(DOC_ROOT."/logos/$filename" ,file_get_contents("$logo_url"));
        $this->setLogo($filename);
    }

    public function setLogo($logo_url) {

        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_series_season_episodes SET logo = '$logo_url' WHERE id = '$this->id' ");

        //REMOVING OLD LOGO IF WAS LOCATED ON SERVER.
        if(filter_var("$this->logo", FILTER_VALIDATE_URL) === false )
            @unlink(DOC_ROOT."/logos/$this->logo");

        $this->logo = $logo_url;

    }

    public function update($POST) {

        $this->set($POST);

        $this->save();

        if(!empty($POST['cover']))
            $this->setLogo($POST['cover']);

        $file = @$POST['web_file'] ?: @$POST['file'];
        if( !empty($file) ) {
            $this->setFile($POST['series_id'], $POST['container'], $POST['transcoding'], $POST['file']);
        }

        return true;

    }

    function remove(bool $remove_source_file = false ) {

        if(filter_var($this->logo, FILTER_VALIDATE_URL) === FALSE && is_file("/home/aces/panel/logos/{$this->logo}") )
            unlink("/home/aces/panel/logos/{$this->logo}");

        $db = new \ACES2\DB;

        $db->query("DELETE FROM iptv_video_play_count  WHERE video_file_id = '{$this->file_id}' ");
        $db->query("DELETE FROM iptv_series_season_episodes WHERE id = '$this->id' ");

        $data['file_id'] = $this->file_id;
        //$data['remove_source_file'] = $remove_source_file;

        try {
            $Server = new \ACES2\IPTV\SERVER($this->server_id);
            $Server->send_action(\ACES2\IPTV\SERVER::ACTION_REMOVE_VOD, $data);

            if( $remove_source_file && !filter_var($this->source_file, FILTER_VALIDATE_URL)) {
                $connection = \ssh2_connect($Server->address, 22);
                if(!@\ssh2_auth_password($connection, 'root', $Server->ssh_password ))  {
                    AcesLogE("Fail Connecting to ssh on server $Server->name to remove file.");
                }

                \ssh2_exec($connection, "rm -f $this->source_file");
            }


        } catch (\Exception $exp) {
            AcesLogE("Error removing episode. ".$exp->getMessage());
        }


        $db->query("DELETE FROM iptv_video_files WHERE id = $this->file_id  ");

        //$this->adminAddLog("Remove Episode #{$row['number']} From Series {$row['series']}, Season #{$row['season_number']} ");

        return true;
    }

    public static function add($POST):self {
        $db = new \ACES2\DB;
        $episode = new self();

        if(!$episode->series_id = (int)$POST['series_id'])
            throw new \Exception("Select a series to add episode to.");

        if(empty($POST['file']) || $POST['file'] == '/')
            throw new \Exception("File is required.");

        $episode->set($POST);

        $db->query("INSERT INTO iptv_series_season_episodes (season_id, number, title, about,
                                         runtime_seconds, logo, rate, release_date, server_id, tmdb_id) 
                                         
                          VALUES('$episode->season_id','$episode->episode_number', '$episode->title', '$episode->about',
                                 '$episode->runtime_seconds', '$episode->logo', '$episode->rate', '$episode->release_date', 
                                 $episode->server_id, '$episode->tmdb_id') ");

        $episode->id = $db->insert_id;

        $db->query("UPDATE iptv_ondemand  SET last_update = UNIX_TIMESTAMP() WHERE id = '$episode->series_id' ");

        //$episode->setFile($POST);
        $episode->setFile($POST['series_id'], $POST['container'], $POST['transcoding'], $POST['file'] );

        return $episode;
    }

    public static function add2(
        string $title, int $episode_num, int $series_id, int $season_number,
        $release_date,   $source_file,
        $transcoding, int $server_id, $about = '', int $tmdb_id = 0,
        $file_container = 'mp4', $rate = 0.0

    ):self {

        $db = new \ACES2\DB;
        $title = $db->escString($title);
        $about = $db->escString($about);
        $Series = new Video($series_id);
        if(!$Series->is_series)
            throw new \Exception("Video #$series_id is not a series.");

        $Server = new \ACES2\IPTV\SERVER($server_id);

        $r_season = $db->query("SELECT id FROM iptv_series_seasons WHERE series_id = '$series_id' AND 
                                         number = '$season_number' ");
        if(!$season_id = $r_season->fetch_assoc()['id']){
            $db->query("INSERT INTO iptv_series_seasons (series_id, number) 
                VALUES ('$series_id', '$season_number')");
            $season_id = $db->insert_id;

        }

        $db->query("INSERT INTO iptv_series_season_episodes (season_id, number,  title, about,  rate, 
                                         release_date, server_id, tmdb_id)
                   VALUES('$season_id', '$episode_num', '$title',  '$about',  '$rate',
                          '$release_date', '$server_id', '$tmdb_id' )");

        $episode_id = $db->insert_id;

        $Episode = new self($episode_id);

        $Episode->setFile($server_id, $file_container, $transcoding, $source_file);

        return $Episode;

    }

    public function save() {

        $db = new \ACES2\DB;

        $title = $db->escString($this->title);
        $about = $db->escString($this->about);

        $db->query("UPDATE iptv_series_season_episodes SET title = '$title', number = '$this->episode_number',
        about = '$about', release_date = '$this->release_date', runtime_seconds = '$this->runtime_seconds',
        rate = '$this->rate', tmdb_id = '$this->tmdb_id' WHERE id = '$this->id'");

        return true;

    }

    /**
     * Update episode info from TMDB.
     * @param bool|null $download_logo if null logo will not be updated from TMDB.
     * @return void
     * @throws \Exception
     */
    public function updateFromTMDB(bool $download_logo = null) {

        $series = new Video($this->series_id);

        if( !$this->episode_number || !$this->season_number || !$series->tmdb_id )
            throw new \Exception("Unable to update info on episode #$this->id from TMDB. 
            Episode number and season number and Series tmdb id is required. ");

        $Episode = new \ACES2\IPTV\TMDB\Episode($this->episode_number, $this->season_number, $series->tmdb_id, $this->tmdb_lang);
        $this->title = $Episode->name;
        $this->runtime_seconds = $Episode->runtime_minutes * 60;
        $this->about = $Episode->overview;
        $this->rate = $Episode->vote_average;
        $this->release_date = $Episode->air_date;
        $this->tmdb_id = $Episode->id;

        $this->save();

        //IF IS NULL WILL BE IGNORED.
        if(!is_null($download_logo)) {
            if($download_logo) {
                $this->downloadLogo($Episode->getPoster('high'));
            } else {
                $this->setLogo($Episode->getPoster('high'));
            }
        }

        return ;
    }

}