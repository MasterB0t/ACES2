<?php

namespace ACES2\IPTV\TMDB;

class Series extends TMDB {

    public $id = 0;

    public $language_info = "";
    public $name = "";
    public $overview = "";
    private $poster_path = "";
    private $backdrop_path = "";
    public $tagline = "";
    public $status = "";
    public $genres = [];
    public $in_production = false;
    public $imdb_id = "";
    public $original_language = "";
    public $original_name = "";
    public $number_of_episodes = 0;
    public $number_of_seasons = 0;
    /**
     * @var mixed|string FORMAT YYYY-MM-DD
     */
    public $first_air_date = "";
    /**
     * @var mixed|string FORMAT YYYY-MM-DD
     */
    public $last_air_date = "";
    /**
     * @var string FORMAT YYYY-MM-DD
     */
    public $release_date = "";

    public $vote_average = 0.0;
    public $vote_count = 0;
    private $youtube_trailer = "";

    private $age_rate = 0;
    private $age_certification = '';
    private $_credits;
    private $cast_str = '';
    private $director_name  = "";

    public function __construct(int $id, $language_info = "EN") {

        parent::__construct();

        $this->id = $id;
        $this->language_info = $language_info;

        $json = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$id?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"),1);

        $this->name = $json['name'];
        $this->overview = $json['overview'];
        $this->poster_path = $json['poster_path'];
        $this->backdrop_path = $json['backdrop_path'];
        $this->tagline = $json['tagline'];
        $this->status = $json['status'];
        $this->genres = $json['genres'];
        $this->in_production = $json['in_production'];
        $this->imdb_id = (int)$json['imdb_id'];
        $this->original_language = $json['original_language'];
        $this->original_name = $json['original_name'];
        $this->number_of_episodes = (int)$json['number_of_episodes'];
        $this->number_of_seasons = (int)$json['number_of_seasons'];
        $this->first_air_date = $json['first_air_date'];
        $this->last_air_date = $json['last_air_date'];
        $this->release_date = $json['first_air_date'];
        $this->vote_average = $json['vote_average'];
        $this->vote_count = (int)$json['vote_count'];

        return;

    }

    public function getYoutubeTrailer():string {

        $trailer = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$this->id/videos?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"),1);
        foreach($trailer['results'] as $trailer) {
            if($trailer['site'] == 'YouTube') {
                $this->youtube_trailer = "https://www.youtube.com/watch?v=".$trailer['key'];
                break;
            }
        }

        return $this->youtube_trailer;
    }
    public function getAgeCertification($from_country = "US"):string {

        if(empty($this->age_certification))
            return $this->age_certification;

        $age_rate = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/{$this->id}/release_dates?api_key={$this->TMDB_API_KEY}"),1);

        foreach($age_rate['results'] as $result) {

            if($result['iso_3166_1'] == strtoupper($from_country) ) {

                $this->age_certification = $result['release_dates'][0]['certification'];
                break;

            }
        }

        return $this->age_certification ;

    }
    public function getAgeRate() : int {

        if(empty($this->age_certification))
            $this->getAgeCertification();

        switch($this->age_certification) {
            case 'U':
            case 'G':
            case 'TV-Y':
                $this->age_rate = 1;
                break;
            case 'TV-Y7':
            case 'TV-Y7-FV':
                $this->age_rate = 7;
                break;
            case 'TV-PG':
            case 'PG':
                $this->age_rate = 9;
                break;
            case '12':
            case '12A':
                $this->age_rate = 12;
                break;
            case 'PG-13':
                $this->age_rate = 13;
                break;
            case 'TV-14':
                $this->age_rate = 14;
                break;
            case '15':
                $this->age_rate = 15;
                break;
            case 'R':
                $this->age_rate = 17;
                break;
            case 'TB-MA':
            case 'NC-17':
            case '18':
            case 'R18':
                $this->age_rate = 18;
                break;

        }

        return $this->age_rate;

    }
    public function getCastStr() : string {

        if($this->cast_str != "")
            return $this->cast_str;

        if(is_array($this->_credits['cast']))
        foreach($this->_credits['cast'] as $cast) {
            if($this->cast_str != '' ) $this->cast_str .= ", ";
            $this->cast_str .= $cast['name'];
        }
        return $this->cast_str;
    }
    public function getDirectorName() : string {
        if( $this->director_name != "" )
            return $this->director_name;

        if($this->_credits == null)
            $this->getCredits();

        foreach($this->_credits['crew'] as $crew) {
            if($crew['job'] == 'Director') {  $this->director_name = $crew['name']; break; }
        }

        return $this->director_name;
    }
    public function getPosterPath( $quality = "high" ):string {

        return match ($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w92/" . $this->poster_path,
            'low' => "https://image.tmdb.org/t/p/w185/" . $this->poster_path,
            'medium' => "https://image.tmdb.org/t/p/w500/" . $this->poster_path,
            default => "https://image.tmdb.org/t/p/original/" . $this->poster_path,
        };

    }
    public function getBackdropPath( $quality = "high" ):string {

        return match ($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w300/" . $this->poster_path,
            'low' => "https://image.tmdb.org/t/p/w780/" . $this->poster_path,
            'medium' => "https://image.tmdb.org/t/p/w1280/" . $this->poster_path,
            default => "https://image.tmdb.org/t/p/original/" . $this->poster_path,
        };
    }

