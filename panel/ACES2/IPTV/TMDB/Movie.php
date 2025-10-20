<?php

namespace ACES2\IPTV\TMDB;

class Movie extends TMDB {

    public $id = 0;
    public $language_info = "EN";
    public $imdb_id = "";
    public $original_title = "";
    public $original_language = "";
    public $title = "";
    public $name = "";
    public $runtime_minutes = 0;
    //YYYY-MM-DD
    public $release_date = "";
    public $tagline = "";
    public $overview = "";
    public $genres = [];
    private $backdrop_path = "";
    private $poster_path = "";
    public $vote_average = 0.0;
    public $vote_count = 0;
    private $youtube_trailer = '';
    private $_credits;
    private $director_name = "";
    private $cast_str = "";
    private $age_rate = 0;
    private $age_certification = '';


    public function __construct(int $id , string $lang = "EN")  {

        parent::__construct();

        $this->language_info = $lang;

        $json = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/$id?api_key={$this->TMDB_API_KEY}&language={$lang}"),1);

        $this->id = $id;
        $this->imdb_id = $json['imdb_id'];
        $this->original_title = $json['original_title'];
        $this->original_language = $json['original_language'];
        $this->title = $json['title'];
        $this->name = $this->title;
        $this->runtime_minutes = $json['runtime'];
        $this->release_date = $json['release_date'];
        $this->tagline = $json['tagline'];
        $this->overview = $json['overview'];
        $this->genres = $json['genres'];
        $this->backdrop_path = $json['backdrop_path'];
        $this->poster_path = $json['poster_path'];
        $this->vote_average = $json['vote_average'];
        $this->vote_count = $json['vote_count'];


        $this->_credits = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/$id/credits?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"),1);
        foreach($this->_credits['cast'] as $cast) {
            if($this->cast_str != '' ) $this->cast_str .= ", ";
            $this->cast_str .= $cast['name'];
        }

        foreach($this->_credits['crew'] as $crew) {
            if($crew['job'] == 'Director') {  $this->director_name = $crew['name']; break; }
        }


        return;
    }

    public function getAgeCertification($from_country = "US"):string {

        if(!empty($this->age_certification))
            return $this->age_certification;

        $age_rate = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$this->id}/release_dates?api_key={$this->TMDB_API_KEY}"),1);

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

    public function getYoutubeTrailer():string {

        $trailer = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/$this->id/videos?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"),1);
        foreach($trailer['results'] as $trailer) {
            if($trailer['site'] == 'YouTube') {
                $this->youtube_trailer = "https://www.youtube.com/watch?v=".$trailer['key'];
                break;
            }
        }

        return $this->youtube_trailer;
    }

    public function getCastStr() : string {

        if($this->cast_str != "")
            return $this->cast_str;

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

    public function getLogoTitle($quality = "high", $language = 'en' ):string {

        $images = json_decode(
            file_get_contents("https://api.themoviedb.org/3/movie/$this->id/images?api_key=$this->TMDB_API_KEY&language=en"), 1);

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

    public function getBackdropPath( $quality = "high" ):string {

        return match ($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w300/" . $this->backdrop_path,
            'low' => "https://image.tmdb.org/t/p/w780/" . $this->backdrop_path,
            'medium' => "https://image.tmdb.org/t/p/w1280/" . $this->backdrop_path,
            default => "https://image.tmdb.org/t/p/original/" . $this->backdrop_path,
        };
    }

    static public function search($movie_name, int $movie_year = null, $info_language = "EN" ) {

        $db = new \ACES2\DB();
        $r=$db->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
        $API_KEY = $r->fetch_assoc()['value'];
        if(empty($API_KEY))
            throw new \Exception("Unable to get from TMDB. API key is not set");

        $name = str_replace("(","", trim($movie_name));
        $name = str_replace(")","",$name);
        $name = str_replace("[","",$name);
        $name = str_replace("]","",$name);
        $name = str_replace("."," ",$name);
        $name = str_replace("_"," ",$name);
        $name = str_replace("-"," ",$name);

        if($movie_year) {
            $name = str_replace($movie_year, "", $name);
            $q_year = "&year=$movie_year";
        }

        $query = urlencode($name);


        $info = json_decode(
            file_get_contents("https://api.themoviedb.org/3/search/movie?api_key={$API_KEY}&language={$info_language}&query={$query}{$q_year}&page=1&include_adult=false")
        ,1 );

        return $info['results'];

    }

}