<?php

$server_offset = date('Z');
$PidFile = "/home/aces/run/aces_build_guide.pid";
ini_set("error_log", "/home/aces/logs/aces_build_guide.log");
ini_set("log_errors", 1);
chdir("/home/aces/panel");
set_time_limit ( 0 );

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

function quit($msg = '') {
    global $PidFile;
    if($msg) error_log($msg."\n");
    unlink($PidFile);
    unlink("/home/aces/tmp/epg_progress");
    unlink("/home/aces/tmp/output_guide.xml");
    unlink($PidFile);
    die($msg);
}

function setProgress($progress) {
    file_put_contents('/home/aces/tmp/epg_progress', $progress);
}

register_shutdown_function('quit');

use ACES2\IPTV\EpgSource;


include "/home/aces/panel/ACES2/DB.php";
include "/home/aces/panel/ACES2/IPTV/EpgSource.php";
include "/home/aces/panel/ACES2/Settings.php";
include "/home/aces/panel/ACES2/IPTV/Settings.php";

$DB = new \ACES2\DB;

$LIMIT_PROGRAMME_HOUR = \ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::EPG_LENGTH);
$BUILD_GZ = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::EPG_BUILD_GZIP);
$BUILD_ZIP = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::EPG_BUILD_ZIP);
$BUILD_XML = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::EPG_BUILD_XML);

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

if(is_file($PidFile)) {

    $PID = file_get_contents($PidFile);
    if(posix_getpgid($PID))
        quit("Another importer process is running please wait until it finished.");

} else {

    if(!file_put_contents($PidFile,getmypid()))
        quit('Could not write pid file.');

}


//DELETE EVERYTHING OLDER THAN A MONTH.
$DB->query("DELETE FROM iptv_epg WHERE end_time < UNIX_TIMESTAMP( NOW() - INTERVAL 1 MONTH ) ");


fwrite($ofile,'<?xml version="1.0" encoding="UTF-8"?>');
fwrite($ofile,"\n");
fwrite($ofile,'<!DOCTYPE tv SYSTEM "xmltv.dtd">');
fwrite($ofile,"\n\n");
fwrite($ofile,'<tv source-info-name="Aces Panel" generator-info-name="aces" >');
fwrite($ofile,"\n");


$rc = $DB->query("SELECT id,name,tvg_id FROM iptv_channels WHERE tvg_id != '' AND event_id = 0 GROUP BY tvg_id ");
while($row=$rc->fetch_assoc()) {
    $channel_name = str_replace('&','&amp;',$row['name']);
    $tvg_id = str_replace('&','&amp;',$row['tvg_id']);

    fwrite($ofile,"<channel id=\"{$tvg_id}\">\n");
    fwrite($ofile,"     <display-name>{$channel_name}</display-name>\n");
    fwrite($ofile,"</channel>\n");

}


$r=$DB->query("SELECT * FROM iptv_epg_sources WHERE status = 1 ");
$TOTAL_EPG = $r->num_rows;
$CURRENT_PROGRESS = 0;

while($row=$r->fetch_assoc()) {
    $CURRENT_PROGRESS++;

    echo "DOWNLOADING EPG {$row['name']}\n";

    $DB->query("UPDATE iptv_epg_sources SET error_msg = '' WHERE id = {$row['id']} ");

    try {
        $EpgSource = new \ACES2\IPTV\EpgSource($row['id']);
        $EpgSource->updateTvgID2();

        echo "IMPORTING PROGRAMME FROM {$row['name']}\n";

        $handler = fopen($EpgSource->xml_file, "r");
        while (($line = fgets($handler)) !== false) {

            if(str_contains($line, '<programme')) {
                $string_xml = $line;

                while (($line = fgets($handler)) ) {
                    if($line == false) {
                        $string_xml = '';
                        break; //END OF FILE SHOULD EXIST HERE.
                    }

                    $string_xml .=  $line;

                    if(str_contains($line, '</programme>'))
                        break;
                }

                //WE DON'T FOUND END ENDING PROGRAMME TAG LET SKIP FILE.
                if(!$string_xml)
                    break;

                $xml_obj = simplexml_load_string( $string_xml );
                $array = json_decode(json_encode($xml_obj),TRUE);
                $tvg_id = $DB->escString($array['@attributes']['channel']);

                $r_chan=$DB->query("SELECT id FROM iptv_channels WHERE lower(tvg_id) = lower('$tvg_id') ");
                while($channel = $r_chan->fetch_assoc()) {

                    $channel_id = $channel['id'];

                    //NOW GETTING TO OFFSET OF PROGRAMME AND CONVERTING INTO SECONDS.
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

                    if(  $DATE < $prog_end && $LIMIT_DATE > $prog_start ) {

                        //THIS WAS ON OLD SCRIPT DON'T THINK IS NECESSARY
//                    $xml = str_replace('&','&amp;',$xml);
//                    $xml = str_replace('quot;','',$xml);
//                    $xml = str_replace('&

                        fwrite($ofile, $string_xml);
                        fwrite($ofile,"\n");

                        //TODO SOME SOURCE GIVING $array['title'] AS ARRAY.
                        $title= !is_array($array['title']) ? $DB->escape_string($array['title']) : '';
                        $desc = !is_array($array['desc']) ? $DB->escape_string($array['desc']) : '';

                        //ADD PROGRAMME IF DOES NOT EXIST YET.
                        $r_epg=$DB->query("SELECT chan_id FROM iptv_epg WHERE chan_id = '$channel_id' AND start_date = '$start' ");
                        if($r_epg->num_rows < 1)
                            $DB->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,end_date,start_time,end_time) 
                                        VALUES('$channel_id','{$tvg_id}','$title','$desc','$start','$end','$start_time','$end_time');  ");


                    }

                }

            }

        }


    } catch(\Exception $e) {
        echo "Error updating epg {$row['name']} : {$e->getMessage()}\n";
        $DB->query("UPDATE iptv_epg_sources SET error_msg = '{$e->getMessage()}' WHERE id = {$row['id']} ");
        $ignore = 1;
    }

    $DB->query("UPDATE iptv_epg_sources SET last_update = NOW() WHERE id = {$row['id']} ");
    setProgress($CURRENT_PROGRESS / $TOTAL_EPG * 100);

}




