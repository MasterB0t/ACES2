<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS)) {
    http_response_code(403);
    die;
}


$DB = new \ACES2\DB();


$r = $DB->query("SELECT count(id) as total_streams FROM iptv_channels WHERE enable = 1 and stream = 1 ");
$json['db']['total_streams'] = mysqli_fetch_array($r) ['total_streams'];

$r = $DB->query("SELECT count(s.chan_id) as online_streams FROM iptv_channels c 
                        LEFT JOIN iptv_streaming s ON s.chan_id = c.id AND s.server_id = c.stream_server 
                        WHERE s.status = 1 AND TIME_TO_SEC(TIMEDIFF(NOW(), connected_datetime)) > 60");
$stats['online_streams'] = (int)$r->fetch_assoc()['online_streams'];

$r = $DB->query("SELECT count(s.chan_id) as total_running_streams FROM iptv_channels c 
                        LEFT JOIN iptv_streaming s ON s.chan_id = c.id AND s.server_id = c.stream_server 
                        WHERE s.status != 2 AND TIME_TO_SEC(TIMEDIFF(NOW(), connected_datetime)) > 1");
$stats['total_running_streams'] = (int)$r->fetch_assoc()['total_running_streams'];

$stats['online_streamms_percent'] = ($stats['online_streams'] && $stats['total_running_streams']) ? (int)(($stats['online_streams'] / $stats['total_running_streams']) * 100) : 0;


$r = $DB->query("SELECT count(id) as total_clients, sum(limit_connections) as total_connections FROM iptv_devices WHERE status = 1 AND subcription > NOW() ");
$row = $r->fetch_assoc();
$stats['total_clients'] = (int)$row['total_clients'];
$stats['total_connections'] = (int)$row['total_connections'];

$r = $DB->query("SELECT count(DISTINCT(device_id)) as active_clients FROM iptv_access WHERE limit_time > NOW() AND device_id != 0  ");
$stats['active_clients'] = (int)$r->fetch_assoc()['active_clients'];
$stats['active_clients_percent'] = ($stats['active_clients'] && $stats['total_clients']) ? (int)(($stats['active_clients'] / $stats['total_clients']) * 100) : 0;

$r = $DB->query("SELECT count(id) as active_connections FROM iptv_access WHERE limit_time > CURTIME() AND device_id != 0 ");
$stats['active_connections'] = (int)$r->fetch_assoc()['active_connections'];

$stats['active_connections_percent'] = ($stats['active_connections'] && $stats['total_connections']) ? (int)(($stats['active_connections'] / $stats['total_connections']) * 100) : 0;

$json['data'] = $stats;

$json['complete'] = 1;

echo json_encode($json);
exit;