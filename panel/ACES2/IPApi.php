<?php

namespace ACES2;

class IPApi {
    public $ipAddress = '';
    public $asn = '';
    public $city = '';
    public $country = '';
    public $countryCode = '';
    public $isp = '';
    public $org = '';
    public $region = '';
    public $regionName = '';
    public $timezone = '';
    public $zipCode = '';

    public function __construct(String $ip_address, bool $force_update = false) {

        $this->ipAddress = $ip_address;

        if($force_update)
            self::fetchInfo($ip_address);

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_ip_info WHERE ip_address='{$ip_address}'");
        if($r->num_rows < 1 && !$force_update ) {
            if(!self::fetchInfo($ip_address))
                return ; //NOTHING FOUND. NOTHING ELSE TO DO.
            $r=$db->query("SELECT * FROM iptv_ip_info WHERE ip_address='{$ip_address}'");
        }

        if($row=$r->fetch_assoc()) {
            $this->asn = $row['asn'];
            $this->city = $row['city'];
            $this->country = $row['country'];
            $this->countryCode = $row['country_code'];
            $this->isp = $row['isp'];
            $this->org = $row['org'];
            $this->region = $row['region'];
            $this->regionName = $row['region_name'];
            $this->timezone = $row['timezone'];
            $this->zipCode = $row['zipcode'];
        }

    }

    static public function fetchInfo(String $ip_address):bool {

        $db = new \ACES2\DB;
        $ip_info = json_decode(file_get_contents("https://ip-api.com/json/$ip_address"),true);
        if(!is_array($ip_info)){
            LogE("Failed to fetch IP info of '$ip_address'");
            return false;
        }

        $db->query("INSERT INTO iptv_ip_info (ip_address,asn,city,country,country_code,isp,org,region,region_name,timezone,zipcode) 
            VALUES('{$_SERVER['REMOTE_ADDR']}','{$ip_info['as']}','{$ip_info['city']}','{$ip_info['country']}','{$ip_info['countryCode']}',
                   '{$ip_info['isp']}','{$ip_info['org']}','{$ip_info['region']}','{$ip_info['regionName']}','{$ip_info['timezone']}','{$ip_info['zip']}' )  ");

        return true;

    }
}