<?php

$ADMIN = new \ACES2\Admin();

if(!adminIsLogged(false)) {
    die();

} else if(!$ADMIN->hasPermission('')) {
    die();
}

$DB = new \ACES2\DB();

$id = (INT) $_GET['id'];

if(empty($id) || !is_numeric($id)) die;

$r=$DB->query("SELECT id,name FROM iptv_devices WHERE id = '$id' LIMIT 1 ");
if(!$row=mysqli_fetch_assoc($r)) die;
$device_name =$row['name'];
$device_id = $row['id'];

$r=$DB->query("SELECT a.*,c.name,s.name as server_name,v.movie_id,episode_id, TIMEDIFF(NOW(),a.add_date) as uptime 
        FROM iptv_access a
        LEFT JOIN iptv_channels c ON c.id = a.chan_id
        LEFT JOIN iptv_servers s ON s.id = a.server_id
        LEFT JOIN iptv_video_files v ON a.vod_id = v.id
        WHERE device_id = $id AND limit_time > NOW() 
        ORDER BY c.name");

?>

?>


<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Connections</title>
</head>
<body>

<script>

    function stopCon(tr,id) {

        $.ajax({
            async:false,
            url: 'ajax/POST.php',
            type: 'post',
            dataType: 'json',
            data: { 'stop_connection' : id } ,
            success: function(data) {

                if(data.no_logged) {location.reload();  }
                else if(data.errors) { alert('Unknown Error.'); }
                else if(data.complete) { $(tr).fadeOut(); }

            }

        });

        return false;

    }

    function stopAll(device_id) {

        if(!confirm('Are you sure you want to stop all connection from this device.')) return false;

        $.ajax({
            async:false,
            url: 'ajax/POST.php',
            type: 'post',
            dataType: 'json',
            data: { 'stop_device_connections' : device_id } ,
            success: function(data) {

                if(data.no_logged) {location.reload();  }
                else if(data.errors) { alert('Unknown Error.'); }
                else if(data.complete) { location.reload();  }

            }
        });

        return false;
    }

    function blockIP(ip) {

        if(!confirm('Are you sure you want to block this ip-address ?')) return false;

        $.ajax({

            url: 'ajax/POST.php',
            type: 'post',
            dataType: 'json',
            data: {'manage_blacklist': 1, 'add_ip' : 1 , 'ip_address' : ip },
            success: function(data) {
                if(data){
                    if(data.not_logged) {location.reload();  }
                    else if(data.errors) {alert(data.errors);}
                    else if(data.complete) { location.reload();  }

                }

            }
        });

    }

    function blockUserAgent(user_agent) {

        if(!confirm('Are you sure you want to block this user-agent ? ')) return false;

        $.ajax({

            url: 'ajax/POST.php',
            type: 'post',
            dataType: 'json',
            data: {'manage_blacklist': 1, 'add_user_agent' : 1 , 'user_agent' : user_agent },
            success: function(data) {
                if(data){
                    if(data.not_logged) {location.reload();  }
                    else if(data.errors) {alert(data.errors);}
                    else if(data.complete) { location.reload();  }

                }

            }
        });

    }

</script>

<style>

    * { margin:0; padding:0; }

    table { width:100%;  border-collapse: collapse; }
    th, td { border-bottom: 1px solid #ddd; text-align: left; padding: 6px; }

    tr:hover {background-color: #f5f5f5 ;}

    .head { width:100%; }
    h2 { padding:20px; }
    button { float:right; margin:10px;cursor:pointer;}
    .clr { clear:both;}
    .red { color:red; }

    a { color:red; }

</style>


<h2><?php echo "Connections of <i> '$device_name' </i>";?></h2>
<button onClick="location.reload(); "> Refresh </button>
<button class="red" onClick="stopAll('<?php echo $device_id; ?>');" >Stop All </button>
<p class="clr"> </p>

<div style="overflow-x:auto;">
    <table>
        <thead>
        <tr>

            <th>Channel Name</th>
            <th>Client IP</th>
            <th>Server</th>
            <th>Uptime</th>
            <th>User Agent</th>
            <th>Stream Format</th>
            <th>Start Time</th>
            <th>Stop Connection</th>
        </tr>
        </thead>
        <tbody>
        <?php
        while($row=$r->fetch_assoc()) {

            if( $row['movie_id'] != 0 || $row['episode_id'] != 0 ) {
                if($row['movie_id'])  {
                    $r2 = $DB->query("SELECT name from iptv_ondemand WHERE id = {$row['movie_id']} ");
                    $name = "MOVIE : ".mysqli_fetch_array($r2)['name'];
                } else if($row['episode_id']) {
                    $r2 = $DB->query("SELECT e.title as name, e.number as episide_number, o.name as series_name, s.number as season_number from iptv_series_season_episodes e "
                        . " LEFT JOIN iptv_series_seasons s ON s.id = e.season_id  "
                        . " LEFT JOIN iptv_ondemand o ON o.id = s.series_id "
                        . " WHERE e.id = {$row['episode_id']} ");
                    $row_series = mysqli_fetch_array($r2);
                    $name = "SERIE : {$row_series['series_name']}, Season{$row_series['season_number']}  Episode{$row_series['episide_number']} - {$row_series['name']} ";
                }

            }else  $name = "STREAM : ".$row['name'];



            echo "<tr><td>$name</td> <td style='cursor:pointer'><a onclick=\" window.open('http://{$_SERVER['HTTP_HOST']}/adminiptv/tb_ip_info.php?ip={$row['ip_address']}','IP-ADDRESS INFO','width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;\"> {$row['ip_address']} </a> <button onclick=\"blockIP('{$row['ip_address']}');\"> Block IP</button> </td>  <td> {$row['server_name']} ({$row['server_ip']}) </td> <td>{$row['uptime']}</td>  <td>{$row['user_agent']}(<a href=\"#!\" onClick=\"blockUserAgent('{$row['user_agent']}');\">Block User Agent</a>) <td>{$row['stream_format']}</td> </td> <td>{$row['add_date']}</td><td><a onClick=\"stopCon($(this).parent().parent(),'{$row['id']}');\"> STOP </a></td>";

        }
        ?>

        </tbody>


    </table>
</div>
<!-- jQuery 2.1.4 -->
<script src="plugins/jQuery/jQuery-2.1.4.min.js"
        type="text/javascript"></script>

</body>
<html>
