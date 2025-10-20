<?php

class Cache {

    const PATH = "/home/aces/cache/";

    public $filename = '';

    public $expiration_time = 60;

    public $path = "/home/aces/";

    public $compressed = false;

    public function __construct(
        $filename,
        $expiration_time=60,
        $compress_it=false) {

        $pathinfo = pathinfo($filename);
        if($pathinfo['dirname'] == '.') {
            if(!is_dir(  self::PATH ))
                if(!mkdir(self::PATH , 664, true ))
                    AcesLogE("Unable to create directory to save cache.");

            $this->filename = self::PATH . $filename;

        } else {
            if(!is_dir($pathinfo['dirname']))
                if(!mkdir($pathinfo['dirname'], 664, true ))
                    AcesLogE("Unable to create directory {$pathinfo['dirname']} to save cache.");


            $this->filename = $filename;
        }

        if((int)$expiration_time > 0 )
            $this->expiration_time = $expiration_time;

        if($compress_it) {
            $this->filename = $this->filename . ".gz";
            $this->compressed = true;
        }

    }

    public function clear() {
        if(unlink($this->filename)) return true;
        return false;
    }

    public function saveit($data='') {

        if(!$this->filename || !$data ) return false;
        if($this->compressed) {
            $data = gzdeflate($data);
        }
        if (!file_put_contents($this->filename,$data))
            return false;

        return true;
    }

    public function get($ignore_expired=0) {

        if(!$data=file_get_contents($this->filename))
            return false;


        if( $this->isExpired() && !$ignore_expired )
            return false;

        if($this->compressed)
            $data = gzinflate($data);


        return $data;
    }

    public function isExpired() {

        clearstatcache();

        $exp = (filemtime($this->filename) + $this->expiration_time ) ;

        if( $exp < time() || ! $data=file_get_contents($this->filename) ) {
            return true;
        }

        return false;
    }

}