<?php

namespace ACES2\IPTV\TMDB;

class Episode extends TMDB {

    public $info_language = "";
    public $id = 0;
    public $series_id = 0;
    public $episode_number = 0;
    public $season_number = 0;
    public $name = "";
    public $overview = "";
    public $air_date = "0000-00-00";
    public $runtime_minutes = 0;
    private $still_path = "";
    public $vote_average = 0.0;
    public $vote_count = 0;

    public function __construct(int $episode_number, int $season_number, int $series_id, $info_language = "EN" ) {

        parent::__construct();

        $this->episode_number = $episode_number;
        $this->series_id = $series_id;
        $this->season_number = $season_number;
        $this->info_language = $info_language;

        $info = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/{$this->series_id}/season/{$this->season_number}/episode/{$this->episode_number}?api_key={$this->TMDB_API_KEY}&language=$info_language"),1);

        if(!$info['id'])
            throw new \Exception("Unable to retrive episode from TMDB. 
            Episode $episode_number from season $season_number of series #$series_id 
            not found");

        $this->id = $info['id'];
        $this->name = $info['name'];
        $this->overview = $info['overview'];
        $this->runtime_minutes = $info['runtime'];
        $this->vote_average = $info['vote_average'];
        $this->vote_count = $info['vote_count'];
        $this->still_path = $info['still_path'];

        if($info['air_date'])
            $this->air_date = $info['air_date'];

    }
    public function getSeason():Season {
        return new Season($this->series_id, $this->season_number, $this->info_language);
    }
    public function getSeries():Series {
        return new Series($this->series_id, $this->info_language);
    }
    public function getPoster($quality = "high"):string {
        return match ($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w92/" . $this->still_path,
            'low' => "https://image.tmdb.org/t/p/w185/" . $this->still_path,
            'medium' => "https://image.tmdb.org/t/p/w300/" . $this->still_path,
            default => "https://image.tmdb.org/t/p/original/" . $this->still_path,
        };
    }

}