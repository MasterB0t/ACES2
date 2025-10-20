<?php

namespace ACES2\IPTV\XCAPI;

use ACES2\IPTV\Account;

class Episode {

    public int $id = 0;
    public $episode_num = 0;
    public $title = "";
    public $container_extension = '';
    public $custom_sid = '';
    public int $added = 0;
    public $season = 0;
    public $direct_source = '';
    public int $tmdb_id = 0;
    public $release_date = '';
    public $plot = '';
    public int $duration_secs = 0;
    public $duration;
    public $movie_image = '';
    public int $bitrate = 0;
    public float $rating = 0.0;
    public $cover_big = '';

    private XCAccount $Account;


    public function __construct(array $episode) {
        $this->id = (int)$episode['id'];
        $this->episode_num = (int)$episode['episode_num'];
        $this->title = $episode['title'];
        $this->container_extension = $episode['container_extension'];
        $this->custom_sid = $episode['custom_sid'];
        $this->added = (int)$episode['added'];
        $this->season = (int)$episode['season'];
        $this->direct_source = $episode['direct_source'];
        $this->tmdb_id = (int)$episode['info']['tmdb_id'];
        $this->release_date = $episode['info']['release_date'];
        $this->plot = $episode['info']['plot'];
        $this->duration_secs = (int)$episode['info']['duration_secs'];
        $this->duration = $episode['info']['duration'];
        $this->movie_image = $episode['info']['movie_image'];
        $this->bitrate = (int)$episode['info']['bitrate'];
        $this->rating = (float)$episode['info']['rating'];
        $this->cover_big = $episode['info']['cover_big'];
    }

    public function setAccount(XCAccount $account) {
        $this->Account = $account;
    }

    public function getStreamLink() {
        return "{$this->Account->url}/series/{$this->Account->username}/{$this->Account->password}/{$this->id}.{$this->container_extension}";
    }

    static public function fetchFromPortal(int $episode_id) {

    }

}