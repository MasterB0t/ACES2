<?php

error_reporting(0);

include "/home/aces/stream/config.php";

$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) { logfile('ERROR: COULD NOT CONNECT TO DATABASE.'); DIE; } 

$r0=$DB->query("SELECT c.id FROM iptv_channels c RIGHT JOIN iptv_streaming s ON s.chan_id = c.id WHERE c.type = 1 ");
while($crow=mysqli_fetch_assoc($r0)) { 

    $r1=$DB->query("SELECT id,status,file_id FROM iptv_channel_files WHERE channel_id = '{$crow['id']}' ORDER BY ordering ASC ");
    while($row=mysqli_fetch_assoc($r1)) {    

        $r2=$DB->query("SELECT o.name,o.about,o.runtime_seconds,e.title,e.about as episode_about,e.number as episode_number,s.number as season_number, f.duration,f.movie_id,f.episode_id FROM iptv_video_files f  "
                . " LEFT JOIN iptv_series_season_episodes e ON e.id = f.episode_id"
                . " LEFT JOIN iptv_series_seasons s ON s.id = e.season_id "
                . " LEFT JOIN iptv_ondemand o ON o.id = f.movie_id OR s.series_id = o.id "
                . " WHERE f.id = {$row['file_id']} ");

        while($row_epg=$r2->fetch_assoc()) {  

                $name='';$about='';
                if($row_epg['movie_id']) { $name = $DB->escape_string($row_epg['name']); $about = $DB->escape_string($row_epg['about']); }
                else { $name = $DB->escape_string("{$row_epg['name']} S{$row_epg['season_number']} E{$row_epg['episode_number']} {$row_epg['title']}"); $about = $DB->escape_string($row_epg['episode_about']); }
                
                $name = str_replace('&','&amp;',$name);
                $about = str_replace('&','&amp;',$about);

                $epg[] = array('name'=>$name,'about'=>$about,'runtime'=>$row_epg['duration']);

        }

    }
    
    
    $r3=$DB->query("SELECT end_time FROM iptv_epg WHERE chan_id = '{$crow['id']}' ORDER BY end_time DESC LIMIT 1");
    if(! $ctime = $r3->fetch_assoc()['end_time']) $ctime = time();
    $endtime=$ctime+(86430*1);
    
    while($ctime < $endtime ) { 
    
        foreach($epg as $e ) { 
            
            $end = $ctime+$e['runtime'];
           
            if(!empty($e['name']))
                $DB->query("INSERT INTO iptv_epg (chan_id,title,description,start_date,end_date,start_time,end_time) "
                    ." VALUES('{$crow['id']}','{$e['name']}','{$e['about']}', FROM_UNIXTIME( $ctime ),FROM_UNIXTIME( $end ),'$ctime', '$end' ) ");
                
            $ctime = $ctime + $e['runtime'];
                           
        }
        
    }
    
}