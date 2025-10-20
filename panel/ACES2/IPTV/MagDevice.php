<?php

namespace ACES2\IPTV;

class MagDevice {

    private $id;
    public $mac_address = '';
    public $account_id = 0;
    public $serial = '';
    public $image = '';
    public $model = '';
    public $last_ip = '';
    public $add_date = '';

    public $play_in_preview_by_ok = false;
    public $theme = 'default';
    public $show_after_loading = 'main_menu';
    public $stream_format = 'ts';
    public $favorite_streams = [];
    public $favorite_videos = [];


    public function __construct(int $account_id  = null) {

        if(is_null($account_id))
            return;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_mag_devices WHERE account_id = '$account_id'");
        if(!$row=$r->fetch_assoc()) {
            $r2=$db->query("SELECT mag FROM iptv_devices WHERE id = '$account_id' AND allow_mag = 1 ");
            if(!$mac = $r2->fetch_assoc()['mag'])
                throw new \Exception("No mag device found for account #$account_id");
            else {
                self::add($account_id);
                $r=$db->query("SELECT * FROM iptv_mag_devices WHERE account_id = '$account_id'");
                $row=$r->fetch_assoc();
            }

        }

        $this->id = $row['id'];
        $this->account_id = $row['account_id'];
        $this->mac_address = $row['mac'];
        $this->serial= $row['serial'];
        $this->model = $row['model'];
        $this->image = $row['image'];
        $this->last_ip = $row['ip'];
        $this->add_date = $row['add_date'];

        $settings = unserialize($row['settings']);
        $this->play_in_preview_by_ok = (bool)$settings['play_in_preview_by_ok'];
        $this->theme = $settings['theme'];
        $this->show_after_loading = $settings['show_after_loading'];


        $this->stream_format = $settings['stream_format'] == "m3u8" ? $settings['stream_format'] : 'ts';

        $force_theme = (bool)Settings::get(Settings::STB_FORCE_THEME);
        if($force_theme) {
            $this->theme = Settings::get(Settings::STB_THEME);
        } else {
            $this->theme = !$settings['theme']
                ? Settings::get(Settings::STB_THEME)
                : $settings['theme'];
        }

        $this->getFavorites();
    }


    private function getFavorites():bool {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT favs FROM iptv_mag_devices WHERE account_id = '$this->account_id'");

        if(!$favs = unserialize($r->fetch_assoc()['favs']))
            return true;

        $this->favorite_videos = is_array($favs['video']) ? $favs['video'] : [];
        $this->favorite_streams = is_array($favs['itv']) ? $favs['itv'] : [];

        return true;
    }

    public static function getThemes() {
        $TEMPLATES = [];
        $templates_dir = DOC_ROOT . 'c/template/';
        foreach( glob( "$templates_dir/*" , GLOB_ONLYDIR) as $dir ) {

            $dir = str_replace($templates_dir.'/' , '' , $dir);
            $name = str_replace("_", ' ', $dir);
            $name = strtoupper($name);
            $TEMPLATES[$dir] = $name;

        }

        return $TEMPLATES;
    }

    public function setStreamFormat(String $stream_format = '') {
        $this->stream_format = $stream_format !== 'm3u8' ? 'ts' : $stream_format;
    }

    public function setPlayInPreviewByOk(bool $play_in_preview_by_ok):bool {
        $this->play_in_preview_by_ok = $play_in_preview_by_ok;
        return true;
    }

    public function setFavoritesVideos(array $favorites_videos):bool {

        $new_favorites = [];
        foreach($favorites_videos as $video) {
            $video = (int)$video;
            if($video && !in_array($video, $this->favorite_videos))
                $new_favorites[] = $video;

        }

        $this->favorite_videos = $new_favorites;
        return true;
    }

    public function setFavoritesStreams(array $favoritesStreams):bool {

        $new_favorites = [];
        foreach($favoritesStreams as $stream) {
            $stream = (int)$stream;
            if($stream && !in_array($stream, $this->favorite_videos))
                $new_favorites[] = $stream;

        }

        $this->favorite_streams = $new_favorites;
        return true;
    }

    public function sendEvent(String $event, String $message = '', bool $reboot_on_confirm = true ):bool {

        if(!in_array($event,array('send_msg','reload_portal','reboot','cut_off','reset_stb_lock')) )
            throw new \Exception("Unknown Event.");

        $db = new \ACES2\DB;
        $message = $db->escString($message);

        if($event == 'send_msg') {
            $db->query("INSERT INTO iptv_mag_event (account_id,event,message,reboot_after_ok) 
                VALUES('$this->account_id','send_msg','$message','$reboot_on_confirm' )");
        } else {
            $r=$db->query("SELECT id FROM iptv_mag_event WHERE account_id = $this->account_id AND event = '$event'");
            if(!$r->fetch_assoc())
                $db->query("INSERT INTO iptv_mag_event (account_id,event,reboot_after_ok) 
                    VALUES('$this->account_id','$event','$reboot_on_confirm' )");
        }

        return true;
    }

    public function setTheme(string $theme):bool {
        $this->theme = $theme;
        return true;
    }

    public function save():bool {
        $db = new \ACES2\DB;
        $settings = serialize(
            array(
                'play_in_preview_by_ok' => $this->play_in_preview_by_ok,
                'theme' => $this->theme,
                'show_after_loading' => $this->show_after_loading,
                'stream_format' => $this->stream_format,
            )
        );

        $fav = serialize(
            array(
                'video' => $this->favorite_videos,
                'itv' => $this->favorite_streams,
            )
        );

        $db->query("UPDATE iptv_mag_devices SET settings = '$settings', favs = '$fav'
                        WHERE account_id = '$this->account_id'");
        return true;
    }

    public static function add(int $account_id):self {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT id FROM iptv_mag_devices WHERE account_id = '$account_id'");
        if($r->num_rows)
            return new self($account_id);

        $r=$db->query("SELECT mag FROM iptv_devices WHERE id = '$account_id'");
        if(!$mac = $r->fetch_assoc()['mag'])
            throw new \Exception("Unable to create mag device for account #$account_id without Mac-Address");

        $db->query("INSERT INTO iptv_mag_devices (account_id,mac,add_date) 
            VALUES('$account_id','$mac',now())");

        return new self($account_id);

    }

}