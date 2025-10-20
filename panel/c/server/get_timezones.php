<?php

header('Content-Type: application/json');
header('Connection: close');


$d=array();
foreach(timezone_identifiers_list() as $i => $v ) {
    $d[] = array('label'=>$v,'value'=>$v,'selected'=>0);
}

$js=array (
  'js' => $d
);

echo json_encode($js);
die;