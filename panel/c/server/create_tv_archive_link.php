<?php

header('Content-Type: application/json');
header('Connection: close');


$cmd = $_REQUEST['cmd'];

$media_id=str_replace('auto ','',$cmd);
$media_id=str_replace('/media/','',$media_id);
$media_id=str_replace('.mpg','',$media_id);
$media_id=str_replace('file_','',$media_id);

$cmd = "http://{$_SERVER['HTTP_HOST']}/load/record/{$MAG_USERNAME}/{$MAG_TOKEN}/$media_id.ts";
//$cmd = "http://192.168.122.1:8080/1.mp4";
//$cmd = "http://24.171.236.129:8080/3.mkv";
//$cmd = "http://24.171.236.129:8080/628-.m3u8";
//$cmd = "http://24.171.236.129:9090/load/123456/123456/5580.m3u8";

$js = array (
  'js' => 
  array (
    'id' => $media_id,
    'cmd' => "$cmd",
    'storage_id' => '1',
    'load' => '1',
    'error' => '',
    'subtitles' => 
    array (
    ),
  ),
  'text' => '',
);

echo json_encode($js);
die;