<?php 
$server_offset = date('Z');
$PidFile = "/home/aces/run/aces_build_guide.pid";
ini_set("error_log", "/home/aces/logs/aces_build_guide.log");
ini_set("log_errors", 1);
chdir("/home/aces/panel");
set_time_limit ( 0 );

ini_set('memory_limit', '-1');

//date_default_timezone_set('UTC');
//error_reporting(0);

function sanitizeXML($string) {
    if (!empty($string)) 
    {
        // remove EOT+NOREP+EOX|EOT+<char> sequence (FatturaPA)
        $string = preg_replace('/(\x{0004}(?:\x{201A}|\x{FFFD})(?:\x{0003}|\x{0004}).)/u', '', $string);
 
        $regex = '/(
            [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0[\x80-\x9F] # Overlong encoding of prior code point
            | \xF0[\x80-\x8F] # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
        $string = preg_replace($regex, '', $string);
 
        $result = "";
        $current;
        $length = strlen($string);
        for ($i=0; $i < $length; $i++)
        {
            $current = ord($string[$i]);
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $result .= chr($current);
            }
            else
            {
                $ret;    // use this to strip invalid character(s)
                // $ret .= " ";    // use this to replace them with spaces
            }
        }
        $string = $result;
    }
    return $string;
}

function logfile($msg) { 
    if($msg)
        file_put_contents('/home/aces/logs/aces_guide_builder.log', "\n[ ".date('Y:m:d H:i:s')." ] $text \n\n", FILE_APPEND );
    
}

function quit($msg = '') {
    global $PidFile;
    if($msg) error_log($msg);
    unlink($PidFile);
    unlink("/home/aces/tmp/epg_progress");
    unlink("/home/aces/tmp/output_guide.xml");
    die($msg);
}

if(is_file($PidFile)) {
    
    $PID = file_get_contents($PidFile);
    if(posix_getpgid($PID))
        quit("Another importer process is running please wait until it finished.");
    
} else {
 
    if(!file_put_contents($PidFile,getmypid()))
        quit('Could not write pid file.');
 
}


register_shutdown_function('quit');




//include_once '/home/aces/panel/includes/init.php';
include_once '/home/aces/stream/config.php';
include_once '/home/aces/panel/class/EpgParser.php';
include_once '/home/aces/panel/ACES2/DB.php';
include_once '/home/aces/panel/ACES2/IPTV/EpgSource.php';
include_once '/home/aces/panel/class/db.php';

$DB = new \DB($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) { quit("Could not connect to database."); } 

$json_status = array();

//if(!($LIMIT_PROGRAMME_HOURS =$DB->get_setting('iptv.EpgLength'))) $LIMIT_PROGRAMME_HOUR=48 ;
$r=$DB->query("SELECT value FROM settings WHERE name = lower('iptv.EpgLength') ");
if(!$LIMIT_PROGRAMME_HOUR=mysqli_fetch_array($r)['value']) $LIMIT_PROGRAMME_HOUR=48 ;
$LIMIT_TIME = time() + (( 60*60 ) * $LIMIT_PROGRAMME_HOUR);
$LIMIT_DATE = date("YmdHis",$LIMIT_TIME);
$DATE = date('YmdHis',time()-((60*60)*24));

if(!file_put_contents('/home/aces/tmp/epg_progress', 0))
    quit("Could not write progress to file.");

if(!is_dir("/home/aces/guide/")) {
    if(!mkdir("/home/aces/guide/",0777 ))
        quit("Could not make guide directory.");
}
if(!$ofile = fopen('/home/aces/tmp/output_guide.xml','w'))
    quit('No permissions to write output file.');

echo "DOWNLOADING SOURCES.\n";
$r=$DB->query("SELECT * FROM iptv_epg_sources WHERE status = 1 ");

while($row=$r->fetch_assoc()) {

    $DB->query("UPDATE iptv_epg_sources SET error_msg = '' WHERE id = {$row['id']} ");

    try {
        $EpgSource = new \ACES2\IPTV\EpgSource($row['id']);
        $EpgSource->updateTvgID2();
        $DB->query("UPDATE iptv_epg_sources SET last_update = NOW() WHERE id = {$row['id']} ");
    } catch(\Exception $e) {
        echo "Error Updating tvg ids for {$row['name']} : {$e->getMessage()}\n";
        $DB->query("UPDATE iptv_epg_sources SET error_msg = '{$e->getMessage()}' WHERE id = {$row['id']} ");
        $ignore = 1;
    }

}

file_put_contents('/home/aces/tmp/epg_progress', 1);

