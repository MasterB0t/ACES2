<?php

namespace ACES2\IPTV\XCAPI;

class Series {

    public int $id = 0;
    public $name;
    public $title;
    public int $year = 0;
    public $cover = '';
    public $plot;
    public $cast;
    public $director;
    public $genre;
    public $release_date;
    public int $last_modified=0;
    public int $rating = 0;
    public float $rating_5based = 0.0;
    public $backdrop_path = [];
    public $youtube_trailer = '';
    public int $episode_run_time =0;
    public int $category_id = 0;
    private $category_name = '';
    public $category_ids = [];
    public $seasons = [];
    public $episodes = [];

    private XCAccount $Account;

    public function __construct($info) {
        $this->id = (int)$info['id'];
        $this->name = $info['name'];
        $this->title = $info['title'];
        $this->year = (int)$info['year'];
        $this->cover = $info['cover'];
        $this->plot = $info['plot'];
        $this->cast = $info['cast'];
        $this->director = $info['director'];
        $this->genre = $info['genre'];
        $this->release_date = $info['release_date'];
        $this->last_modified = (int)$info['last_modified'];
        $this->rating = (int)$info['rating'];
        $this->rating_5based = (float)$info['rating_5based'];
        $this->backdrop_path = (array)$info['backdrop_path'];
        $this->youtube_trailer = $info['youtube_trailer'];
        $this->episode_run_time = (int)$info['episode_run_time'];
        $this->category_id = (int)$info['category_id'];
        $this->category_ids = (array)$info['category_ids'];
    }

    public function setAccount(XCAccount $Account) {
        $this->Account = $Account;
    }

    private function setSeasons($seasons) {
        $this->seasons = $seasons;
    }

    private function setEpisodes($episodes) {
        $this->episodes = $episodes;
    }

    public static function fetchFromPortal(int $account_db_id, int $series_id):self {

        $Account = new XCAccount($account_db_id);

        $info = json_decode(file_get_contents("{$Account->url}/player_api.php?username={$Account->username}&password={$Account->password}&action=get_series_info&series_id={$series_id}"),true);
        $info['info']['id'] = $series_id;
        $series = new Series($info['info']);
        $series->setAccount($Account);
        $series->setSeasons((array)$info['seasons']);
        $series->setEpisodes((array)$info['episodes']);
        return $series;

    }

    public function getCategoryName() {

        if($this->category_name)
            return $this->category_name;

        $vod_cats = json_decode(file_get_contents(
            "{$this->Account->url}/player_api.php?username={$this->Account->username}&password={$this->Account->password}&action=get_series_categories"),true);

        foreach($vod_cats as $cat) {
            if($cat['category_id'] == $this->category_id)
                $this->category_name = $cat['category_name'];
        }

        return $this->category_name;

    }

    public function getSeason(int $season_number):season {
        $Season = null;
        foreach($this->seasons as $i => $season) {
            if($season['season_number'] == $season_number)
                return new Season($this->seasons[$i]);
        }
        throw new \Exception("Season {$season_number} do not exist in this series.");
    }
    public function getEpisode(int $season_number , int $episode_number):episode {
        $episode = $this->episodes[$season_number][$episode_number];
        return new Episode($episode);
    }

    public function getEpisodeByID(int $episode_id ) {
        foreach($this->episodes as $season_n => $episodes) {
            foreach($episodes as $i => $episode)
                if($episode['id'] == $episode_id)
                    return new Episode($episode);
        }
        return null;
    }

    public function add($categories, $bouquets, $server_id, $transcoding, $do_not_download_logo = false ) {

        $youtube = $this->youtube_trailer ?  'https://www.youtube.com/watch?v=' .$this->youtube_trailer : '';
        $backlogo = $this->backdrop_path[0];

        $genres = explode(',',$this->genre);

        $genre1 = $genres[0];
        $genre2 = $genres[1];
        $genre3 = $genres[2];

        $post = array(
            'name' => $this->name,
            'type' => 'series',
            'youtube_trailer' => $youtube,
            'genre1' => $this->genre,
            'genre2' => '',
            'genre3' => '',
            'about' => $this->plot,
            'director' => $this->director,
            'rating' => $this->rating,
            'release_date' => $this->release_date,
            'tmdb_id' => 0,
            'web_file' => '',
            'categories' => $categories,
            'bouquets' => $bouquets,
        );

        $vod = \ACES2\IPTV\Video::add($post);

        if($do_not_download_logo)
            $vod->setLogos($this->cover, $this->backdrop_path[0]);
        else
            $vod->downloadLogos($this->cover, $this->backdrop_path[0]);

        foreach($this->episodes as $seasons ) {
            foreach($this->seasons as $episode ) {
                $Episode = new Episode($episode);

            }
        }

    }


}