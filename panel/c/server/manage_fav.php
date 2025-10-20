<?php

header('Content-Type: application/json');
header('Connection: close');

error_log(print_r($_GET,1));

if(isset($_GET['video_id']) && is_numeric($_GET['video_id'])) { 
    
    
    if($_REQUEST['action'] == 'set_fav') {

        if(!is_array($FAVS['video'])) $FAVS['video'] = [];
        
        if(!in_array($_GET['video_id'],$FAVS['video'])) $FAVS['video'][] = $_GET['video_id'];
        
    } else if($_REQUEST['action'] == 'del_fav') { 
        
        if (($key = array_search($_GET['video_id'], $FAVS['video'])) !== false) {
            unset($FAVS['video'][$key]);
        }
        
    }
        

} else if(isset($_GET['fav_ch'])) { 
    
    $f = explode(',',$_GET['fav_ch']);
   // $FAVS['itv'] = $f;
    

    if($_REQUEST['action'] == 'set_fav') {

        if(!is_array($FAVS['itv'])) $FAVS['itv'] = [];

        if(!in_array($_GET['fav_ch'],$FAVS['itv'])) $FAVS['itv'][] = $_GET['fav_ch'];

    } else if($_REQUEST['action'] == 'del_fav') {

        if (($key = array_search($_GET['fav_ch'], $FAVS['itv'])) !== false) {
            unset($FAVS['itv'][$key]);
        }

    }
    
    

    
    
} else { echo '{"js":[]}'; die; }


$s= serialize($FAVS);
$DB->query("UPDATE iptv_mag_devices SET favs = '$s' WHERE id = $MAG_ID ");