//DELETE EVERYTHING OLDER THAN A MONTH.
$DB->query("DELETE FROM iptv_epg WHERE end_time < UNIX_TIMESTAMP( NOW() - INTERVAL 1 MONTH ) ");


fwrite($ofile,'<?xml version="1.0" encoding="UTF-8"?>');
fwrite($ofile,"\n");
fwrite($ofile,'<!DOCTYPE tv SYSTEM "xmltv.dtd">');
fwrite($ofile,"\n\n");
fwrite($ofile,'<tv source-info-name="Aces Panel" generator-info-name="aces" >');
fwrite($ofile,"\n");

$TOTAL_CHANNS=0;
$PROGRESS_CHANNELS=0;

$rc = $DB->query("SELECT id,name,tvg_id FROM iptv_channels WHERE tvg_id != '' GROUP BY tvg_id ");
while($row=$rc->fetch_assoc()) { 
    
    $row['name'] =  str_replace('&','&amp;',$row['name']);
    $row['tvg_id'] = str_replace('&','&amp;',$row['tvg_id']);

    $data[] = $row;
    
    if($row['tvg_id']) { 
        
        $TOTAL_CHANNS ++;

        //$name = htmlspecialchars($row['name'], ENT_XML1, 'UTF-8');
        //$row['name'] = str_replace('&','&amp;',$row['name']);
        //$row['tvg_id'] = str_replace('&','&amp;',$row['tvg_id']);
        
        //$tvg_id =  str_replace('&','&amp;',$row['tvg_id']);
        //$name =  str_replace('&','&amp;',$row['name']);
        
        fwrite($ofile,"<channel id=\"{$row['tvg_id']}\">\n");
        fwrite($ofile,"     <display-name>{$row['name']}</display-name>\n");
        fwrite($ofile,"</channel>\n");
    }
    
}