    public function getLogoTitle($quality = "high", $language = 'en' ):string {

        $images = json_decode(
            file_get_contents("https://api.themoviedb.org/3/tv/$this->id/images?api_key=$this->TMDB_API_KEY&language=en"), 1);

        $path = match($quality) {
            'very_low' =>  $images['logos'][1]['file_path'],
            'low' =>  $images['logos'][2]['file_path'],
            'medium' => $images['logos'][4]['file_path'],
            'high' => $images['logos'][5]['file_path'],
            default => $images['logos'][5]['file_path'],
        };

        //MAKE SURE THERE IS LOGO FOR THE MOVIE
        if(!$path)
            return '';

        return match($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w92/" . $path,
            'low' => "https://image.tmdb.org/t/p/w185/" . $path,
            'medium' => "https://image.tmdb.org/t/p/w300/" . $path,
            'high' => "https://image.tmdb.org/t/p/w500/" . $path,
            default => "https://image.tmdb.org/t/p/original/".  $path,
        };

    }

    private function getCredits() {
        $this->_credits = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/$this->id/credits?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"),1);
    }

    public static function search($series_name, int $series_year = null, string $info_language = "EN" ) {

        $db = new \ACES2\DB();
        $r=$db->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
        $API_KEY = $r->fetch_assoc()['value'];
        if(empty($API_KEY))
            throw new \Exception("Unable to get from TMDB. API key is not set");

        $name = str_replace("(","", trim($series_name));
        $name = str_replace(")","",$name);
        $name = str_replace("[","",$name);
        $name = str_replace("]","",$name);
        $name = str_replace("."," ",$name);
        $name = str_replace("_"," ",$name);
        $name = str_replace("-"," ",$name);

        $queries[] = "&query=" . urlencode($name) . "&year=$series_year&page=1&include_adult=false";

        if($series_year) {
            //QUERY WITHOUT REMOVING THE YEAR FROM NAME.
            $queries[] = "&query=" . urlencode($name) . "&year=$series_year&page=1&include_adult=false";

            $name = str_replace($series_year, "", $name);
            $queries[] = "&query=" . urlencode($name) . "&year=$series_year&page=1&include_adult=false";

        } else {

            //NORMAL QUERY
            $queries[] = "&query=" . urlencode($name) . "&page=1&include_adult=false";

            //MATCH ALL POSSIBLE YEARS IN NAME.
            preg_match_all('!\d+!', $name, $matches);
            foreach($matches[0] as $year ) {
                //ADDING TWO QUERIES TO SEARCH. ONE WITH YEAR ON NAME AND ONE WITHOUT.
                $o_name = str_replace("$year", "", $name);
                $queries[] = "&query=" . urlencode($o_name) . "&year=$year&page=1&include_adult=false";
                $queries[] = "&query=" . urlencode($name) . "&year=$year&page=1&include_adult=false";
            }
        }

        foreach($queries as $query) {

            $info = json_decode(
                file_get_contents("https://api.themoviedb.org/3/search/tv?api_key={$API_KEY}&language={$info_language}&query=$query")
                ,1 );

            //THE FIRST RESULT SHOULD BE THE ONE????
            if(count($info["results"])>0)
                return $info["results"];
        }

        return [];

    }

}