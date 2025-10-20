<?php
die;
if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) { exit(); } 


error_reporting(0);
if(empty($_GET['token'])) die;


list($ACCESS_ID,$token) = explode(':',$_GET['token']);

if(strlen($token) != 32 && strlen($token) != 64 ) die;
if(!preg_match('/^[a-zA-Z0-9-]+$/',$token)) die;
if(!is_numeric($ACCESS_ID )) die;



set_time_limit ( 0 );
#ob_end_clean();
#header("Connection: close\r\n");
#header("Content-Encoding: none\r\n");

//FOR SOME SOFTWARE MAY DETECT LEAVE WILL ITS IS CONNECTED. 
//ignore_user_abort(true); // optional
ob_start();

$CHANID = (int)$_GET['file'];

$file = explode('.',$_GET['file']);
$CHANID = (int)$file[0];
$TYPE= $file[1];


if(!is_numeric($CHANID)) DIE;

include 'config.php';

if(empty($_GET['type'])) $TYPE= 'playlist';
else if($_GET['type'] == 'mpegts' ) $TYPE='mpegts';
else if($_GET['type'] == 'm3u8')  $TYPE= 'playlist';
else if($_GET['type'] == 'ts') $TYPE = 'chunk';
else $TYPE= 'playlist';



clearstatcache();
if(!is_file("/home/aces/stream_tmp/$CHANID-.m3u8")) { die; } 
if( (fileatime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ){ die; }


if($TYPE == 'playlist') { 

	ob_clean();
//	header("Connection: close");
//	header("Accept-Ranges: bytes");
//	header('Content-type:application/force-download');
//	header('Content-Disposition: attachment; filename=playlist.m3u8');
//	//header('Content-Length: ' . filesize("/home/aces/stream_tmp/$CHANID-.m3u8"));
//	header('Content-type: application/vnd.apple.mpegurl' );
        
        header('Content-Type: application/x-mpegurl');
        header('Content-Length: ' . filesize("/home/aces/stream_tmp/$CHANID-.m3u8"));
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate');

	$fp = fopen("$FOLDER/{$_GET['file']}", 'rb');
	fpassthru($fp);
	fclose($fp);

	@ob_flush();@flush();
	//ob_end_clean();
	
	die;

}





$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0){ die(); }



$r=$DB->query("SELECT id FROM iptv_access WHERE id=$ACCESS_ID AND token='$token' AND chan_id = $CHANID AND limit_time > NOW() AND  ip_address = '{$_SERVER['REMOTE_ADDR']}'   ");
if(!($row=$r->fetch_assoc())) { $DB->close(); die; }

function close() {

	Global $DB,$ACCESS_ID;

	$DB->query("DELETE FROM iptv_access WHERE id = $ACCESS_ID");
	//$DB->close();
	die;

}



