<?php

$ERR[100] = "Unknown Error";
$ERR[101] = "System Error.";

$ERR[201] = "Unauthorized";
$ERR[202] = "Incomplete or Parameters.";


function set_error($e) { 
    
    global $ERR;
    
    if(!isset($ERR[$e])) $e = 100;
    
    $json['status'] = 'error';
    $json['error_msg'] = $ERR[$e];
    $json['error_code'] = $e;
    
    http_response_code(401);
    
    echo json_encode($json);
    die;
    
}

function set_complete($DATA=null) { 
    
        
    $json['status'] = 'complete';
    if($DATA) $json['data'] = $DATA;
    
    echo json_encode($json);
    die;
    
}