$lprogress = 0;
foreach($data as $i => $v ) { 
    
    if($data[$i]['tvg_id']) { 
        
        $ini=0;$len=0;
        $programme;
        $chunkSize = 1024 * 5;
        
        $no_more_programme = 0;
        
        $added_progs=array();
        
        //for each imported guide.
        foreach (glob("/home/aces/imported_guides/*.xml") as $filename) { 

            $handler = fopen($filename,'r');
 
            while (!feof($handler)) {
                $buffer = fread($handler, 1024 * 5 );
                while( true ) { 

                    try {
                        $b = 0;
                        strpos($buffer, '<programme',($len+$ini));
                        if(!$ini = @strpos($buffer, '<programme',($len+$ini))) {  break; }
                    } catch( \ValueError $exp ) {
                        $b =1;
                    }

                    if($b) break;

                    while(  (!$len = (strpos($buffer, '>', $ini))) && !feof($handler) )
                        { $buffer = substr($buffer, $ini).fread($handler, $chunkSize ); $ini=0;  }

                    //COUND NOT FOUND ANYTHING??
                    if(!$len) {  quit('NOTHING FOUND'); }

                    $len = ($len - $ini) + strlen('>');
                    $tag = substr($buffer, $ini, $len);
                    
                    //echo "{$tag}\n\n\n{$data[$i]['tvg_id']}";
                    
                    if(strpos($tag,'"'.$data[$i]['tvg_id'].'"')) { 
                        
                            while(  (!$len = (strpos($buffer, '</programme>', $ini))) && !feof($handler) ) { $buffer = substr($buffer, $ini).fread($handler, $chunkSize ); $ini=0; }

                            //COUND NOT FOUND ANYTHING??
                            if(!$len) { quit("NOTHING"); }


                            $len = ($len - $ini) + strlen('</programme>');

                            //GETTING XML PROGRAMME.
                            //$xml = utf8_encode(sanitizeXML(substr($buffer, $ini, $len)));
                            $xml = substr($buffer, $ini, $len);
                            
                            //echo "$xml\n\n\n";
                            
                            //$xml = mb_convert_encoding(substr($buffer, $ini, $len), 'UTF-8'  );
                            //$xml = iconv( mb_detect_encoding($xml),  'UTF-8',  $xml);
                            
                            //$xml=iconv(mb_detect_encoding($xml, mb_detect_order(), true), "UTF-8", $xml);
                            
                            //$xml = substr($buffer, $ini, $len);
                            //$xml = htmlspecialchars($xml, ENT_XML1, 'UTF-8');
                            
                            
                            //CONVERTING XML TO JSON TO EASY MANAGE.
                            $xml_obj = simplexml_load_string( $xml );
                            $array = json_decode(json_encode($xml_obj),TRUE);
                            
                            //NOW GETTING TO OFSET OF PROGRAMME AND CONVERTING INTO SECONDS.
                            $guide_offset = (int)explode(' ',$array['@attributes']['start'])[1];
                            $guide_offset = (($guide_offset/100)*60)*60;
                            
                            $client_time_offset =0;
			                if($client_time_offset < 0 && $guide_offset < 0 ) $offset = $client_time_offset - $guide_offset;
                            else { $offset = $client_time_offset - $guide_offset; } 
                            
			                if($server_offset < 0 && $guide_offset < 0 ) $to_server_offset = $server_offset - $guide_offset;
                            else { $to_server_offset = $server_offset - $guide_offset; } 
                            
                            //GETTING PROGRAMME TIME.
                            $offset = 0;
                            $prog_start = date('YmdHis', strtotime( explode(' ',$array['@attributes']['start'])[0] ) + $offset  );
                            $prog_end = date('YmdHis', strtotime( explode(' ',$array['@attributes']['stop'])[0] ) + $offset  );
                            
                            $start = date('YmdHis', strtotime( explode(' ',$array['@attributes']['start'])[0] ) + $to_server_offset  );
                            $end = date('YmdHis', strtotime( explode(' ',$array['@attributes']['stop'])[0] ) + $to_server_offset  );
                            
                            $start_time = strtotime($start);
                            $end_time = strtotime($end);

                            //$o_start = explode(' ',$array['@attributes']['start'])[0];
                            //$o_end =  explode(' ',$array['@attributes']['stop'])[0];
                                                        
                            //LET ONLY WRITE IF PROGRAMME HAVENT EXPIRE AND IS ON LIMIT.
                            if(  $DATE < $prog_end && $LIMIT_DATE > $prog_start && !in_array($prog_start,$added_progs) ) { 
                            
                                $xml = str_replace('&','&amp;',$xml);
                                $xml = str_replace('quot;','',$xml);
                                $xml = str_replace('&','',$xml);
                                
                                fwrite($ofile, $xml); 
                                fwrite($ofile,"\n");
                                $added_progs[] = $prog_start;

                                //TODO SOME SOURCE GIVING $array['title'] AS ARRAY.
                                $title= !is_array($array['title']) ? $DB->escape_string($array['title']) : '';
                                $desc = !is_array($array['desc']) ? $DB->escape_string($array['desc']) : '';


                                //$repg=$DB->query("SELECT chan_id FROM iptv_epg WHERE chan_id = {$data[$i]['id']} AND start_date = '$start' ");
//                                $repg=$DB->query("SELECT chan_id FROM iptv_epg WHERE tvg_id = '{$data[$i]['tvg_id']}' AND start_date = '$start' ");
//                                if(!mysqli_fetch_array($repg)) { 
//                                    $rce = $DB->query("SELECT id FROM iptv_channels WHERE tvg_id = '{$data[$i]['tvg_id']}' ");
//                                    while($rce_id=mysqli_fetch_array($rce)['id'])
//                                        $DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date) VALUES('$rce_id','{$data[$i]['tvg_id']}','$title','$desc','$start','$end')  ;  ");
//                                }
//                                
                                //SELECTING ALL CHANNEL WITH THIS TVG_ID
                                $repg=$DB->query("SELECT id FROM iptv_channels WHERE tvg_id = '{$data[$i]['tvg_id']}'"); 
                                while($rce_id=mysqli_fetch_assoc($repg)['id']) { 
                                    $repg2=$DB->query("SELECT chan_id FROM iptv_epg WHERE chan_id = $rce_id AND start_date = '$start' ");
                                    if(!mysqli_fetch_array($repg2)) { 
                                        //$DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date,start_time,end_time) VALUES('$rce_id','{$data[$i]['tvg_id']}','$title','$desc','$start','$end','$start_time','$end_time')  ;  ");
                                        $DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date,start_time,end_time) 
                                            VALUES('$rce_id','{$data[$i]['tvg_id']}','$title','$desc','$start','$end','$start_time','$end_time');  ");
                                        
                                        
                                    }
                                }
                                

                                
                                
                                
                            }

                            flush();
                    }

                }

            }
        }
    }
    
    $PROGRESS_CHANNELS++;
    //$progress = 1 + round(  ($PROGRESS_CHANNELS / $TOTAL_CHANNS) * 100 );
    $progress = 10 + round(  ($PROGRESS_CHANNELS / ($TOTAL_CHANNS + ($TOTAL_CHANNS * 2.5 ))  ) * 100 );
    if($lprogress != $progress) { 
        echo "%$progress DB\n";
        file_put_contents('/home/aces/tmp/epg_progress', $progress);
        $lprogress = $progress;
    }
    
    
}

