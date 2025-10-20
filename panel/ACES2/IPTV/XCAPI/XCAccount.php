<?php

namespace ACES2\IPTV\XCAPI;

use ACES2\Curl;

class XCAccount {
    public $id = 0;
    public $host = "";
    public $port = 80;
    public $username = "";
    public $password = "";
    public $url = "";
    public $expire_time = 0;
    private $max_connections = 0;
    private $active_connections = 0;
    public $is_trial = true;
    private $is_ssl = false;
    private $cache_time = 30;
    private $last_update_time = 0;
    private $expire_date = '';
    private $status = 'Not Found';
    public $auto_update_content = false;

    public function __construct(int $id = null) {

        if($id !== null) {
            $this->id = $id;
            $db = new \ACES2\DB;
            $r=$db->query("SELECT * FROM iptv_xc_videos_imp WHERE id=$id ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to retrieve XC Account #$id from database.");

            $this->port = (int)$row["port"];
            $this->username = $row["username"];
            $this->password = $row["password"];
            $this->max_connections = $row['max_connections'];
            $this->active_connections = $row['active_connections'];
            $this->last_update_time = $row['last_update_time'];
            $this->expire_date = $row['exp_date'];
            $this->status = $row['status'];
            $this->is_ssl = (bool)$row["is_ssl"];
            $this->auto_update_content = (bool)$row["auto_update_content"];

            $scheme = $this->is_ssl ? 'https' : 'http';
            $this->host =$row['host'];

//            if(!$scheme = parse_url($row['host'], PHP_URL_SCHEME)) {
//                $scheme = "http";
//                $this->host = $row['host'];
//            } else
//                $this->host = parse_url($row['host'], PHP_URL_HOST);

            $this->url = "$scheme://$this->host";
            if($this->port > 0)
                $this->url .= ":".$this->port;

        }


    }

    private function getInfo() {

        if( $this->last_update_time + $this->cache_time > time() )
            return;

        $resp = $this->getCurl();
        if($resp['user_info']['auth'] != 1 )
            throw new \Exception("Unable to info from XC '$this->host' Api. Username and password are correct?");

        $this->max_connections = $resp['user_info']["max_connections"];
        $this->active_connections = $resp['user_info']["active_cons"];
        $this->expire_time = $resp['user_info']["exp_date"];
        $this->expire_date = date("Y-m-d H:i:s",  $this->expire_time);
        $this->is_trial = (bool)$resp['user_info']["is_trial"];
        $this->last_update_time = time();
        $this->status = $resp['user_info']["status"];
        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_xc_videos_imp SET last_update_time= '$this->last_update_time', 
                              max_connections = '$this->max_connections', active_connections = '$this->active_connections',
                              `exp_date` = '$this->expire_date', `status` = '$this->status'
                              WHERE id = $this->id ");

    }

    public function getActiveConnections() {
        $this->getInfo();
        return $this->active_connections;
    }

    public function getMaxConnections() {
        $this->getInfo();
        return $this->max_connections;
    }

    public function getExpireDate() {
        $this->getInfo();
        return $this->expire_date;
    }

    public function getStatus() {
        $this->getInfo();
        return $this->status;
    }

    public function getVodCategories() {
        return $this->getCurl('get_vod_categories');
    }

    public function getSeriesCategories() {
        return $this->getCurl('get_series_categories');
    }

    public function getLiveStreams(int $category_id = 0) {

        $url = "{$this->url}/player_api.php?username={$this->username}&password={$this->password}&action=get_live_streams";
        if($category_id > 0)
            $url .= "&category_id=$category_id";


        $curl = new \ACES2\Curl($url);
        return json_decode($curl->getBody(), true);

    }

    private function getCurl(String $action = ''):array {

        $ch = curl_init();
        $param = $action != '' ? '&action='.$action : '';

        $curl = curl_init("$this->url/player_api.php?username=$this->username&password={$this->password}{$param}");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        $resp = curl_exec($curl);
        if (curl_errno($curl)  ) {
            $error_msg = curl_error($curl);
            throw new \Exception("Fail to connect to XC Server. Error '$error_msg'");
        }

        $json =  json_decode($resp, true);
        if(is_null($json))
            throw new \Exception("Unable to get info from portal $this->host.");

        return json_decode($resp,1);

    }

    private function set($POST) {
        $db = new \ACES2\DB;
        $this->username = $db->escString($POST['username']);
        $this->port = (int)$POST['port'];

        $sql = '';
        if($this->id > 0 ) {
            $sql = "AND id != '$this->id'";
            if(!empty($POST['password']))
                $this->password = $db->escString($POST['password']);
        } else
            $this->password = $db->escString($POST['password']);

        $scheme = parse_url($POST['host'], PHP_URL_SCHEME);
        if(!$this->host = parse_url($POST['host'], PHP_URL_HOST))
            throw new \Exception("Enter a valid url.");

        $this->is_ssl = $scheme != 'https' ? false : true ;

        $this->url = "$scheme://$this->host";
        if($this->port > 0)
            $this->url .= ":$this->port";

        $this->auto_update_content = (bool)$POST['auto_update_content'];

        if(empty($this->username) || empty($this->password))
            throw new \Exception("Username and password are required.");

        $r=$db->query("SELECT id FROM iptv_xc_videos_imp 
          WHERE username='$this->username' AND password='$this->password' AND host='$this->host' $sql ");
        if($r->num_rows > 0)
            throw new \Exception("This account already exists.");


        $this->getInfo();

    }

    public function update($POST) {
        $this->set($POST);
        $db = new \ACES2\DB();
        $db->query("UPDATE iptv_xc_videos_imp SET username = '$this->username', password = '$this->password', 
                              port = '$this->port', host = '$this->host', auto_update_content = '$this->auto_update_content',
                              is_ssl = '$this->is_ssl'
                          WHERE id = '$this->id'");
    }

    public function remove():bool {
        $db = new \ACES2\DB();
        $db->query("DELETE FROM iptv_xc_videos_imp WHERE id = '$this->id'");
        return true;
    }

    public static function add($POST) {
        $xc_account = new self();
        $xc_account->set($POST);
        $db = new \ACES2\DB();

        $db->query("INSERT INTO iptv_xc_videos_imp (host, port, username, password, is_ssl,auto_update_content)
            VALUES('$xc_account->host', '$xc_account->port', '$xc_account->username', '$xc_account->password', 
                   '$xc_account->is_ssl', '$xc_account->auto_update_content' )");

        return $xc_account;

    }

}