<?php

//http_response_code(403);

//sleep(60*10);

set_time_limit(0);
ini_set('upload_max_filesize', '10G');
ini_set('post_max_size', '10G');
ini_set('max_input_time', 300*600);
ini_set('max_execution_time', 300*600);

include "/home/aces/stream/config.php";
if($_GET['api_token'] != $API_TOKEN ) { sleep(5);http_response_code(400);  die; } 

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ error_log('COULD NOT CONNECT TO DATABASE'); http_response_code(500); die(); }
$r=$DB->query("SELECT id FROM iptv_servers WHERE address = '{$_SERVER['REMOTE_ADDR']}' ");
if(!$r->fetch_assoc()) { sleep(5); error_log('UPLOAD NOT FROM PANEL SERVER'); http_response_code(500); die(); }



if(empty($_GET['filename'])) { http_response_code(400); error_log("Missing filename or path"); die; }
//$PATH = "/home/aces/".urldecode($_GET['path']);
$FILENAME = "/home/aces/".urldecode($_GET['filename']);
$MD5 = $_GET['checksum'];

/* PUT data comes in on the stdin stream */
if(!$input_file = fopen("php://input", "r")) { http_response_code(400); error_log("UNABLE TO READ FROM STDIN"); die; }

/* Open a file for writing */
$fp = fopen("$FILENAME", "w");

/* Read the data 1 KB at a time
   and write to the file */
while ($data = fread($input_file, 1024))
  fwrite($fp, $data);

/* Close the streams */
fclose($fp);
fclose($input_file);


if(isset($_GET['checksum']) && md5_file("$FILENAME") !== $MD5 ) { http_response_code(500);  error_log("FILE FAIL TO UPLOAD "); die; }