//BUILDING GUIDE FOR 24/7 CHANNELS
$d='+';
$offset_server = ((date('Z')/60)/60)*100;
if($offset_server<0){ $d = '-';  $offset_server = str_replace('-','',$offset_server); }
if(strlen($offset_server) == 3 ) $offset_server = "0".$offset_server;
$offset_server = $d.$offset_server;

echo "OFFSET SERVER: ".$offset_server."\n";

// 24/7 Channels
$r0=$DB->query("SELECT c.id,c.name FROM iptv_channels c 
    RIGHT JOIN iptv_streaming s ON s.chan_id = c.id WHERE c.type = 1 ");

//BUILDING GUIDE FOR STREAM EVENT.
$r00=$DB->query("SELECT s.id,name FROM iptv_channels s
        WHERE event_id > 0
");

$Streams = $r0->fetch_all(MYSQLI_ASSOC);
$Streams = array_merge($Streams,$r00->fetch_all(MYSQLI_ASSOC));


foreach($Streams as $crow) {

    $crow['name']=str_replace("&",'and',$crow['name']);
    //$crow['name']=utf8_encode($crow['name']);
    $crow['name']=sanitizeXML($crow['name']);

    fwrite($ofile,"<channel id=\"aces-channel-{$crow['id']}\">\n");
    fwrite($ofile,"     <display-name>{$crow['name']}</display-name>\n");
    fwrite($ofile,"</channel>\n");

    echo "FOUND PROGRAMME FOR CHANNEL {$crow['id']}\n";

    $re=$DB->query("SELECT title,description,start_time,end_time FROM iptv_epg 
                                             WHERE chan_id = '{$crow['id']}'  ");
    while($rowe=$re->fetch_assoc()) {


        echo "TITLE : {$rowe['title']}\n";

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


//while($row=$r->fetch_assoc()) {
//
//    fwrite($ofile,"<channel id=\"aces-channel-{$crow['id']}\">\n");
//    fwrite($ofile,"     <display-name>{$row['name']}</display-name>\n");
//    fwrite($ofile,"</channel>\n");
//
//}


fwrite($ofile,"</tv>\n");
fclose($ofile);

echo "COMPRESSING FILES.\n";

rename("/home/aces/tmp/output_guide.xml", "/home/aces/tmp/guide.xml");

unlink('/home/aces/guide/guide.xml');
unlink('/home/aces/guide/guide.xml.gz');
unlink('/home/aces/guide/guide.zip');

if($BUILD_XML) {

    echo "MOVING XML FILES.\n";

    unlink('/home/aces/guide/guide.xml');
    if(is_file('/home/aces/guide/guide.xml')) {
        error_log("UNABLE TO REMOVE OLD guide.xml");
    } else
        copy("/home/aces/tmp/guide.xml","/home/aces/guide/guide.xml");
}

if($BUILD_GZ) {

    echo "GZIP FILE\n";

    if(is_file('/home/aces/guide/guide.xml.gz'))
        error_log("UNABLE TO REMOVE OLD guide.xml.gz" );
    else {
        exec("gzip -k -f  /home/aces/tmp/guide.xml");
        copy("/home/aces/tmp/guide.xml.gz","/home/aces/guide/guide.xml.gz");
    }

}

if($BUILD_ZIP) {

    if(is_file('/home/aces/guide/guide.zip'))
        error_log("UNABLE TO REMOVE OLD guide.zip" );
    else {
        exec("zip /home/aces/guide/guide.zip /home/aces/tmp/guide.xml");
    }

}

unlink("/home/aces/tmp/guide.xml");

echo "FINISHED\n";
