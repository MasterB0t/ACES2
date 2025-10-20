<?php

namespace ACES2\IPTV\XCAPI;

use ACES2\DB;
use ACES2\IPTV\Video;

class Movie {

    public int $num = 0;
    public int $id = 0;
    public  $name = '';
    public  $title = '';
    public  $stream_type = '';
    public int $stream_id = 0;
    public float $rating = 0.0;
    public float $rating_5based = 0.0;
    public int $added = 0;
    public  $plot = '';
    public  $cast = '';
    public  $actors = '';
    public  $director = '';
    public  $genre = '';
    public  $release_date = '';
    public int $year = 0;
    public  $youtube_trailer = '';
    //public int $episode_run_time = 0;
    public int $category_id = 0;
    public  $age = '';
    public  $mpaa_rating = '';
    public  $country = '';
    public $category_ids = [];
    public string $container_extension = '';
    public  $custom_sid = '';
    public  $direct_source = '';
    public $kinopoisk_url = '';
    public int $tmdb_id =0;
    public array $backdrop_path = [];
    public $movie_image = '';
    private $category_name = '';

    private XCAccount $Account;

    public function __construct(array $vod ) {
        $this->num = (int)$vod['num'];
        $this->id = (int)$vod['stream_id'];
        $this->name = $vod['name'];
        $this->title = $vod['title'];
        $this->stream_type = 'movie';
        $this->stream_id = $vod['stream_id'];
        $this->category_id = $vod['category_id'];
        $this->category_ids = (array)$vod['category_ids'];
        $this->container_extension = $vod['container_extension'];
        $this->custom_sid = $vod['custom_sid'];
        $this->direct_source = $vod['direct_source'];
    }

    private function setAccount(XCAccount $Account) {
        $this->Account = $Account;
    }

    public function getCategoryName() {

        if($this->category_name)
            return $this->category_name;

        $vod_cats = json_decode(file_get_contents(
            "{$this->Account->url}/player_api.php?username={$this->Account->username}&password={$this->Account->password}&action=get_vod_categories"),true);

        foreach($vod_cats as $cat) {
            if($cat['category_id'] == $this->category_id)
                $this->category_name = $cat['category_name'];
        }

        return $this->category_name;

    }

    public function setInfo($info) {
        $this->rating = (float)$info['rating'];
        $this->rating_5based = (float)$info['rating_5based'];
        $this->added = (int)$info['added'];
        $this->plot = $info['plot'];
        $this->cast = $info['cast'];
        $this->actors = $info['actors'];
        $this->director = $info['director'];
        $this->genre = $info['genre'];
        $this->release_date = $info['release_date'];
        $this->youtube_trailer = $info['youtube_trailer'];
        $this->kinopoisk_url = $info['kinopoisk_url1'];
        $this->age = $info['age'];
        $this->mpaa_rating = $info['mpaa_rating'];
        $this->country = $info['country'];
        $this->backdrop_path = $info['backdrop_path'];
        $this->movie_image = $info['movie_image'];
        $this->tmdb_id = (int)$info['tmdb_id'];
        //$this->episode_run_time = $info['episode_run_time'];
    }

    public function getStreamLink() {
        return "{$this->Account->url}/movie/{$this->Account->username}/{$this->Account->password}/{$this->id}.{$this->container_extension}";
    }

    static public function fetchFromPortal(int $account_db_id, int $movie_id):self {

        $Account = new XCAccount($account_db_id);
        $info = json_decode(file_get_contents("{$Account->url}/player_api.php?username={$Account->username}&password={$Account->password}&action=get_vod_info&vod_id={$movie_id}"),true);

        $vod = new self($info['movie_data']);
        $vod->setInfo($info['info']);
        $vod->setAccount($Account);
        return $vod;
    }


    public function add($categories,$bouquets,$transcoding,$server_id, $do_not_download_logo = false):Video {

        $db = new \ACES2\DB();

        $youtube = $this->youtube_trailer ?  'https://www.youtube.com/watch?v=' .$this->youtube_trailer : '';

        $post = array(
            'name' => $this->name,
            'type' => 'movies',
            'youtube_trailer' => $youtube,
            'genre1' => $this->genre,
            'genre2' => '',
            'genre3' => '',
            'about' => $this->plot,
            'director' => $this->director,
            'rating' => $this->rating,
            'release_date' => $this->release_date,
            'tmdb_id' => (int)$this->tmdb_id,
            'web_file' => '',
            'categories' => $categories,
            'bouquets' => $bouquets,
            'container' => $this->container_extension,
            'transcoding' => $transcoding,
            'server_id' => $server_id,
            'file' => $this->getStreamLink()
        );

        $vod = \ACES2\IPTV\Video::add($post);
        if($do_not_download_logo)
            $vod->setLogos($this->movie_image, $this->backdrop_path[0]);
        else
            $vod->downloadLogos($this->movie_image, $this->backdrop_path[0]);



        return $vod;

    }

}