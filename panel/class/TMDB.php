<?php

class TMDB {

    public $TMDB_API = '';
    public $DB ;
    public $SERVER_ID = 0;
//    public $LANG = 'en-US';
    public $LANG = 'en';
    public $DO_NOT_DOWNLOAD_LOGOS = false;

    public function __construct($do_not_download_logos=false) {

        require "/home/aces/stream/config.php";

        $this->SERVER_ID = $SERVER_ID;

        $this->DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
        if($this->DB->connect_errno > 0){ throw new Exception("FAIL TO CONNECT TO DATABASE"); }

        $r2=$this->DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
        if(!$this->TMDB_API=$r2->fetch_assoc()['value']) return false; //throw new Exception("NO TMDB API KEY SET.");

        $this->DO_NOT_DOWNLOAD_LOGOS = $do_not_download_logos;

    }

    public function logfile($msg) {
        if(is_array($msg))
            $msg = print_r($msg,1);
        file_put_contents("/home/aces/logs/dir_import.log", "\n[ ".date('Y:m:d H:i:s')." ] $msg \n\n", FILE_APPEND );
    }

    public function fetch_imdb($url,$is_file=false)  {

        if(!$this->TMDB_API)
            return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        if($is_file) {

            $fp = fopen("/home/aces/tmp/" . $is_file, 'w+');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            if($this->SERVER_ID == 1 ) copy("/home/aces/tmp/" . $is_file,"/home/aces/panel/logos/".$is_file);
            else {

                $r_server = $this->DB->query("SELECT * FROM iptv_servers WHERE id = 1 ");
                $server = $r_server->fetch_assoc();

                $md5 = md5_file("/home/aces/tmp/" . $is_file);
                $logo_filename = urlencode("/panel/logos/$is_file");
                $fup = fopen("/home/aces/tmp/" . $is_file, 'r');
                $curl = curl_init();

                //if($server['sport'] && $server['dns'] ) $url = "https://{$server['dns']}:{$server['sport']}/stream/put.php?filename=$logo_filename&checksum=$md5&api_token={$server['api_token']}";
                //else
                    $url = "http://{$server['address']}:{$server['port']}/stream/put.php?filename=$logo_filename&checksum=$md5&api_token={$server['api_token']}";

                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_PUT, 1);
                curl_setopt($curl, CURLOPT_INFILE, $fup );
                curl_setopt($curl, CURLOPT_INFILESIZE, filesize("/home/aces/tmp/" . $is_file));
                curl_exec($curl);
                curl_close($curl);

            }
            unlink("/home/aces/tmp/" . $is_file);
            return $is_file;

        } else {

            $json = json_decode(curl_exec($ch), 1);
            curl_close($ch);
            if(isset($json['status_code'])) return false;
            return $json;

        }

    }

    public function fetch_imdb_movie($movie_imdb_id = 0 ) {
        if(!$movie_imdb_id) return false ;
        return $this->fetch_imdb("https://api.themoviedb.org/3/movie/{$movie_imdb_id}?api_key={$this->TMDB_API}&language={$this->LANG}");
    }

    public function fetch_imdb_series($series_imdb_id = 0 ) {
        if(!$series_imdb_id) return false ;
        return $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$series_imdb_id}?api_key={$this->TMDB_API}&language={$this->LANG}");
    }

    public function search_imdb($name,$type,$year='') {

        $name = str_replace("(","",$name);
        $name = str_replace(")","",$name);
        $name = str_replace("[","",$name);
        $name = str_replace("]","",$name);
        $name = str_replace("."," ",$name);
        $name = str_replace("_"," ",$name);
        $name = str_replace("-"," ",$name);
        $query = urlencode($name);


        $years[] = '';
        if(strlen($year)  == 4  && $year != '0000')  $years = array($year);
        else {
            preg_match_all('!\d+!', $name, $matches);
            if(count($matches[0]) > 0 ) $years = $matches[0];
        }
        foreach($years as $y ) {

            $q_year = '';
            if(strlen($y) == 4 ) {
                $q_year = "&year=$y";
                //$query = urlencode(str_replace($y,"",trim($name)));
                $query = urlencode(explode($y,$name)[0]);
            }

            if ($type == 'movies' || $type == 'movie')
                $results = $this->fetch_imdb("https://api.themoviedb.org/3/search/movie?api_key={$this->TMDB_API}&language={$this->LANG}&query={$query}{$q_year}&page=1&include_adult=false");

            else
                $results = $this->fetch_imdb("https://api.themoviedb.org/3/search/tv?api_key={$this->TMDB_API}&language={$this->LANG}&query={$query}{$q_year}&page=1&include_adult=false");

            if (@is_array($results['results']) && count($results['results']) > 0) {

                return $results['results'];
            }

        }

        return false;

    }

    public function fetch_ondemand($ondemand_id=0) {

        $r=$this->DB->query("SELECT type,tmdb_id FROM iptv_ondemand WHERE id = '$ondemand_id' AND tmdb_id != 0 ");
        if(!$row=$r->fetch_assoc() ) return false;

        if($row['type'] == 'series') {
            $i_ondemand = $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['tmdb_id']}?api_key={$this->TMDB_API}&language={$this->LANG}");
            $i_credits = $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['tmdb_id']}/credits?api_key={$this->TMDB_API}&language={$this->LANG}");
            $trailer = $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['tmdb_id']}/videos?api_key={$this->TMDB_API}&language={$this->LANG}");
            $age_rate = $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['tmdb_id']}/content_ratings?api_key={$this->TMDB_API}");

        } else {
            $i_ondemand = $this->fetch_imdb("https://api.themoviedb.org/3/movie/{$row['tmdb_id']}?api_key={$this->TMDB_API}&language={$this->LANG}");
            $i_credits = $this->fetch_imdb("https://api.themoviedb.org/3/movie/{$row['tmdb_id']}/credits?api_key={$this->TMDB_API}&language={$this->LANG}");
            $trailer = $this->fetch_imdb("https://api.themoviedb.org/3/movie/{$row['tmdb_id']}/videos?api_key={$this->TMDB_API}&language={$this->LANG}");

            $age_rate = $this->fetch_imdb("https://api.themoviedb.org/3/movie/{$row['tmdb_id']}/release_dates?api_key={$this->TMDB_API}");

        }

        $i_ondemand['age_rating'] = '';
        if(is_array($age_rate))
            foreach($age_rate['results'] as $result) {
                if($result['iso_3166_1'] == 'US' || $result['iso_3166_1'] == 'GB' ) {
                    
                    if($row['type'] == 'series')
                        $i_ondemand['age_rating'] = $result['rating'];
                    else
                        $i_ondemand['age_rating'] = $result['release_dates'][0]['certification'];

                    if($i_ondemand['age_rating'])
                        break;

                }
            }

        switch($i_ondemand['age_rating']) {
            case 'U':
            case 'G':
            case 'TV-Y':
                $i_ondemand['age_rating_i'] = 1;
                break;
            case 'TV-Y7':
            case 'TV-Y7-FV':
                $i_ondemand['age_rating_i'] = 7;
                break;
            case 'TV-PG':
            case 'PG':
                $i_ondemand['age_rating_i'] = 9;
                break;
            case '12':
            case '12A':
                $i_ondemand['age_rating_i'] = 12;
                break;
            case 'PG-13':
                $i_ondemand['age_rating_i'] = 13;
                break;
            case 'TV-14':
                $i_ondemand['age_rating_i'] = 14;
                break;
            case '15':
                $i_ondemand['age_rating_i'] = 15;
                break;
            case 'R':
                $i_ondemand['age_rating_i'] = 17;
                break;
            case 'TB-MA':
            case 'NC-17':
            case '18':
            case 'R18':
                $i_ondemand['age_rating_i'] = 18;
                break;

        }


        foreach($trailer['results'] as $videos ) {

            if($videos['site'] == 'YouTube' && $videos['type'] == 'Trailer') {
                $i_ondemand['trailer_link'] = "https://www.youtube.com/watch?v=".$videos['key'];
                break;
            }
        }


        $i_ondemand['cast'] = ''; $i_ondemand['director'] = '';
        foreach($i_credits['cast'] as $cast) {
            if($i_ondemand['cast'] != '' ) $i_ondemand['cast'] .= ", ";
            $i_ondemand['cast'] .= $cast['name'];
        }
        foreach($i_credits['crew'] as $crew) {
            if($crew['job'] == 'Director') {  $i_ondemand['director'] = $crew['name']; break; }
        }

        return $i_ondemand;

    }

    public function fetch_season($season_id=0) {

        $r=$this->DB->query("SELECT s.id,s.number as season_number,o.tmdb_id as series_tmdb_id FROM iptv_series_seasons s
            RIGHT JOIN iptv_ondemand o ON o.id = s.series_id 
            WHERE s.id = '$season_id' AND o.tmdb_id != 0 ");

        if(!$row=$r->fetch_assoc()) return false;

        if(!$row['series_tmdb_id']) return false;

        return $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['series_tmdb_id']}?api_key={$this->TMDB_API}&language={$this->LANG}");

    }

    public function fetch_episode($episode_id=0) {

        $r=$this->DB->query("SELECT e.id,e.number as episode_number,e.season_id,s.number as season_number,o.tmdb_id as series_tmdb_id  FROM iptv_series_season_episodes e
            RIGHT JOIN iptv_series_seasons s ON s.id = e.season_id 
            RIGHT JOIN iptv_ondemand o ON o.id = s.series_id
            WHERE e.id = '$episode_id' AND o.tmdb_id != 0 ");

        if(!$row=$r->fetch_assoc()) return false;

        if(!$i_episode = $this->fetch_imdb("https://api.themoviedb.org/3/tv/{$row['series_tmdb_id']}/season/{$row['season_number']}/episode/{$row['episode_number']}?api_key={$this->TMDB_API}&language={$this->LANG}"))
            return false;

        return $i_episode;

    }

    public function update_ondemand($ondemand_id=0) {

        if(!$i_tmdb = $this->fetch_ondemand($ondemand_id))
            return false;

        $r=$this->DB->query("SELECT id,type FROM iptv_ondemand WHERE id = $ondemand_id") ;
        $row = $r->fetch_assoc();


        if($row['type'] == 'movies') {
            $info['name'] = $this->DB->escape_string($i_tmdb['title']);
            $info['release_date'] = $i_tmdb['release_date'];
        } else {
            $info['name'] = $this->DB->escape_string($i_tmdb['name']);
            $info['release_date'] = $i_tmdb['first_air_date'];
            $info['runtime_seconds'] =  $i_tmdb['episode_run_time'][0]*60;
        }

        $i_tmdb['poster'] = $i_tmdb['poster_path'];

        $info['about'] = $this->DB->escape_string($i_tmdb['overview']);

        if($i_tmdb['runtime'])
            $info['runtime_seconds'] = (int)$i_tmdb['runtime']*60;

        $info['genre1'] = $i_tmdb['genres'][0]['name'];
        $info['genre2'] = @$i_tmdb['genres'][1]['name'];
        $info['genre3'] = @$i_tmdb['genres'][2]['name'];

        $info['rating'] = $i_tmdb['vote_average'];

        $info['year'] = explode("-",  $info['release_date'])[0];

        $info['age_rating'] = $i_tmdb['age_rating'];
        $info['age_rating_i'] = (int)$i_tmdb['age_rating_i'];

        if($i_tmdb['director']) $info['director'] = $this->DB->escape_string($i_tmdb['director']);
        if($i_tmdb['cast']) $info['cast'] = $this->DB->escape_string($i_tmdb['cast']);

        if($i_tmdb['trailer_link']) $info['trailer_link'] = $i_tmdb['trailer_link'];

        if($this->DO_NOT_DOWNLOAD_LOGOS) {
            $info['logo'] = "https://image.tmdb.org/t/p/w500/{$i_tmdb['poster']}";
            $info['back_logo'] = "https://image.tmdb.org/t/p/original/{$i_tmdb['backdrop_path']}";
        } else {

            $info['logo'] = "v{$row['id']}-".time().".jpg";
            $info['back_logo'] = "vb{$row['id']}-".time().".jpg";

            //$this->fetch_imdb("http://image.tmdb.org/t/p/original/{$i_tmdb['poster']}","v{$row['id']}.jpg");
            $this->fetch_imdb("https://image.tmdb.org/t/p/w500/{$i_tmdb['poster']}",$info['logo']);
            //$this->fetch_imdb("http://image.tmdb.org/t/p/w342/{$i_tmdb['poster']}","v{$row['id']}.jpg");
            $this->fetch_imdb("https://image.tmdb.org/t/p/original/{$i_tmdb['backdrop_path']}",$info['back_logo']);


        }


        $info['tmdb_lang'] = $this->LANG;

        foreach ($info as $field => $data)
            $update_sqls[] = "$field = '" . $data . "' ";
        $sql  = implode(', ', $update_sqls);

        $this->DB->query("UPDATE iptv_ondemand SET $sql WHERE id = '$ondemand_id' ");

        return true;

    }

    public function update_season($season_id=0) {

        if(!$i_season=$this->fetch_season($season_id)) return false;

        if($this->DO_NOT_DOWNLOAD_LOGOS) {
            $logo = "https://image.tmdb.org/t/p/original/{$i_season['poster_path']}";
        } else {
            $logo = "vse{$season_id}-".time().".jpg";
            $this->fetch_imdb("https://image.tmdb.org/t/p/original/{$i_season['poster_path']}",$logo);

        }

        $i_season['overview'] = $this->DB->escape_string($i_season['overview']);

        $this->DB->query("UPDATE iptv_series_seasons SET air_date = '{$i_season['air_date']}', overview = '{$i_season['overview']}', 
                               tmdb_id = '{$i_season['id']}', logo = '$logo' WHERE id = '$season_id' ");

        return true;

    }

    public function update_episode($episode_id=0) {

        if(!$i_episode = $this->fetch_episode($episode_id)) return false;

        if($this->DO_NOT_DOWNLOAD_LOGOS) {
            $logo = "https://image.tmdb.org/t/p/original/{$i_episode['still_path']}";
        } else {
            $logo = "vep{$episode_id}-".time().".jpg";
            $this->fetch_imdb("http://image.tmdb.org/t/p/original/{$i_episode['still_path']}",$logo);
        }


        $i_episode['name'] = $this->DB->escape_string($i_episode['name']);
        $i_episode['overview'] = $this->DB->escape_string($i_episode['overview']);

        $this->DB->query("UPDATE iptv_series_season_episodes SET title = '{$i_episode['name']}', about = '{$i_episode['overview']}', 
                                       rate = '{$i_episode['vote_average']}', tmdb_id = '{$i_episode['id']}', 
                                       release_date = '{$i_episode['air_date']}', logo = '$logo'
                                   WHERE id = '$episode_id'  ");

        return true;

    }


    public function update_cover($ondemand_id) {

        if(!$i_tmdb = $this->fetch_ondemand($ondemand_id))
            return false;

        $r=$this->DB->query("SELECT id,type FROM iptv_ondemand WHERE id = $ondemand_id") ;
        $row = $r->fetch_assoc();

        if($this->DO_NOT_DOWNLOAD_LOGOS) {
            $info['logo'] = "https://image.tmdb.org/t/p/w500/{$i_tmdb['poster_path']}";
            //$info['back_logo'] = "https://image.tmdb.org/t/p/original/{$i_tmdb['backdrop_path']}";
        } else {
            $info['logo'] = "v{$row['id']}-".time().".jpg";

            $this->fetch_imdb("http://image.tmdb.org/t/p/w500/{$i_tmdb['poster_path']}",$info['logo']);
            //$this->fetch_imdb("http://image.tmdb.org/t/p/original/{$i_tmdb['backdrop_path']}","vb{$row['id']}.jpg");

            //$info['back_logo'] = "vb{$row['id']}.jpg";
        }

        foreach ($info as $field => $data)
            $update_sqls[] = "$field = '" . $data . "' ";
        $sql  = implode(', ', $update_sqls);

        $this->DB->query("UPDATE iptv_ondemand SET $sql WHERE id = '$ondemand_id' ");

        return true;

    }

    public function getCredits($vod_id, $type='movie') {

        if($type == 'series')
            $url = "https://api.themoviedb.org/3/tv/$vod_id/credits?api_key={$this->TMDB_API}";
        else
            $url = "https://api.themoviedb.org/3/movie/$vod_id/credits?api_key={$this->TMDB_API}";

        return $this->fetch_imdb($url);

    }

    public function getPerson($person_id) {
        $person =  $this->fetch_imdb("https://api.themoviedb.org/3/person/$person_id?api_key={$this->TMDB_API}");
        $vods =  $this->fetch_imdb("https://api.themoviedb.org/3/person/$person_id/combined_credits?api_key={$this->TMDB_API}");
        //$series = $this->fetch_imdb("https://api.themoviedb.org/3/person/$person_id/tv_credits?api_key={$this->TMDB_API}");

        return array ( 'person' => $person, 'vods' => $vods  );
    }


}