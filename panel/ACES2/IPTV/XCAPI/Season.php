<?php

namespace ACES2\IPTV\XCAPI;

class Season {
    public int $id = 0;
    public $air_date;
    public int $episode_count = 0;
    public $name;
    public $overview;
    public $season_number;
    public $cover;
    public $cover_big;

    public function __construct($info) {
        $this->id = (int)$info["id"];
        $this->air_date = $info["air_date"];
        $this->episode_count = (int)$info["episode_count"];
        $this->name = $info["name"];
        $this->overview = $info["overview"];
        $this->season_number = (int)$info["season_number"];
        $this->cover = $info["cover"];
        $this->cover_big = $info["cover_big"];
    }

}