$r=$DB->query("SELECT value FROM settings WHERE name = lower('iptv.EpgBuildVideosEpg') ");
if(mysqli_fetch_array($r)['value']) {

//if($DB->get_setting('iptv.EpgBuildVideosEpg')) { 
    
    echo "BUILDING VIDEOS EPG.\n";

    $rm = $DB->query("SELECT id,name,about,year,genre1,genre2,genre3 FROM iptv_ondemand ");
    while($row=mysqli_fetch_array($rm)) { 
        
        $row['name'] = str_replace("&",'and',$row['name']);
        $row['about'] = str_replace("&",'and',$row['about']);
        $row['genre1'] = str_replace("&",'and',$row['genre1']);
        $row['genre2'] = str_replace("&",'and',$row['genre2']);
        $row['genre3'] = str_replace("&",'and',$row['genre3']);
        
        $row['name'] = utf8_encode($row['name']);
        $row['about'] = utf8_encode($row['about']);
        $row['genre1'] = utf8_encode($row['genre1']);
        $row['genre2'] = utf8_encode($row['genre2']);
        $row['genre3'] = utf8_encode($row['genre3']);
        
        $row['name'] = sanitizeXML($row['name']);
        $row['about'] = sanitizeXML($row['about']);
        $row['genre1'] = sanitizeXML($row['genre1']);
        $row['genre2'] = sanitizeXML($row['genre2']);
        $row['genre3'] = sanitizeXML($row['genre3']);

        fwrite($ofile,"<channel id=\"aces-movie-id-{$row['id']}\">\n");
        fwrite($ofile,"     <display-name>{$row['name']}</display-name>\n");
        fwrite($ofile,"</channel>\n");

        fwrite($ofile,"<programme start=\"".date('YmdHis')." +0000\" stop=\"20300000000000 +0000\" channel=\"aces-movie-id-{$row['id']}\">\n");
        fwrite($ofile,"<title lang=\"en\">{$row['name']}</title>\n");
        fwrite($ofile,"<desc lang=\"en\">{$row['about']}</desc>\n");
        if($row['genre1']) fwrite($ofile,"<category lang=\"en\">{$row['genre1']}</category>\n");
        if($row['genre2']) fwrite($ofile,"<category lang=\"en\">{$row['genre2']}</category>\n");
        if($row['genre3']) fwrite($ofile,"<category lang=\"en\">{$row['genre3']}</category>\n");
        fwrite($ofile,"</programme>\n");
    }
}

//BUILDING GUIDE FOR 24/7 CHANNELS
$d='+';
$offset_server = ((date('Z')/60)/60)*100;
if($offset_server<0){ $d = '-';  $offset_server = str_replace('-','',$offset_server); } 
if(strlen($offset_server) == 3 ) $offset_server = "0".$offset_server;
$offset_server = $d.$offset_server;


$r0=$DB->query("SELECT c.id,c.name FROM iptv_channels c RIGHT JOIN iptv_streaming s ON s.chan_id = c.id WHERE c.type = 1 ");
while($crow=mysqli_fetch_assoc($r0)) {
    
    $crow['name']=str_replace("&",'and',$crow['name']); 
    //$crow['name']=utf8_encode($crow['name']);
    $crow['name']=sanitizeXML($crow['name']);
    
    fwrite($ofile,"<channel id=\"aces-channel-{$crow['id']}\">\n");
    fwrite($ofile,"     <display-name>{$crow['name']}</display-name>\n");
    fwrite($ofile,"</channel>\n");
    
    $re=$DB->query("SELECT title,description,start_time,end_time FROM iptv_epg WHERE chan_id = '{$crow['id']}'  ");
    while($rowe=mysqli_fetch_assoc($re)) { 
        
        //$rowe['description'] = str_replace("&",'and',$rowe['description']);
        //$rowe['description'] = utf8_encode($rowe['description']);
        //$rowe['description'] = sanitizeXML($rowe['description']);
        
        $rowe['title'] = str_replace("&","&amp;",$rowe['title']);
        if($offset_server == '0')
            $offset_server = "+0000";
        
        fwrite($ofile,"<programme start=\"".date('YmdHis',$rowe['start_time'])." $offset_server\" stop=\"".date('YmdHis',$rowe['end_time'])." $offset_server\" channel=\"aces-channel-{$crow['id']}\">\n");
        fwrite($ofile,"<title lang=\"en\">{$rowe['title']}</title>\n");
        fwrite($ofile,"<desc lang=\"en\">{$rowe['description']}</desc>\n");
        fwrite($ofile,"<icon></icon>\n");
        fwrite($ofile,"</programme>\n");
        
    }
    
}
    
    
fwrite($ofile,"</tv>\n");
fclose($ofile);


