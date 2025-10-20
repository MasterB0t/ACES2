<?php

namespace ACES2\IPTV;

class Video {
    const STATUS_DOWNLOADING = 0;
    CONST STATUS_PROCESSING = 0;
    const STATUS_STOPPED =  -1;
    CONST STATUS_FAIL = -2;
    CONST STATUS_OK = 1;

    public $id = 0;
    public $name = '';
    public $type = 'movies';
    public $is_series = false;
    public $logo = '';
    public $logo_name = '';
    public $back_cover = '';
    public $about = '';
    public $genre1 = '';
    public $genre2 = '';
    public $genre3 = '';
    public $runtime_seconds = 0;
    public $cast = '';
    public $director = '';
    public $release_date = '';
    public $release_year = '0000';
    public $rating = 0;
    public $age_rate = '';
    public $age_rate_int = 0;
    public $youtube_trailer = '';
    public $file = '';
    public $file_id = 0;
    public $file_container = '';
    public $file_transcoding = '';
    public $source_file = '';
    public $server_id = 0;
    public $tmdb_id = 0;
    public $tmdb_lang = 'en';


    public function __construct(int $id = null) {

        $db = new \ACES2\DB;
        if(!is_null($id)){

            $r = $db->query("SELECT o.* FROM iptv_ondemand o WHERE o.id = '$id' ");
            if (!$row=$r->fetch_assoc())
                throw new \Exception("Unable to get vod #$id from database");

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->type = $row['type'];
            $this->is_series = $row['type'] == 'series';
            $this->logo = $row['logo'];
            $this->logo_name = $row['logo_name'];
            $this->back_cover = $row['back_logo'];
            $this->about = $row['about'];
            $this->genre1 = $row['genre1'];
            $this->genre2 = $row['genre2'];
            $this->genre3 = $row['genre3'];
            $this->rating = $row['rating'];
            $this->youtube_trailer = $row['trailer_link'];
            $this->runtime_seconds = $row['runtime_seconds'];

            if($t = strtotime($row['release_date']))
                $this->release_date = $row['release_date'];

            $this->cast = $row['cast'];
            $this->director = $row['director'];
            $this->age_rate = $row['age_rating'];

            $this->tmdb_id = (int)$row['tmdb_id'];
            $this->tmdb_lang = empty($row['tmdb_lang']) ? "EN" : $row['tmdb_lang']; ;

            if($this->type == 'movies') {
                $r_file = $db->query("SELECT id,server_id,container,source_file,transcoding,source_file 
                        FROM iptv_video_files  WHERE movie_id = '$this->id'");

                if($file = $r_file->fetch_assoc()) {
                    $this->file_id = $file['id'];
                    $this->file = $file['source_file'];
                    $this->file_container = $file['container'];
                    $this->file_transcoding = $file['transcoding'];
                    $this->server_id = $file['server_id'];
                    $this->source_file = urldecode($file['source_file']);
                }
            }
            
        }
        return ;

    }
    public function getCategories():array {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT i.category_id as id, c.name  FROM iptv_in_category i
                          RIGHT JOIN iptv_stream_categories c ON i.category_id = c.id
                          WHERE vod_id = '$this->id' ");

        $categories=array();
        while($row=$r->fetch_assoc()) $categories[]=$row;
        return $categories;
    }
    public function getBouquets():array {
        $db = new \ACES2\DB;

        $bouquets=[];
        $r=$db->query("SELECT i.bouquet_id as id, b.name FROM iptv_ondemand_in_bouquet i 
                          RIGHT JOIN iptv_bouquets b ON b.id = i.bouquet_id
                          WHERE i.video_id = $this->id ");
        while($row=$r->fetch_assoc()) $bouquets[] = $row;

        return $bouquets;

    }

    public function set($POST) {

        $db = new \ACES2\DB;

        $this->name = $db->escString($POST['name']);

        if(!(int)$POST['categories'][0])
            throw new \Exception("At least one category must be set");

        $this->type = $POST['type'] == "series" ? "series" : "movies";
        $this->is_series = $POST['type'] == "series";

        $this->genre1 = $db->escString($POST['genre1']);
        $this->genre2 = $db->escString($POST['genre2']);
        $this->genre3 = $db->escString($POST['genre3']);

        $this->youtube_trailer = $POST['youtube_trailer'];
        if($this->youtube_trailer
            && filter_var($this->youtube_trailer, FILTER_VALIDATE_URL) === false )
                throw new \Exception("Invalid youtube trailer");


        if($this->age_rate = $POST['age_rate'])
            switch($this->age_rate) {
                case 'U':
                case 'G':
                case 'TV-Y':
                    $this->age_rate_int = 1;
                    break;
                case 'TV-Y7':
                case 'TV-Y7-FV':
                    $this->age_rate_int = 7;
                    break;
                case 'TV-PG':
                case 'PG':
                    $this->age_rate_int = 9;
                    break;
                case '12':
                case '12A':
                    $this->age_rate_int = 12;
                    break;
                case 'PG-13':
                    $this->age_rate_int = 13;
                    break;
                case 'TV-14':
                    $this->age_rate_int = 14;
                    break;
                case '15':
                    $this->age_rate_int = 15;
                    break;
                case 'R':
                    $this->age_rate_int = 17;
                    break;
                case 'TB-MA':
                case 'NC-17':
                case '18':
                case 'R18':
                    $this->age_rate_int = 18;
                    break;

            }


        $this->about = $db->escString($POST['about']);
        $this->cast = $db->escString($POST['cast']);
        $this->director = $db->escString($POST['director']);
        $this->rating = (int)$POST['rating'];
        $this->release_date = $POST['release_date'];
        if($this->release_date) {
            $this->release_year = date("Y", strtotime($POST['release_date']));
        }

        $this->runtime_seconds = ((int)$POST['runtime_minutes']) ? $POST['runtime_minutes'] * 60 : 0 ;

        $this->tmdb_id = (int)$POST['tmdb_id'];
        if($POST['tmdb_lang'])
            $this->tmdb_lang  = $db->escString($POST['tmdb_lang']);

    }

    public function setTmdbID($tmdb_id = 0) {
        if(!$tmdb_id = (int)$tmdb_id)
            return;
        $this->tmdb_id = $tmdb_id;
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_ondemand SET tmdb_id ='$tmdb_id' WHERE id = '$this->id'");
    }

    public function setBouquets(array $bouquets) {

        $db = new \ACES2\DB;
        $db->query("DELETE FROM 
           iptv_ondemand_in_bouquet WHERE video_id = '$this->id' ");

        foreach($bouquets as $b ) {
            if( (int)$b )
                $db->query("INSERT INTO iptv_ondemand_in_bouquet (bouquet_id,video_id) 
                    VALUES('$b','$this->id') ");
        }
    }
    public function setCategories(array $categories) {

        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_in_category WHERE vod_id = '$this->id'");

        $main_category=0;

        foreach($categories as $c ) {
            if((int)$c) {
                if(!$main_category)
                    $main_category = $c;
                $db->query(" INSERT INTO iptv_in_category (vod_id,category_id) VALUES('$this->id','$c') ");
            }

        }

        $db->query("UPDATE iptv_ondemand SET category_id = '$main_category' WHERE id = '$this->id' ");

    }
    public function downloadLogos(string $cover, string $back_logo) {

        $DB = new \ACES2\DB;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if (false !== $logo_ext = array_search(
                $finfo->buffer(file_get_contents($cover)), array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ), true
            )) {
            $ext = pathinfo($cover, PATHINFO_EXTENSION);
            $logoname = "v$this->id-".time().".$ext";
            copy($cover,"/home/aces/panel/logos/$logoname");
            $DB->query("UPDATE iptv_ondemand SET logo = '$logoname'  where id = $this->id ");
        }

        if (false !== $logo_ext = array_search(
                $finfo->buffer(file_get_contents($back_logo)), array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ), true
            )) {
            $ext = pathinfo($back_logo, PATHINFO_EXTENSION);
            $logoname = "vb$this->id-".time().".$ext";
            copy($cover,"/home/aces/panel/logos/$logoname");
            $DB->query("UPDATE iptv_ondemand SET back_logo = '$logoname'  where id = $this->id ");
        }

    }
    public function setLogos($cover = '', $back_logo = '') {
        $db = new \ACES2\DB();
        $db->query("UPDATE iptv_ondemand SET logo = '$cover', back_logo = '$back_logo' where id = $this->id ");
    }
    public function setFile(array $File) {

        $db = new \ACES2\DB;

        $Server = new \ACES2\IPTV\Server($File['server_id']);
        $this->file_container =  $File['container']  ? $File['container'] : 'mp4';
        $this->file_transcoding = $File['transcoding'] ? $File['transcoding'] : 'copy';
        $this->source_file = $File['file'];

        if(filter_var($this->source_file, FILTER_VALIDATE_URL) === false  && $this->file_transcoding == 'redirect')
            $this->file_transcoding = 'symlink';

        $subtitles=array();
        if(is_array($File['subtitle_files']))
            foreach($File['subtitle_files'] as $i => $v) {
                $lang = $this->escString($File['subtitle_langs'][$i]);
                $subtitles[] = array('file'=>$File['subtitle_files'][$i],'lang'=>$lang);
            }

        $subtitles = count($subtitles) > 0 ? serialize($subtitles) : '';

        $r = $db->query("SELECT id FROM iptv_video_files WHERE movie_id = '$this->id'");
        if($r->num_rows > 0) {
            $db->query("UPDATE iptv_video_files SET subtitles = '$subtitles', transcoding = '$this->file_transcoding', 
                            container = '$this->file_container', server_id = '$Server->id', source_file = '$this->source_file' 
                            WHERE movie_id = '$this->id' ");
        } else {
            $db->query("INSERT INTO iptv_video_files (movie_id,type,transcoding,container,server_id,source_file,subtitles) 
                VALUES($this->id,'movie','$this->file_transcoding','$this->file_container','$Server->id','$this->source_file','$subtitles') ");
            $this->file_id = $db->insert_id;
        }


        $Server->send_action($Server::ACTION_PROCESS_VOD,
            array('file_id'=> $this->file_id));

    }

    public function update($POST) {

        $this->set($POST);

        $main_cat = (int)$POST['categories'][0];

        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_ondemand SET  name = '$this->name', category = '$main_cat', genre1 = '$this->genre1', 
                          genre2 = '$this->genre2', genre3 = '$this->genre3', age_rating = '$this->age_rate', age_rating_i = '$this->age_rate_int', 
                          year = '$this->release_year', release_date = '$this->release_date', runtime_seconds = '$this->runtime_seconds', cast = '$this->cast',
                          director = '$this->director', trailer_link = '$this->youtube_trailer', rating = '$this->rating', about = '$this->about', 
                          tmdb_id = '$this->tmdb_id', tmdb_lang = '$this->tmdb_lang'
                     
                     WHERE id = $this->id ");

        $this->setBouquets($POST['bouquets']);
        $this->setCategories($POST['categories']);

//        $file = @$POST['web_file'] ?: @$POST['file'];
//        if(!$this->is_series && !empty($file) )
//            $this->setFile($file);

        if(!empty($POST['logo']))
            $db->query("UPDATE iptv_ondemand SET logo = '{$POST['logo']}' where id = $this->id ");

        if(!empty($POST['back_logo'])) {
            $db->query("UPDATE iptv_ondemand SET back_logo = '{$POST['back_logo']}' where id = $this->id ");
        }


    }

    public static function add($POST):self {

        $db = new \ACES2\DB;
        $vod = new self(null);
        $vod->set($POST);

        $main_cat = (int)$POST['categories'][0];
        $file = $POST['web_file'] ?: $POST['file'];
        if(!$vod->is_series) {
            if ($file == "/" || empty($file))
                throw new \Exception("Video file is required");
        }

        $db->query("INSERT INTO iptv_ondemand 
                (`name`,`enable`,category_id,type,genre1,genre2,genre3,age_rating,age_rating_i,year,release_date,runtime_seconds,cast,
                 director,trailer_link,rating,about,tmdb_id,tmdb_lang,add_date, last_update) 
        VALUES('$vod->name',1,'$main_cat','$vod->type','$vod->genre1','$vod->genre2','$vod->genre3','$vod->age_rate','$vod->age_rate_int',
               '$vod->release_year','$vod->release_date','$vod->runtime_seconds','$vod->cast','$vod->director','$vod->youtube_trailer'
               ,'$vod->rating','$vod->about','$vod->tmdb_id','$vod->tmdb_lang', NOW(), UNIX_TIMESTAMP() ) ");

        $vod->id = $db->insert_id;

        $vod->setBouquets($POST['bouquets']);
        $vod->setCategories($POST['categories']);

        if($POST['logo'])
            $vod->downloadLogos($POST['logo'], $POST['back_logo']);

        if($vod->is_series)
            $db->query("UPDATE iptv_ondemand SET status = 1 WHERE id = $vod->id");
        else {
            $vod->setFile($POST);
        }

        if(!empty($POST['logo']))
            $db->query("UPDATE iptv_ondemand SET logo = '{$POST['logo']}' where id = $vod->id ");

        if(!empty($POST['back_logo'])) {
            $db->query("UPDATE iptv_ondemand SET back_logo = '{$POST['back_logo']}' where id = $vod->id ");
        }

        return $vod;

    }

    public function remove(bool $remove_source_file = false):bool {

        $db = new \ACES2\DB;

        $db->query("DELETE FROM iptv_ondemand_in_bouquet WHERE video_id = '$this->id' ");
        $db->query("DELETE FROM iptv_in_category WHERE vod_id = '$this->id' ");

        if(is_file("/home/aces/panel/logos/{$this->logo}"))
            unlink("/home/aces/panel/logos/{$this->logo}");

        if(is_file("/home/aces/panel/logos/{$this->back_cover}"))
            unlink("/home/aces/panel/logos/{$this->back_cover}");


        if($this->type == 'series') {

            $r_vid = $db->query(" SELECT e.id as id FROM iptv_video_files v 
                    INNER JOIN iptv_series_seasons s ON s.series_id = $this->id
                    INNER JOIN iptv_series_season_episodes e ON e.id = v.episode_id and e.season_id = s.id ");

            while($row_files = $r_vid->fetch_assoc()) {
                $Episode = new \ACES2\IPTV\Episode($row_files['id']);
                $Episode->remove($remove_source_file);
            }

            $db->query("DELETE FROM iptv_series_seasons WHERE series_id = '$this->id' ");
            $db->query("DELETE FROM iptv_ondemand WHERE id = '$this->id'");

            return true;

        } else {

            $db->query("DELETE FROM iptv_ondemand WHERE id = $this->id ");
            $db->query("DELETE FROM iptv_video_files WHERE id = '$this->file_id' ");
            $db->query("DELETE FROM iptv_video_play_count  WHERE video_file_id = '{$this->file_id}' ");

            $data['file_id'] = $this->file_id;
            //$data['remove_source_file'] = $remove_source_file;
            $data['source_file'] = base64_encode($this->file);

            try {

                $Server = new \ACES2\IPTV\SERVER($this->server_id);
                $Server->send_action('REMOVE_VOD_FILE', $data);

                if( $remove_source_file && !filter_var($this->source_file, FILTER_VALIDATE_URL)) {
                    $connection = \ssh2_connect($Server->address, 22);
                    if(!@\ssh2_auth_password($connection, 'root', $Server->ssh_password ))  {
                        AcesLogE("Fail Connecting to ssh on $Server->name to remove file.");
                    }

                    \ssh2_exec($connection, "rm -f $this->source_file");
                }

            } catch (\Exception $exp) {
                $ignore=1;
            }
            return true;

        }

    }

    public function updateFromTMDB( bool $do_not_download_logo = null ):void {

        if(!$this->tmdb_id)
            throw new \Exception("TMDB video ID is required");

        if($this->is_series)
            $info = new \ACES2\IPTV\TMDB\Series($this->tmdb_id, $this->tmdb_lang);
        else
            $info = new \ACES2\IPTV\TMDB\Movie($this->tmdb_id, $this->tmdb_lang);

        $this->name = $info->name;
        $this->about = $info->overview;
        $this->genre1 = $info->genres[0]['name'];
        $this->genre2 = $info->genres[1]['name'];
        $this->genre3 = $info->genres[2]['name'];
        $this->release_date = $info->release_date;
        $this->release_year = explode("-",$info->release_date)[0];

        $this->runtime_seconds = !$this->is_series ? $info->runtime_minutes * 60 : 0;

        $this->cast = $info->getCastStr();
        $this->director = $info->getDirectorName();
        $this->rating = $info->vote_average;
        $this->age_rate_int = $info->getAgeRate();
        $this->age_rate = $info->getAgeRate();
        $this->youtube_trailer = $info->getYoutubeTrailer();
        $this->logo_name = $info->getLogoTitle('original');

        $this->save();

        if(!is_null($do_not_download_logo)) {
            if($do_not_download_logo) {
                $this->setLogos($info->getPosterPath(), $info->getBackdropPath());
            } else {
                $this->downloadLogos($info->getPosterPath(), $info->getBackdropPath());
            }
        }
        
    }

    private function save() {

        $db = new \ACES2\DB;

        $name = $db->escString($this->name);
        $about = $db->escString($this->about);
        $genre1 = $db->escString($this->genre1);
        $genre2 = $db->escString($this->genre2);
        $genre3 = $db->escString($this->genre3);
        $cast = $db->escString($this->cast);
        $director = $db->escString($this->director);

        $db->query("UPDATE iptv_ondemand  SET name = '$name', about = '$about', genre1 = '$genre1',
            genre2 = '$genre2', genre3 = '$genre3', year = '$this->release_year', 
            release_date = '$this->release_date',runtime_seconds = '$this->runtime_seconds', 
            trailer_link = '$this->youtube_trailer', cast = '$cast', 
            director = '$director', rating = '$this->rating', age_rating = '$this->age_rate', 
            age_rating_i = '$this->age_rate_int', tmdb_id = '$this->tmdb_id', logo_name = '$this->logo_name'
                      WHERE id = '$this->id' ");
    }



}