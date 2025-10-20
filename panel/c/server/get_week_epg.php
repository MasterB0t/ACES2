<?php


header('Content-Type: application/json');
header('Connection: close');

$d=array();
for($i=16;$i>0;$i--) { 
    
    $h = date('D d M', strtotime(date('Ymd') . " -$i day"));
    $q = date('Y-m-d', strtotime(date('Ymd') . " -$i day"));
    $d[] = array('f_human' => $h, 'f_mysql' => $q, 'today' => 0 );
    
}

$h = date('D d M', time());
$q = date('Y-m-d', time());
$d[] = array('f_human' => $h, 'f_mysql' => $q, 'today' => 1 );


for($i=1;$i<5;$i++) { 
    
    $h = date('D d M', strtotime(date('Ymd') . " +$i day"));
    $q = date('Y-m-d', strtotime(date('Ymd') . " +$i day"));
    $d[] = array('f_human' => $h, 'f_mysql' => $q, 'today' => 0 );
    
}

$js = array('js'=> $d);

//error_log(print_r($d,1));

echo json_encode($js);
die;