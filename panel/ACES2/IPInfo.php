<?php

namespace ACES2;

class IPInfo {

    public $ipAddress = '';
    public $isIPv6 = false;
    public $hostname = '';
    public $asn = '';
    public $city = '';
    public $country = '';
    public $geo_location = "";
    public $countryCode = '';
    //public $isp = '';
    public $org = '';
    public $region = '';
    public $regionName = '';
    public $timezone = '';
    public $postalCode = '';
    public $zipCode = '';

    public function __construct(String $ip_address, $force_update = false) {

        if(!filter_var($ip_address, FILTER_VALIDATE_IP))
            throw new \Exception("Invalid IP address");

        $this->ipAddress = $ip_address;
        $this->isIPv6 = (bool)filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        $db = new \ACES2\DB;

        if($force_update)
            $this->fetchIpInfo();
        else {
            $r=$db->query("SELECT city,country_code,hostname,org,region_name,timezone,geo_location,zipcode  
            FROM iptv_ip_info WHERE ip_address='$ip_address'  ");

            if($r->num_rows < 1) {
                if(!$this->fetchIpInfo())
                    throw new \Exception("Unable to fetch '$ip_address' IP info");

                $r=$db->query("SELECT city,country_code,hostname,org,region_name,timezone,geo_location,zipcode  
                    FROM iptv_ip_info WHERE ip_address='$ip_address'  ");

            }

        }


        $r=$db->query("SELECT city,country_code,hostname,org,region_name,timezone,geo_location,zipcode  
            FROM iptv_ip_info WHERE ip_address='$ip_address'  ");

        $row = $r->fetch_assoc();

        $this->city = $row['city'];
        $this->countryCode = $row['country_code'];
        $this->hostname = $row['hostname'];
        $this->org = $row['org'];
        $this->regionName = $row['region_name'];
        $this->timezone = $row['timezone'];
        $this->geo_location = $row['geo_location'];
        $this->zipCode = $row['zipcode'];
        $this->postalCode = $row['zipcode'];


    }


    private function fetchIpInfo() {

        $db = new \ACES2\DB;

        $url = $this->isIPv6 ? "https://v6.ipinfo.io/" : "https://ipinfo.io/";

        $resp = json_decode(file_get_contents($url.$this->ipAddress."/json"));

        include_once  $_SERVER['DOCUMENT_ROOT'].'/includes/countries.php';
        $country_name = $__COUNTRIES[$resp->country];
        $org = $db->escString($resp->org);
        $region = $db->escString($resp->region);
        $city = $db->escString($resp->city);


        $r=$db->query("SELECT ip_address FROM iptv_ip_info WHERE ip_address = '".$this->ipAddress."'");
        if($r->num_rows < 1 )
            $db->query("INSERT INTO iptv_ip_info (is_ipv6,ip_address,city,country,country_code,org,region_name,timezone,
                          zipcode,geo_location,hostname)   
                values('$this->isIPv6','$resp->ip', '$city', '$country_name', '$resp->country', '$org', '$region', 
                       '$resp->timezone', '$resp->postal','$resp->loc', '$resp->hostname'  );");

        else
            $db->query("UPDATE iptv_ip_info SET city = '$city', country = '$country_name', country_code = '$resp->country', org = '$org',
                region_name = '$region', timezone = '$resp->timezone', geo_location = '$resp->loc', hostname = '$resp->hostname', 
                zipcode = '$resp->zipcode'    
            WHERE ip_address = '$this->ipAddress'");

        return true;

    }



}