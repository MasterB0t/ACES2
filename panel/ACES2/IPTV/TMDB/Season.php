<?php

namespace ACES2\IPTV\TMDB;

class Season extends TMDB {

    public $language_info = '';
    public $id = 0;
    public $series_id = 0;
    /**
     * @var string FORMAT YYYY-MM-DD
     */
    public $air_date = '';
    public $episode_count = 0;
    public $name = '';
    public $overview = '';
    private $poster_path = '';
    public $season_number = 0;
    public $vote_average = 0.0;

    public function __construct(int $series_id, int $season_number , $language_info = "US") {

        parent::__construct();

        $this->series_id = $series_id;
        $this->season_number = $season_number;
        $this->language_info = $language_info;

        $info = json_decode(file_get_contents("https://api.themoviedb.org/3/tv/{$this->series_id}/season/{$this->season_number}?api_key={$this->TMDB_API_KEY}&language={$this->language_info}"), true);

        $this->id = $info['id'];
        $this->episode_count = $info['episode_count'];
        $this->name = $info['name'];
        $this->overview = $info['overview'];
        $this->poster_path = $info['poster_path'];
        $this->air_date = $info['air_date'];
        $this->vote_average = (float)$info['vote_average'];

        return ;

    }

    public function getPosterPath($quality = "high") : string {

        return match ($quality) {
            'very_low' => "https://image.tmdb.org/t/p/w92" . $this->poster_path,
            'low' => "https://image.tmdb.org/t/p/w185" . $this->poster_path,
            'medium' => "https://image.tmdb.org/t/p/w500" . $this->poster_path,
            default => "https://image.tmdb.org/t/p/original" . $this->poster_path,
        };

    }

}