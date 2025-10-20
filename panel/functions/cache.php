<?php
function SaveCache($filename,$data,$compress_it=true) {

    if(!$filename || !$data ) return false;

    $pathinfo = pathinfo($filename);
    if($pathinfo['dirname'] == '.') {
        if(!is_dir( ACES_ROOT . "/cache" ))
            if(!mkdir(ACES_ROOT."/cache", 664, true ))
                AcesLogE("Unable to create directory to save cache.");

        $filename= ACES_ROOT . "/cache/" . $filename;

    } else {
        if(!is_dir($pathinfo['dirname']))
            if(!mkdir($pathinfo['dirname'], 664, true ))
                AcesLogE("Unable to create directory {$pathinfo['dirname']} to save cache.");


        $filename = $filename;
    }



    if($compress_it) {
        $data = gzdeflate($data);
    }
    if (!file_put_contents($filename,$data))
        return false;

    return true;


}