echo "BUILDING API EPG.\n";

$epg = new EgpParser('/home/aces/tmp/output_guide.xml');
$file = "/home/aces/guide/guide.json";
$r = $DB->query("SELECT id,name,tvg_id FROM iptv_channels where tvg_id != ''  ");

$lprogress=0;
$PROGRESS_CHANNELS=0;

while($row=$r->fetch_assoc()) {
    $epg_id=NULL;
    if($row['tvg_id']) $epg_id = $row['tvg_id'];
    //else $epg_id = $epg->findChannelByName( $row['name'] );

    try {
        if($epg_id && is_array($json) ) {
            $json['epg'][$row['id']] = $epg->getProgramme($epg_id);
            foreach ( $json['epg'][$row['id']] as $i => $v ) {
                #var_dump($json['epg'][$row['id']][$i]);die;
                unset($json['epg'][$row['id']][$i]['category'],$json['epg'][$row['id']][$i]['date'],$json['epg'][$row['id']][$i]['new']);
                unset($json['epg'][$row['id']][$i]['credits'],$json['epg'][$row['id']][$i]['episode-num'],$json['epg'][$row['id']][$i]['previously-shown'],$json['epg'][$row['id']][$i]['subtitles'],$json['epg'][$row['id']][$i]['rating'],$json['epg'][$row['id']][$i]['audio']);}
        }

        $PROGRESS_CHANNELS++;
        //$progress = 1 + round(  ($PROGRESS_CHANNELS / $TOTAL_CHANNS) * 100 );
        $progress = 50 + round(  ($PROGRESS_CHANNELS / ($TOTAL_CHANNS + ($TOTAL_CHANNS * 2.5 ))  ) * 100 );
        if($lprogress != $progress) {
            echo "%$progress \n";
            file_put_contents('/home/aces/tmp/epg_progress', $progress);
            $lprogress = $progress;
        }

        $text = json_encode($json['epg']);
        //$text = iconv(mb_detect_encoding($text), "UTF-8", $text);
        #$text = iconv("utf-8", "utf-8//ignore", $text
        file_put_contents($file,  $text  );

    }catch (TypeError $exp ) {
        $a = 0;
    }

}

echo "Compressing files.\n";

unlink('/home/aces/guide/guide.xml');
if(is_file('/home/aces/guide/guide.xml'))
    echo "FAIL TO REMOVE GUIDE.XML\n";

//if($DB->get_setting('iptv.EpgBuildGZ') == 1 ) {  
$r=$DB->query("SELECT value FROM settings WHERE name = lower('iptv.EpgBuildGZ') ");
if(mysqli_fetch_array($r)['value']) {
    
    if(is_file('/home/aces/guide/guide.xml.gz')) {
        if(!unlink('/home/aces/guide/guide.xml.gz')) { quit("Could not write on /home/aces/gudie/guide.xml.gz. Make sure you have privileges."); }
    }
    copy('/home/aces/tmp/output_guide.xml',"/home/aces/guide/guide.xml"); exec('cd /home/aces/guide/; gzip  /home/aces/guide/guide.xml'); 
    
//} if($DB->get_setting('iptv.EpgBuildZIP') == 1 ) {
} 
$r=$DB->query("SELECT value FROM settings WHERE name = lower('iptv.EpgBuildZIP') ");
if(mysqli_fetch_array($r)['value']) {
    
    if(is_file('/home/aces/guide/guide.zip'))
        if(!unlink('/home/aces/guide/guide.zip')) quit('Could not write on /home/aces/guide/guide.zip. Make Sure you have privileges.'); 
    copy('/home/aces/tmp/output_guide.xml',"/home/aces/guide/guide.xml"); exec('cd /home/aces/guide/; zip  /home/aces/guide/guide.zip  /home/aces/guide/guide.xml');


    
//ALWAYS BUILDING XML, IT NEEDED FOR API
//} if($DB->get_setting('iptv.EpgBuildXML') == 1 ) {
} if( TRUE ) {
    
//    if(is_file('/home/aces/guide/guide.xml')) { 
//        unlink('/home/aces/guide.xml');
//        if(is_file('/home/aces/guide/guide.xml')) quit('Could not write on /home/aces/guide/guide.xml. Please make sure you have privileges.');
//    }
    copy('/home/aces/tmp/output_guide.xml',"/home/aces/guide/guide.xml");

}

exec("chown aces /home/aces/guide/*");