if($TYPE == 'mpegts') {
	
        header('Content-Type: application/octet-stream');
        header('Transfer-Encoding: chunked');

	$LAST_CHK = '';$SKIP_FIRST=0;
	while(true) {
		
		$FOUND_LAST=0;
		
 		clearstatcache();
 		if( (fileatime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ) { close() ; }
	
		if($fp = @fopen("/home/aces/stream_tmp/$CHANID-.m3u8", "r")) {
	
			$chk = '';$time=0;
			while (($line = fgets($fp)) !== false) { 
				$line = trim($line);
	
				//IF WE SEND A CHUNK BEFORE LET FIND IT AND SEND NEXT ONE.
				if($LAST_CHK) { 
	
                                    $c = trim(fgets($fp)); //$line is time, let get next line for chunk
                                    if($c == $LAST_CHK ) { //LAST CHUNK FOUNDED. LET GET TIME AND NEXT CHUNK

                                        $FOUND_LAST = 1;

                                        if( ($n_line = fgets($fp)) !== false ) {
                                                $time =  (int)explode(':',$n_line)[1];
                                                $chk = trim(fgets($fp));
                                        }

                                    } 
	
				} else if( strpos($line,'EXTINF:')) {    

                                    //WE FOUND FIRST CHUNK LET GET TIME.

                                    //FIRST CHUNK MAKE A JUMP FOR SOME REASON. LET SKIP IT AND GET NEXT ONE.
                                    if(!$SKIP_FIRST) $SKIP_FIRST = 1;
                                    else { $chk = trim(fgets($fp));$time =  (int)explode(':',$line)[1]; }
						
	
				}
				
				//DOWNLOADING.
				if($chk) { 
                                        $be_time = time(); //TIME BEFORE SEND.
                                        $be_mtime = microtime(true);
                                        $chunkSize = 1024 * 1024 ;
                                        if(!($handle = fopen("/home/aces/stream_tmp/$chk", 'rb'))) close();
                                        while (!feof($handle)) { 

                                                $buffer = fread($handle, $chunkSize);
                                                echo $buffer;
                                                ob_flush();
                                                flush();


                                         }
                                         fclose($handle);
                                         $af_time = time(); //TIME AFTER SEND.
                                         $ad_mtime = microtime(true);
                                         $mtime = $ad_mtime - $be_mtime;
                                         $time = $be_time - $af_time;
                                         
                                        if(!@$DB->ping()) {
                                            unset($DB);
                                            $DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
                                            if($DB->connect_errno > 0) { die; }
                                        }

                                        //ACCESS IS STILL ALIVE ????
                                        $r1=$DB->query("SELECT id FROM iptv_access WHERE id=$ACCESS_ID  ");
                                        if($r1->num_rows < 1 )  { close(); }

                                        //UPDATING TIME
                                        $DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 30 SECOND WHERE id = $ACCESS_ID ");
                                        $r1->free();
                                        $DB->close();

                                        //DO NOT KEEP SENDING CHUNK ON LIST LET EXIT.
                                        break;
				}
	
	
			}

	
		}

	
		//TODO:
		//IF WE DIDN'T FOUND LAST CHUNK THATS MEAN LIST HAVE RESTARTED. LET START OVER
		//if($SKIP_FIRST && !$FOUND_LAST) {
		//	error_log('SOURCE RESTART LET GET FITST CHUNK.'); $SKIP_FIRST = 0; $LAST_CHK = '';
		//}
	
		
		if($chk) $LAST_CHK = $chk;
		

		if($time) { //IF THERE IS TIME MEAN IT SEND SOMETHING LET DO A SMART SLEEP.
			
			$sleep  = ($be_time+$time) - $af_time;
			
			
			if( ($be_time+$time) > $af_time ) { sleep( (($be_time+$time) - $af_time) - 2  ) ;

			}
			
			
		} else usleep(500000);
                
		fclose($fp);
                
	   	if(connection_status() != CONNECTION_NORMAL ) {  close(); }
	 	if(connection_aborted()){ close(); }
		
		
		
		
	}
	
	
	
// 	$first = 1;
// 	$FOUND = 0;
// 	while(true){


// 		if($FOUND == 0 ) { $last_chunk = null; $first = 1;}
// 		$FOUND =  0;

// 		$is_next=0;
// 		if(empty($last_chunk)) $is_next = 1;
		
// 		clearstatcache();
// 		if( (fileatime("/home/aces/stream_tmp/$CHANID-.m3u8") + 60 ) < time() ) { close() ; }
		

// 		if($fp = @fopen("/home/aces/stream_tmp/$CHANID-.m3u8", "r")) {
// 			while (($line = fgets($fp)) !== false) {
// 			    $line = trim($line);
// 	    		    if ($line && strpos($line,'#') === false) {

// 	    		    		$chk = $line;
		
// 			    		    //NEVER GET FIRST CHUNK IT BUFFER FOR SOME REASON
// 							if($is_next && $first > 1 )  {
// 							//if($is_next) { 

// 									$chunkSize = 1024 * 1024 ;
// 									$handle = fopen("/home/aces/stream_tmp/$chk", 'rb');
// 									while (!feof($handle)) {
// 										$buffer = fread($handle, $chunkSize);
// 										echo $buffer;
// 										ob_flush();
// 										flush();
									       
// 									 }
// 									 fclose($handle);
		
									
// 								 	$last_chunk= $chk;
// 								 	$FOUND = 1;
								 	
// 								 	//ACCESS IS STILL ALIVE ????
// 								 	$r1=$DB->query("SELECT id FROM iptv_access WHERE id=$ACCESS_ID  ");
// 								 	if($r1->num_rows < 1 )  { close(); }
		
// 								 	//UPDATING TIME
// 									$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 1 MINUTE WHERE id = $ACCESS_ID ");
// 									$r1->free();

// 							}
		
// 	    		    		if(!$is_next)
// 	    		    			if($chk && $chk == $last_chunk) {  $is_next = 1; }


// 	    		    		if($first == 1 )$first++;

// 	    		    		if($chk == $last_chunk) $FOUND = 1;
	    		    		
	    		    		
// 	    		    }


// 	    		}
	    		
	    		
// 	    		fclose($fp);
	    		
// 	    	}


//     	if(connection_status() != CONNECTION_NORMAL ) {  close(); }
// 		if(connection_aborted()){ close(); }

// 		sleep(2);


// 	}

} else {

	
	ob_clean();
//	header("Connection: close");
//	header("Accept-Ranges: bytes");
//	header('Content-type:application/force-download');
//	header('Content-Length: ' . filesize('/home/aces/stream_tmp/'.$_GET['file']));
//	header('Content-type: application/vnd.apple.mpegurl' );
        
	#header("Cache-Control: no-cache");
 	#header("Pragma: no-cache");
        

        header('Connection: keep-alive');
        header('Content-Type: video/mp2t');
        header('Cache-Control: no-store, no-cache, must-revalidate');


//	$fp = fopen("/home/aces/stream_tmp//{$_GET['file']}", 'rb');
//	fpassthru($fp);
//	fclose($fp);ob_flush();flush();
	
	////ob_end_clean();
	

	
    $chunkSize = 1024 * 1024 ;
    $handle = fopen("/home/aces/stream_tmp/{$_GET['file']}", 'rb');
    while (!feof($handle))
    {
        $buffer = fread($handle, $chunkSize);
        echo $buffer;
        ob_flush();
        flush();
       
    }
    fclose($handle);

	

}



//UPDATING TIME
$DB->query("UPDATE iptv_access SET limit_time = NOW() + INTERVAL 60 SECOND WHERE id = $ACCESS_ID ");

die;
