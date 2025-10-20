<?php
namespace ACES2\IPTV;

class Season {

    public $id = 0;
    public $series_id = 0;
    public $logo = '';
    public $number = '';
    public $overview = '';
    public $air_date = '';
    public $tmdb_id = 0;
    public $series_tmdb_id = 0;
    public $tmdb_lang = '';

    public function __construct(int $id  ) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT s.*, v.tmdb_lang, v.tmdb_id as series_tmdb_id
            FROM iptv_series_seasons s
            RIGHT JOIN iptv_ondemand v ON v.id = s.series_id
            WHERE s.id = '$id'  ");

        if(!$row=$r->fetch_assoc())
            throw new \Exception("Could not fetch season #$id from database.");

        $this->id = $id;
        $this->series_id = (int)$row['series_id'];
        $this->number = (int)$row['number'];
        $this->logo = $row['logo'];
        $this->air_date = $row['air_date'];
        $this->tmdb_id = (int)$row['tmdb_id'];
        $this->series_tmdb_id = $row['series_tmdb_id'];
        $this->tmdb_lang = $row['tmdb_lang'] ? $row['tmdb_lang'] : Settings::get(Settings::TMDB_LANGUAGE);
        $this->overview = $row['overview'];

    }

    public static function add(
        int $series_id, int $number,  $air_date = '',   $overview = '',  $tmdb_id = 0  ):self {

        $series = new \ACES2\IPTV\Video($series_id);
        if(!$series->is_series)
            throw new \Exception("Video #$series_id is not a series.");

        $db = new \ACES2\DB;
        $overview = $db->escString($overview);

        $r=$db->query("SELECT id FROM iptv_series_seasons WHERE series_id = '$series_id' AND number = '$number'");
        if($season_id = $r->fetch_assoc()['id'])
            return new self($season_id);

        $db->query("INSERT INTO iptv_series_seasons (series_id, number, air_date, overview, tmdb_id )
            VALUES('$series_id', '$number', '$air_date', '$overview', '$tmdb_id')");

        return new self($db->insert_id);

    }

    public function downloadLogo($logo_url) {
        $ext = pathinfo($logo_url, PATHINFO_EXTENSION);
        $filename = "vss-{$this->id}.".time().".$ext";
        file_put_contents(DOC_ROOT."/logos/$filename" ,file_get_contents("$logo_url"));
        $this->setLogo($filename);
    }

    public function setLogo(string $logo_url) {

        if(filter_var($this->logo, FILTER_VALIDATE_URL) === false) {
            unlink($this->logo);
        }

        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_series_seasons SET logo = '$logo_url' WHERE id = $this->id");

        $this->logo = $logo_url;

    }

    function getEpisodes(): array {
        $db = new \ACES2\DB;

        $episodes = [];

        $r=$db->query("SELECT id FROM iptv_series_season_episodes WHERE season_id = '$this->id'");
        while($row=$r->fetch_assoc()) {
            $episodes[] = new Episode($row['id']);
        }
        return $episodes;
    }

    public function updateFromTMDB(bool $download_logo = null) {

        $Season = new TMDB\Season($this->series_tmdb_id, $this->number, $this->tmdb_lang);

        $this->air_date = $Season->air_date;
        $this->overview = $Season->overview;
        $this->logo = $Season->getPosterPath();
        $this->save();

    }

    public function save() {

        $db = new \ACES2\DB;

        $overview = $db->escString($this->overview);

        $db->query("UPDATE iptv_series_seasons SET air_date = '$this->air_date', overview = '$overview', 
                               logo = '$this->logo' 
                   WHERE id = $this->id ");

    }

}