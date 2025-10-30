<?php

require_once "/home/aces/panel/ACES2/DB.php";
require_once "/home/aces/panel/ACES2/Curl.php";
require_once "/home/aces/panel/ACES2/IPTV/Stream.php";
require_once "/home/aces/panel/ACES2/IPTV/XCAPI/XCAccount.php";
require_once "/home/aces/panel/ACES2/IPTV/XCAPI/Stream.php";
require_once "/home/aces/panel/ACES2/IPTV/LeagueTeams.php";
require_once "/home/aces/panel/ACES2/IPTV/SMARKETS/Smarkets.php";
require_once "/home/aces/panel/ACES2/IPTV/SMARKETS/Event.php";

use ACES2\IPTV\XCAPI\XCAccount;

set_time_limit(-1);

#require "/home/aces/panel/ACES2/init.php";


$db = new \ACES2\DB;

if(!$event_id  = (int)$argv[1])
error_log("Scanning Events $event_id");

$r_events = $db->query("SELECT * FROM iptv_dynamic_events WHERE id = $event_id ");
if(!$Event = $r_events->fetch_assoc() )
    exit;


$EVENT_TYPE = $Event['type'];
$Providers = json_decode($Event['providers'], true);
$HideChannels = (bool)$Event['hide_streams_on_no_event'];
$r = $db->query("SELECT s.id FROM iptv_channels s 
WHERE s.event_id = '$event_id' AND 
    ( SELECT e.id FROM iptv_stream_events e WHERE s.id = e.stream_id AND e.end_date > NOW() LIMIT 1 ) IS NULL;    
");

//CLEARING OLD EVENTS.
$sql_hide = $HideChannels ? ', enable = 0 ' : '';
while ($row = $r->fetch_assoc()) {
    $db->query("DELETE FROM iptv_stream_events WHERE stream_id = '{$row['id']}'");
    $db->query("UPDATE iptv_channels SET name = '$EVENT_TYPE : No event' $sql_hide WHERE id = '{$row['id']}'");
    $db->query("DELETE FROM iptv_epg WHERE chan_id = '{$row['id']}'");
}

//GETTING Smarkets Events
$Smarkets = new \ACES2\IPTV\SMARKETS\Smarkets();
$SmarketsGames = $Smarkets->getEvents($EVENT_TYPE);
$Teams = \ACES2\IPTV\LeagueTeams::getTeams($EVENT_TYPE);

$StreamsToRestart = [];

foreach ($Providers as $provider_id) {

    try {

        $XCAccount = new XCAccount($provider_id);
        $json = $XCAccount->getLiveStreams();

        echo "Getting Streams From Provider $XCAccount->host\n";

        foreach ($json as $s) {

            $Stream = new ACES2\IPTV\XCAPI\Stream($s, $XCAccount->id);
            $sname = strtoupper($Stream->name);


            if (strpos($sname, $EVENT_TYPE) !== false) {

                $team1 = null;
                $team2 = null;

                foreach ($Teams as $team => $team_name) {

                    $team = strtoupper($team);

                    if (strpos($sname, $team) !== false) {

                        if (is_null($team1)) {
                            $team1 = $team;
                            $team1_name = $team_name;
                        } else {
                            $team2 = $team;
                            $team2_name = $team_name;
                        }

                        if (!is_null($team1) && !is_null($team2)) {
                            echo "FOUND MATCH1 $team1 vs $team2 $sname\n";

                            $r = $db->query("SELECT id,stream_id FROM iptv_stream_events 
                            WHERE team1 = '$team1' AND team2 = '$team2' AND type = '$EVENT_TYPE' AND end_date > NOW()
                               OR team1 = '$team2' AND team2 = '$team1' AND type = '$EVENT_TYPE' AND end_date > NOW()
                        ");

                            if ($stream_id = $r->fetch_assoc()['stream_id']) {
                                //FOUND EVET LET ADD STREAM URL IF DOESN'T EXIST.
                                echo "\tEVENT EXIST\n";
                                $r_url = $db->query("SELECT chan_id FROM iptv_channels_sources WHERE chan_id = '$stream_id' 
                                  AND url = '{$Stream->getStreamUrl()}' ");
                                if ($r_url->num_rows < 1) {
                                    echo "\tADDING SOURCE\n";
                                    $db->query("INSERT INTO iptv_channels_sources (chan_id, priority, url, enable) 
                                    VALUES('$stream_id',1, '{$Stream->getStreamUrl()}', 1)");

                                    if(!in_array($stream_id, $StreamsToRestart))
                                        $StreamsToRestart[] = $stream_id;

                                }

                            } else {

                                echo "FOUND MATCH2 $team1 vs $team2\n";

                                //CHECK IF THERE STREAM WITHOUT ANY EVENT SET YET.
                                $r_stream_event = $db->query("SELECT s.id FROM iptv_channels s 
                                WHERE s.event_id = '$event_id' AND 
                                    ( SELECT e.id FROM iptv_stream_events e WHERE s.id = e.stream_id AND e.end_date > NOW() LIMIT 1 ) IS NULL;    
                            ");

                                if ($row = $r_stream_event->fetch_assoc()) {

                                    //LET FIND EVENT IN SMARKETS FOR MORE INFO ON THE EVENT AND MAKING SURE IS A TODAY
                                    //EVENT AND NOT OLD FROM THE PORTAL.
                                    $found = false;
                                    foreach ($SmarketsGames as $smarket_game) {
                                        if ($smarket_game->team1 == $team1 && $smarket_game->team2 == $team2
                                            or $smarket_game->team1 == $team2 && $smarket_game->team2 == $team1) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if ($found) {
                                        echo "\tADDING EVENT\n";

                                        $from_tz = '+00:00'; //SMARKET OFFSET IS FIXED.
                                        $to_tz = date("P"); //DEFAULT ACES2 PHP IS IN UTF AND MYSQL IS ON SYSTEM
                                                                  //TIMEZONE THIS COULD GIVE BAD TIME GETTING IT FROM PHP

                                        //REMOVING OLD EVENTS FROM STREAM
                                        $db->query("DELETE FROM iptv_stream_events WHERE stream_id = '{$row['id']}'");

                                        $db->query("INSERT INTO iptv_stream_events (stream_id,sk_id,type,team1,team2,start_datetime,start_date) 
                                            VALUES('{$row['id']}', '$smarket_game->id', '$EVENT_TYPE', '$team1', '$team2', CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz'),
                                                   CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz')
                                           )"
                                        );

                                        //SET END DAY A DAY AFTER IT START.
                                        $insert_id = $db->insert_id;
                                        $db->query("UPDATE iptv_stream_events 
                                                SET end_date = start_datetime + INTERVAL 1 DAY, 
                                                    end_datetime = start_datetime + INTERVAL 4 HOUR
                                        WHERE id = '{$insert_id}' ");

                                        $r_start_date = $db->query("SELECT start_datetime FROM iptv_stream_events WHERE stream_id = '{$row['id']}'");
                                        $start_datetime = $r_start_date->fetch_assoc()['start_datetime'];
                                        $start_date = date("D H:i T", strtotime($start_datetime));

                                        //ADDING EPG
                                        $db->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,start_time,end_date,end_time)
                                            VALUES('{$row['id']}','aces-channel-{$row['id']}', 'No Event', 
                                                   '$EVENT_TYPE : $team1_name @ $team2_name',
                                                   NOW(), 
                                                   UNIX_TIMESTAMP(  ),
                                                   CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz'),
                                                   UNIX_TIMESTAMP( CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz') )
                                           )
                                       ");

                                        $db->query("INSERT INTO iptv_epg (chan_id,tvg_id,title,description,start_date,start_time,end_date,end_time)
                                            VALUES('{$row['id']}','aces-channel-{$row['id']}', '$EVENT_TYPE : $team1_name @ $team2_name', 
                                                   '$EVENT_TYPE : $team1_name @ $team2_name',
                                                   CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz'), 
                                                   UNIX_TIMESTAMP( CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz') ),
                                                   CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz') + INTERVAL 4 HOUR,
                                                   UNIX_TIMESTAMP( CONVERT_TZ('$smarket_game->start_datetime', '$from_tz', '$to_tz') + INTERVAL 4 HOUR )
                                           )
                                       ");

                                        //UPDATING STREAM NAME & TVG ID
                                        $db->query("UPDATE iptv_channels 
                                            SET name = '$EVENT_TYPE : $team1_name @ $team2_name - $start_date',
                                                enable = 1, tvg_id = 'aces-channel-{$row['id']}'
                                            WHERE id = '{$row['id']}'");

                                        if(!in_array($stream_id, $StreamsToRestart))
                                            $StreamsToRestart[] = $stream_id;


                                        //CLEARING OLD SOURCES.
                                        $db->query("DELETE FROM iptv_channels_sources WHERE chan_id = '{$row['id']}'");

                                        echo "\tCLEARING AND ADDING FIRST SOURCE\n";
                                        $db->query("INSERT INTO iptv_channels_sources (chan_id, priority, url, enable) 
                                            VALUES('{$row['id']}',1, '{$Stream->getStreamUrl()}', 1)");
                                    }

                                }

                            }


                        }

                    }

                }

            }

        }

    } catch (\Exception $e) {
        logE($e->getMessage());
        $ignore = true;
    }
}

foreach($StreamsToRestart as $stream_id) {
    try {
        $Stream = new \ACES2\IPTV\Stream($stream_id);
        echo "Restarting $Stream->id\n";
        //$Stream->restart();
    } catch (\Exception $e) {
        $ignore = 1;
    }

}


if(count($StreamsToRestart ) > 0) {
    echo "BUILDING GUIDE\n";
    exec("php /home/aces/bin/iptv_build_guide.php > /dev/null &");
}


echo "\nFinished\n";