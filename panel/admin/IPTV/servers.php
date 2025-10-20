<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission())
    Redirect("/admin/profile.php");

$PageName = "Servers";

$db = new \ACES2\DB;
$r_servers = $db->query("SELECT * FROM iptv_servers ");
$SERVERS = $r_servers->fetch_all(MYSQLI_ASSOC);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| <?=$PageName;?> </title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>
        .chart { position: relative; height: 350px;  }
        .card-header { z-index: 1000; }
    </style>
</head>

<body class="hold-transition sidebar-mini text-sm layout-footer-fixed layout-fixed">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include '../sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?=$PageName;?></h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <a href="form/formServer.php"
                           class="btn btn-primary btn-sm float-right ml-2">Add Server</a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">

                    <?php foreach($SERVERS as $SERVER) { $sid = $SERVER['id'];?>

                        <div class="col-12 col-xl-6">
                            <div class="card card-server-<?=$sid?>">

                                <div class="card-header">
                                    <p class="card-title">SERVER <?= "{$SERVER['name']}, {$SERVER['address']}";?>
                                        <p style="display: inline; font-size:14px;" class="pl-2 uptime-<?=$SERVER['id'];?>"></p>

                                    <div class="card-tools pull-right">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" >
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <div class="dropdown-menu" role="menu">
                                                <a class="dropdown-item" title="Will start only stream are not running on this server."
                                                   onclick="action('start_streams', <?=$SERVER['id'] ?>);" href="#"><b>Start streams on this server</b></a>
                                                <a class="dropdown-item" title="Will restart stream running on this server."
                                                   onclick="action('restart_streams', <?=$SERVER['id'] ?>);" href="#"><b>Restart streams on this server</b></a>
                                                <a class="dropdown-item" title="Will stop stream on this server."
                                                   onclick="action('stop_streams', <?=$SERVER['id'] ?>);" href="#"><b>Stop streams on this server</b></a>
                                                <a class="dropdown-item" title="Move All the stream on this server to another."
                                                   onclick="MODAL('modals/servers/mServerMoveStreams.php?server_id=<?= $SERVER['id'] ?>');" href="#"><b>Move all stream from this server</b></a>
                                            </div>
                                        </div>

                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" >
                                                <i class="fas fa-wrench"></i>
                                            </button>
                                            <div  class="dropdown-menu dropdown-menu-server" role="menu">
                                                <a class="dropdown-item"
                                                   href="form/formServer.php?server_id=<?=$SERVER['id']?>">
                                                    <b>Change Server Settings</b> </a>

                                                <a class="dropdown-item" OnClick="action('restart_services',<?=$SERVER['id'] ?>);"
                                                   href="#"><b>Restart Aces Software</b></a>

                                                <a class="dropdown-item" OnClick="action('reboot_server',<?=$SERVER['id'] ?>);"
                                                   href="#"><b>Reboot Server</b></a>

                                                <a class="dropdown-item" OnClick="action('update_aces',<?=$SERVER['id'] ?>);"
                                                   href="#"><b>Update ACES</b></a>

                                                <a class="dropdown-item" OnClick="action('remove_server',<?=$SERVER['id'] ?>);"
                                                   href="#"><b>Remove This Server</b></a>
                                            </div>
                                        </div>

                                    </div>
                                    </p>
                                </div>

                                <div class="card-body">

                                    <div class="row">
                                        <div class="overlay-wrapper">
                                            <div class="overlay"><i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                                <div class="overlay-text text-bold pt-2">Waiting for server...</div></div>
                                        </div>
                                        <div class="col-7 ">
                                            <div style='min-height:400px;' class="nav-tabs-custom">
                                                <ul class="nav nav-pills nav-justified">
                                                    <li class="nav-item ">
                                                        <a class="nav-link active" href="#tab1-<?=$sid;?>" data-toggle="tab">CPU</a></li>
                                                    <li  class="nav-item" >
                                                        <a class="nav-link "href="#tab2-<?=$sid;?>" data-toggle="tab">RAM</a></li>
                                                    <li  class="nav-item">
                                                        <a class="nav-link " href="#tab3-<?=$sid;?>" data-toggle="tab">BANDWIDTH</a></li>
                                                </ul>
                                                <div class="tab-content pt-5">

                                                    <div class="tab-pane active" id="tab1-<?=$sid;?>">
                                                        <div class="chart" >
<!--                                                            <div id="interactive---><?php //=$sid;?><!--" style="width:100%; height: 300px;"></div>-->
                                                            <canvas id="chart-cpu-<?=$sid;?>" ></canvas>
                                                        </div><!-- /.chart-responsive -->

                                                    </div>

                                                    <div class="tab-pane" id="tab2-<?=$sid;?>">
                                                        <div class="chart" >
                                                            <canvas id="chart-ram-<?=$sid;?>" ></canvas>
                                                        </div><!-- /.chart-responsive -->

                                                    </div>

                                                    <div class="tab-pane" id="tab3-<?=$sid;?>">
                                                        <div class="chart">
                                                            <canvas id="chart-bandwidth-<?=$sid;?>" ></canvas>
                                                        </div><!-- /.chart-responsive -->
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-5 ">

                                            <div style='min-height:400px;' class="nav-tabs-custom">
                                                <ul class="nav nav-pills nav-justified">
                                                    <li class="nav-item ">
                                                        <a class="nav-link active" href="#tabstats-<?=$sid;?>" data-toggle="tab">STATS</a></li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#tab-disks-<?=$sid;?>" data-toggle="tab">DISK</a></li>

                                                </ul>
                                                <div class="tab-content pt-3">

                                                    <div class="tab-pane active" id="tabstats-<?=$sid;?>">
                                                        <div class="progress-group cpu-group-<?= $sid;?>">
                                                            <span class="progress-text">CPU Usage</span>
                                                            <span class="float-right progress-number"></span>
                                                            <div class="progress sm">
                                                                <div class="progress-bar progress-bar-green" style="width: 0%"></div>
                                                            </div>
                                                        </div><!-- /.progress-group -->


                                                        <div class="progress-group ram-group-<?= $sid;?>">
                                                            <span class="progress-text">Ram</span>
                                                            <span class="float-right progress-number"></span>
                                                            <div class="progress sm">
                                                                <div class="progress-bar progress-bar-red" style="width: 0%"></div>
                                                            </div>
                                                        </div><!-- /.progress-group -->
                                                        <div class="progress-group bandwidth-group-<?=$sid;?>">
                                                            <span class="progress-text">Bandwidth</span>
                                                            <span class="float-right progress-number"></span>
                                                            <div class="progress sm">
                                                                <div class="progress-bar progress-bar-green" style="width: 0%"></div>
                                                            </div>
                                                        </div><!-- /.progress-group -->
                                                        <div class="progress-group latency-group-<?= $sid;?>">
                                                            <span class="progress-text">Latency</span>
                                                            <span class="progress-number"></span>
                                                            <div class="progress sm">
                                                                <div class="progress-bar progress-bar-green" style="width: 0%"></div>
                                                            </div>
                                                        </div><!-- /.progress-group -->
                                                        <div class="row">
                                                            <div class="col-12 online-channels-<?=$sid;?> ">
                                                                <h5 style="text-align:center;">Streams<br/><b>0</b></h5></div>
                                                            <div class="col-12 online-users-<?=$sid;?> ">
                                                                <h5 style="text-align:center;">Users<br/><b>0</b></h5></div>
                                                            <div style="cursor:pointer" class="col-12 connections-<?=$sid;?>">
                                                                <h5  onclick=" window.open('<?php HOST ?>/admin/IPTV/tb_stream_clients.php?server_id=<?=$sid;?>','Account Connections',
                                                                        'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;" style="text-align:center;">Connections<br/><b>0</b></h5></div>
                                                        </div>

                                                    </div>
                                                    <div class="tab-pane" id="tab-disks-<?=$sid;?>">
                                                        <?php foreach( json_decode($SERVER['hard_disks'],1) as  $disk ) {
                                                            $disk['device'] = str_replace('/dev/','',$disk['device']);
                                                            $disk['device'] = str_replace('mapper/','',$disk['device']);
                                                            ?>
                                                            <div class="progress-group disk-group-<?=$sid."-".$disk['device']?> ">
                                                                <span class="progress-text"><?=$disk['mount_point'];?></span>
                                                                <span class="progress-number float-right"></span>
                                                                <div class="progress sm">
                                                                    <div class="progress-bar progress-bar-red" style="width: 0%"></div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php } ?>
                </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include '../footer.php'; ?>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- Flot -->
<script src="/plugins/flot/jquery.flot.js"></script>
<script src="/plugins/flot/plugins/jquery.flot.resize.js"></script>
<script src="/plugins/flot/plugins/jquery.flot.categories.js"></script>
<script src="/plugins/flot/plugins/jquery.flot.pie.js"></script>
<!-- ChartJS -->
<script src="/plugins/chart.js/Chart.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>

<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/admin.js"></script>
<script>
    var pagetitle = "servers";

    var chart_cpu = [];
    var chart_ram = [];
    var chart_bandwidth = [];

    var empty_chart = [];
    for (var z = 0; z < 10; z++) {
        empty_chart.push([z, 0 ]);
    }


    function convertUptime(seconds) {
        seconds = Number(seconds);
        var d = Math.floor(seconds / (3600*24));
        var h = Math.floor(seconds % (3600*24) / 3600);
        var m = Math.floor(seconds % 3600 / 60);
        var s = Math.floor(seconds % 60);

        var dDisplay = d > 0 ? d + "d, " : "";
        var hDisplay = h > 0 ? h + "h, " : "";
        var mDisplay = m > 0 ? m + "m, " : "";
        var sDisplay = s > 0 ? s + "s " : "";
        return dDisplay + hDisplay + mDisplay + sDisplay;
    }

    function action(action, server_id) {

        if(action == 'remove_server')
            if(!confirm("Are you sure you want to remove this server?"))
                return false;

        if(action == 'update_aces')
            if(!confirm("Are you sure you want to update ACES software on this server?"))
                return false;

        $.ajax({
            url: 'ajax/pServer.php',
            type: 'post',
            dataType: 'json',
            data: {action: action, server_id : server_id },
            success: function (resp) {
                if(action == 'remove_server') {
                    setTimeout(function(){ window.location.reload() }, 1000);
                    toastr.success("Server have been removed.");
                } else
                    toastr.success("Success");

            }, error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        var response = xhr.responseJSON;
                        if (typeof response != "undefined" && typeof response.error != "undefined")
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");

                }
            }
        });
    }

    function update() {

        $.ajax({

            url: 'ajax/pServer.php',
            type: 'post',
            dataType: 'json',
            data: { 'action': 'get_stats' },
            success: function(resp) {

                setTimeout(update, 3000);

                $.each(resp, function (i, v) { server_id = i;

                    if(resp[i].status != 1 ) {

                        $(".card-server-"+server_id+ " .card-body .overlay-wrapper ").show();
                        $(".card-server-"+server_id+ " .card-body .overlay-wrapper .overlay .overlay-text ").html(resp[i].status_msg);


                    } else {

                        $(".card-server-"+server_id+ " .card-body .overlay-wrapper ").hide();

                        u=convertUptime(resp[i].stats.uptime);
                        $(".uptime-"+server_id).html("Uptime "+u);

                        $(".cpu-group-"+server_id+" .progress-number " ).html("<b>"+resp[i].stats.cpu_load+"%</b>");
                        $(".cpu-group-"+server_id+" .progress-bar " ).width(resp[i].stats.cpu_load+"%");
                        bcolor = 'progress-bar-green';
                        if(resp[i].stats.cpu_load > 80 ) bcolor = 'progress-bar-red';
                        else if(resp[i].stats.cpu_load > 39 ) bcolor = 'progress-bar-yellow';
                        $(".cpu-group-"+server_id+" .progress-bar").removeClass('progress-bar-red progress-bar-green progress-bar-yellow');
                        $(".cpu-group-"+server_id+" .progress-bar").addClass(bcolor);


                        $(".ram-group-"+server_id+" .progress-number " ).html("<b>"+resp[i].stats.memory_usage+"/"+resp[i].stats.memory_total+"</b>");
                        $(".ram-group-"+server_id+" .progress-bar " ).width(resp[i].stats.memory_usage_percent+"%");
                        if(resp[i].stats.memory_usage_percent > 80 ) bcolor = 'progress-bar-red';
                        else if(resp[i].stats.memory_usage_percent > 39 ) bcolor = 'progress-bar-yellow';
                        $(".ram-group-"+server_id+" .progress-bar").removeClass('progress-bar-red progress-bar-green progress-bar-yellow');
                        $(".ram-group-"+server_id+" .progress-bar").addClass(bcolor);


                        $(".bandwidth-group-"+server_id+" .progress-number " ).html("<b> IN "+resp[i].stats.rx_bandwidth+"Mb  / OUT <b>"+resp[i].stats.tx_bandwidth+"Mb  || TOTAL "+Math.round(resp[i].stats.rx_bandwidth+resp[i].stats.tx_bandwidth)+"Mb </b>");
                        $(".bandwidth-group-"+server_id+" .progress-bar " ).width(resp[i].stats.bandwidth_usage_percent+"%");
                        if(resp[i].stats.bandwidth_usage_percent > 80 ) bcolor = 'progress-bar-red';
                        else if(resp[i].stats.bandwidth_usage_percent > 39 ) bcolor = 'progress-bar-yellow';
                        $(".bandwidth-group-"+server_id+" .progress-bar").removeClass('progress-bar-red progress-bar-green progress-bar-yellow');
                        $(".bandwidth-group-"+server_id+" .progress-bar").addClass(bcolor);

                        $(".latency-group-"+server_id+" .progress-number " ).html("<b>"+resp[i].latency+" ms</b>");
                        $(".latency-group-"+server_id+" .progress-bar " ).width(resp[i].latency_percent+"%");
                        if(resp[i].latency_percent > 80 ) bcolor = 'progress-bar-red';
                        else if(resp[i].latency_percent > 39 ) bcolor = 'progress-bar-yellow';
                        $(".latency-group-"+server_id+" .progress-bar").removeClass('progress-bar-red progress-bar-green progress-bar-yellow');
                        $(".latency-group-"+server_id+" .progress-bar").addClass(bcolor);

                        $(".online-channels-"+server_id).find('b').html(resp[i].stats.online_channels);
                        $(".online-users-"+server_id).find('b').html(resp[i].stats.online_users);
                        $(".connections-"+server_id).find('b').html(resp[i].stats.connections);

                        for(d=0;d<resp[i].hard_disks.length;d++) { disk = resp[i].hard_disks[d]; disk['device'] = disk['device'].replace('/dev/',''); disk['device'] = disk['device'].replace('mapper/','');
                            $(".disk-group-"+server_id+"-"+disk['device']+" .progress-number").html("<b>"+disk['size']+" GB</b>");
                            bar = disk['used'] / disk['size'] * 100;
                            bcolor = 'progress-bar-green';
                            if(bar > 80 ) bcolor = 'progress-bar-red';
                            else if(bar > 39 ) bcolor = 'progress-bar-yellow';

                            $(".disk-group-"+server_id+"-"+disk['device']+" .progress-bar").width(bar+"%");
                            $(".disk-group-"+server_id+"-"+disk['device']+" .progress-bar").removeClass('progress-bar-red progress-bar-green progress-bar-yellow');
                            $(".disk-group-"+server_id+"-"+disk['device']+" .progress-bar").addClass(bcolor);
                        }

                        chart_cpu[server_id].data.datasets[0].data = resp[i]['cpu_history'] ;
                        chart_cpu[server_id].update();

                        chart_ram[server_id].data.datasets[0].data = resp[i]['ram_history'] ;
                        chart_ram[server_id].update();

                        chart_bandwidth[server_id].data.datasets[0].data = resp[i]['bandwidth_history'] ;
                        chart_bandwidth[server_id].update();


                    }


                });


            }
        });



    }

    $(document).ready(function () {

        <?php foreach($SERVERS as $SERVER) { $sid = $SERVER['id']; ?>

            chart_cpu[<?=$sid;?>] = new Chart( $('#chart-cpu-<?=$sid;?>')[0], {
                type: 'line',
                data: {
                    labels: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19],
                    datasets: [
                        {
                            label: 'CPU Usage',
                            backgroundColor: 'rgba(60,141,188,0.9)',
                            borderColor: 'rgba(60,141,188,0.8)',
                            pointRadius: false,
                            pointColor: '#3b8bba',
                            pointStrokeColor: 'rgba(60,141,188,1)',
                            pointHighlightFill: '#fff',
                            pointHighlightStroke: 'rgba(60,141,188,1)',
                            data: []
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    legend: {
                        display: false
                    },
                }
            });

        chart_ram[<?=$sid;?>] = new Chart( $('#chart-ram-<?=$sid;?>')[0], {
            type: 'line',
            data: {
                labels: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19],
                datasets: [
                    {
                        label: 'RAM Usage',
                        backgroundColor: 'rgba(60,141,188,0.9)',
                        borderColor: 'rgba(60,141,188,0.8)',
                        pointRadius: false,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data: empty_chart
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: {
                    display: false
                },

            }
        });

        chart_bandwidth[<?=$sid;?>] = new Chart( $('#chart-bandwidth-<?=$sid;?>')[0], {
            type: 'line',
            data: {
                labels: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19],
                datasets: [
                    {
                        label: 'Bandwidth Usage',
                        backgroundColor: 'rgba(60,141,188,0.9)',
                        borderColor: 'rgba(60,141,188,0.8)',
                        pointRadius: false,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data: empty_chart
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: {
                    display: false
                },

            }
        });

        <?php } ?>

        update();

    });

</script>
</body>
</html>