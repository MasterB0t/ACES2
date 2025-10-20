<?php
if(!$AdminID=adminIsLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}

$StreamID = (int)$_REQUEST['stream_id'];
$Stream = new \ACES2\IPTV\Stream($StreamID);
$db = new \ACES2\DB;
$json = [];

$sql_date = '';
if(isset($_REQUEST['filter_start_date'])  && isset($_REQUEST['filter_end_date']) ) {
    $start_date = strtotime((int)$_GET['filter_start_date'] .'000000' );
    $end_date = strtotime((int)$_GET['filter_end_date'] . '235959');
    $sql_date = " AND log_time >= '$start_date' AND log_time <= '$end_date' ";
}

//logD($_REQUEST);

switch($_REQUEST['action']) {

    case 'get_stats':

        $json = array(
            0 => array('amount' => 0, 'time' => 'No data yet'),
            1 => array('amount' => 0, 'time' => 'No data yet'),
            2 => array('amount' => 0, 'time' => 'No data yet'),
            3 => array('amount' => 0, 'time' => 'No data yet'),
        );

        $r_stats = $db->query("SELECT type, 
                if ( last_time = 0 , 0, sum(last_time) ) as amount
                FROM iptv_stream_stats 
                WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' $sql_date
                GROUP BY type ORDER BY type ");
        while($row = $r_stats->fetch_assoc()) {
            $json[$row['type']] = array(
                'amount' => $row['amount'],
                'time' => DateBeautyPrint::simplePrint(time() , time() + $row['amount']),
            );
        }

        $r_fix = $db->query("SELECT last_time,log_time,type,UNIX_TIMESTAMP() as utime 
            FROM iptv_stream_stats 
            WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' $sql_date
            
            ORDER BY log_time DESC LIMIT 1
        ");

        $row = $r_fix->fetch_assoc();
        if($row['last_time'] == 0 ) {
            $amount = $json[$row['type']]['amount'] + ($row['utime'] - $row['log_time']);
            $json[$row['type']] =  array(
                'amount' => $amount,
                'time' => DateBeautyPrint::simplePrint(time() , time() + $amount),
            );
        }

        echo json_encode($json);exit;
        break;

    case 'get_timeline':

        $r_last_logtime = $db->query("SELECT 
    
                if ( last_time = 0 , UNIX_TIMESTAMP() , log_time + last_time ) as last_log_time
                            
                FROM iptv_stream_stats 
                WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' $sql_date
               ORDER BY log_time DESC LIMIT 1");

        //NO LOGS
        if($r_last_logtime->num_rows < 1 ) {
            echo json_encode(array());exit;
        }


        $last_log_time = (int)$r_last_logtime->fetch_assoc()['last_log_time'];

        $r = $db->query("SELECT log_time,type,
                if ( last_time = 0 , UNIX_TIMESTAMP() - log_time, last_time ) as amount
            FROM iptv_stream_stats 
            WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' $sql_date
            ORDER BY log_time ");

        $f_log_time = 0; $total_time =0;
        while($row = $r->fetch_assoc()) {
            if(!$total_time)
                $total_time = $last_log_time - $row['log_time'];

            $json[] = array(
                'type' => $row['type'],
                'percent' =>  round($row['amount'] / $total_time * 100, 2),
                'amount' => $row['amount'],
                'date' => date('Y-m-d H:i:s' , $row['log_time']),
                'time' => DateBeautyPrint::simplePrint(time() , time() + $row['amount']),
                'total_time' => $total_time,
            );

        }

        echo json_encode($json);exit;

    case 'get_source_stats':

        $r_last = $db->query("SELECT source_url,log_time, unix_timestamp() AS utime 
            FROM iptv_stream_stats 
            WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' 
              AND type = 3 AND last_time = 0 $sql_date
            ORDER BY log_time DESC LIMIT 1
        ");

        $row_last = null;
        $row_last = $r_last->fetch_assoc();

        $r=$db->query("SELECT source_url, 
            if ( last_time = 0 , 0, sum(last_time) ) as amount 
            FROM iptv_stream_stats         
            WHERE stream_id = '$StreamID' AND server_id = '$Stream->server_id' 
              AND type = 3 $sql_date
            GROUP BY source_url ORDER BY amount DESC
        ");

        while($row = $r->fetch_assoc()) {

            if( $row['source_url'] == $row_last['source_url']) {
                $row['amount'] = $row['amount'] + ($row_last['utime'] - $row_last['log_time']);
            }

            $json[] = array(
                'source_url' => $row['source_url'],
                'time' => DateBeautyPrint::simplePrint(time() , time() + $row['amount']),
            );
        }

        echo json_encode($json);exit;

        break;

    case 'clear_stats':
        $db->query("DELETE FROM iptv_stream_stats WHERE stream_id = '$StreamID'");
        setAjaxComplete();
        break;

}





