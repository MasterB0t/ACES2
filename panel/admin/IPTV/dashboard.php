<?php
if(!$AdminID=adminIsLogged()){
    Redirect("../login.php");
    exit;
}


$ADMIN = new \ACES2\Admin($AdminID);
if(!$ADMIN->hasPermission())
    Redirect("../login.php");



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Profile </title>

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
    <!-- Morris chart -->
    <link href="/plugins/morris/morris.css" rel="stylesheet" type="text/css" />
    <!-- jvectormap -->
    <link href="/plugins/jvectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />
    <!-- Flags -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>

        #world-map { height: 600px; width: 75%; }

        .top-country-online { color:#72afd2; width:25%;  padding:10px; background-color:#ccc !important; border-radius: 4px; height:600px; }
        .dark-mode .top-country-online { background-color:#343a40 !important }
        .top-country-online ul { height:500px; overflow-y:auto;}

        .top-country-online h3 { font-weight: bold; text-align: center; padding-bottom:10px }
        .products-list .item { -webkit-box-shadow: none; box-shadow: none; }
        .products-list li { border-bottom:1px solid #dee2e6; margin-top:20px; }
        .products-list .fi { transform: scale(2.0); margin-left:20px; }

        .product-info span { font-size:13px; }

        .chart tspan, h4 { font-weight:bold;}

        @media screen and (max-width: 1200px) {

            #world-map { width:100%; }

            .top-country-online {
                display: none;
            }
        }

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
                        <h1></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div id="streams-box" class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-video-camera"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Running Streams</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">
                                  Loading...
                                </span>
                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12" title='Amount of active accounts.'>
                        <div id="clients-box" class="info-box bg-blue">
                            <span class="info-box-icon"><i class="fa fa-desktop"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active Accounts</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">
                                  Loading...
                                </span>
                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12" title='Amount of connections.'>
                        <div id="connections-box" class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-plug"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Connections</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style ="width: 0%"></div>
                                </div>
                                <a onclick=" window.open('<?php HOST ?>/admin/IPTV/tb_stream_clients.php','Account Connections',
                                'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;"  style="cursor:pointer; color:white;" class="progress-description">
                                  Loading...
                                </a>

                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                </div>


                <!-- Map box -->
                <div class="box box-solid bg-light-blue pt-3">
                    <div class="box-header">

                        <i class="fa fa-map-marker"></i>
                        <h3 class="box-title">
                            Online Clients
                        </h3>
                    </div>
                    <div class="box-body ">
                        <div style="display:flex; ">

                            <div id="world-map" ></div>

                            <div  class="bg-light top-country-online d-sm-block ">
                                <h3 >BY COUNTRIES</h3>
                                <ul  class="products-list p-1">

                                    <li class="item ">
                                        <div style="background-color:#fff !important;" class="product-info">
                                            <a  href="#!" class="product-title bg-light"><h4> No Connections Yet. </h4></a>
                                        </div>
                                    </li><!-- /.item -->

                                </ul>
                            </div>

                        </div>
                    </div><!-- /.box-body-->
                </div>
                <!-- /.box -->

                <div class="row mt-5">
                    <div class="col-md-3 offset-md-9">
                        <div class="form-group">
                            <select class="form-control" onchange="updateCharts()" id="chart-interval">
                                <option value="7">Last 7 Days</option>
                                <option value="15">Last 15 Days</option>
                                <option value="30">Last 30 Days</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 p-3">
                        <div class="box box-solid ">
                            <div class="box-header">
                                <h4>Credit Sales From Admin</h4>
                            </div>
                            <div class="box-body border-radius-none">
                                <div class="chart " id="sales-from-admin-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 p-3">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4> Credit Sales From Resellers</h4>
                            </div>
                            <div class="box-body border-radius-none">
                                <div class="chart" id="sales-from-reseller-chart"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 p-3">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4>Credits Expended On Accounts</h4>
                            </div>
                            <div class="box-body">
                                <div class="chart tab-pane active" id="account-credits-chart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 p-3 ">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4>New Account Created</h4>
                            </div>
                            <div class="box-body">
                                <div class="chart tab-pane active" id="new-accounts-chart"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class='col-12 p-3'>
                        <div class="box box-solid">
                            <div class="box-header">
                                <h4>Credits Expended By Months</h4>
                            </div>
                            <div class="box-body">
                                <canvas id="sales-chart" height="300"></canvas>
                            </div>
                        </div>

                    </div>
                </div>

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
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="/plugins/morris/morris.min.js" type="text/javascript"></script>
<!-- jvectormap -->
<script src="/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js" type="text/javascript"></script>
<script src="/plugins/jvectormap/jquery-jvectormap-world-mill-en.js" type="text/javascript"></script>
<!--ChartJS-->
<script src="/plugins/chart.js/Chart.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/admin.js"></script>
<script>
    var chartSalesFromAdmin;
    var chartSalesFromResellers;
    var chartAccountCredits;
    var chartNewAccounts;

    var MonthSalesChart;

    var WorldMap;

    var Visitors = [] ;

    var line_color = localStorage.getItem('admin.dark_mode') == 'true'  ? "#fff" : "#343a40";

    var ticksStyle = {
        fontColor: line_color,
        fontStyle: 'bold'
    }

    $("#chart-interval").change(function() {
        localStorage.setItem('chart-interval', $(this).val() );
    });

    function updateMap() {

        $.get('ajax/gVisits.php', function(data) {

            Object.keys(Visitors).forEach(v => Visitors[v] = null);
            WorldMap.vectorMap('get', 'mapObject').series.regions[0].setValues(Visitors);

            if( Object.keys(data.online_map).length ) {

                Visitors = data.online_map;
                WorldMap.vectorMap('get', 'mapObject').series.regions[0].setValues(data.online_map);
            }


            $(".products-list").empty();
            if(data.online_top_countries.length < 1 )
                $(".products-list").html('<li class="item"><div class="product-info"><a href="#!" class="product-title"><h4> No Connections Yet. </h4></a></div></li>');

            else for(i=0;i<data.online_top_countries.length;i++) {

                country = data.online_top_countries[i];

                flag = country['country_code'] == 'unknown' ?
                    'fa fa-circle-question fa-xl' :
                    'fi fi-'+ country['country_code'].toString().toLowerCase();


                htm = '<li class="item border-bottom pr-3">';
                htm +=      '<div class="product-img"><span class="'+flag+'" > </span></div>';
                htm +=      '<div class="product-info">';
                htm +=          '<a href="#!" class="product-title">'+country['country'] +
                    '<span class="badge badge-info float-right">'+country['amount']+'</span></a>';

                htm += '</div></li>';



                $(".products-list").append(htm);

            }


            $("#streams-box .info-box-number").html(data.stats.online_streams+'/'+data.stats.total_running_streams);
            $("#streams-box .progress-bar ").css('width', data.stats.online_streams_percent+'%');
            $("#streams-box .progress-description").html(data.stats.online_streams_percent+"% Streams Connected");

            $("#clients-box .info-box-number").html(data.stats.active_clients+'/'+data.stats.total_clients);
            $("#clients-box .progress-bar ").css('width', data.stats.active_clients_percent+'%');
            $("#clients-box .progress-description").html(data.stats.active_clients_percent+"% Clients Active");


            $("#connections-box .info-box-number").html(data.stats.active_connections+'/'+data.stats.total_connections);
            $("#connections-box .progress-bar ").css('width', data.stats.active_connectons_percent+'%');
            $("#connections-box .progress-description").html(data.stats.active_connections_percent+"% Active Connections <i class='mr-5 fa fa-hand-point-up'></i>");


        }, 'json' );

    }

    function updateCharts() {
        intv = $("#chart-interval").val();

        $.ajax({
            url: 'ajax/gCharts.php?interval='+intv,
            type: 'post',
            dataType: 'json',
            success: function (data) {
                chartAccountCredits.setData(data.chart_account_credits);
                chartNewAccounts.setData(data.chart_new_accounts);
                chartSalesFromResellers.setData(data.chart_sales_resellers);
                chartSalesFromAdmin.setData(data.chart_admin_credits);

                MonthSalesChart.data.labels = data.chart_sales_months_labels;
                MonthSalesChart.data.datasets[0].data = data.chart_sales_months_vals;
                MonthSalesChart.update();

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

    $(document).ready(function() {

        chartSalesFromResellers = new Morris.Line({
            element: 'sales-from-reseller-chart',
            resize: true,
            xkey: 'date',
            ykeys: ['Credits'],
            labels: ['Credits'],
            lineColors: [line_color],
            lineWidth: 2,
            hideHover: 'auto',
            gridTextColor: line_color,
            gridStrokeWidth: 0.4,
            pointSize: 5,
            pointStrokeColors: [line_color],
            gridLineColor: line_color,
            gridTextFamily: "Open Sans",
            gridTextSize: 14
        });

        chartSalesFromAdmin = new Morris.Line({
            element: 'sales-from-admin-chart',
            resize: true,
            xkey: 'date',
            ykeys: ['Credits'],
            labels: ['Credits'],
            lineColors: [line_color],
            lineWidth: 2,
            hideHover: 'auto',
            gridTextColor: line_color,
            gridStrokeWidth: 0.4,
            pointSize: 5,
            pointStrokeColors: [line_color],
            gridLineColor: line_color,
            gridTextFamily: "Open Sans",
            gridTextSize: 14
        });

        chartAccountCredits = new Morris.Line({
            element: 'account-credits-chart',
            resize: true,
            xkey: 'date',
            ykeys: ['Credits'],
            labels: ['Credits'],
            lineColors: [line_color],
            lineWidth: 2,
            hideHover: 'auto',
            gridTextColor: line_color,
            gridStrokeWidth: 0.4,
            pointSize: 5,
            pointStrokeColors: [line_color],
            gridLineColor: line_color,
            gridTextFamily: "Open Sans",
            gridTextSize: 14
        });

        chartNewAccounts = new Morris.Line({
            element: 'new-accounts-chart',
            resize: true,
            xkey: 'date',
            ykeys: ['Accounts'],
            labels: ['Accounts Created'],
            lineColors: [line_color],
            lineWidth: 2,
            hideHover: 'auto',
            gridTextColor: line_color,
            gridStrokeWidth: 0.4,
            pointSize: 5,
            pointStrokeColors: [line_color],
            gridLineColor: line_color,
            gridTextFamily: "Open Sans",
            gridTextSize: 14
        });


        MonthSalesChart = new Chart( $('#sales-chart'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        backgroundColor: '#007bff',
                        borderColor: '#007bff',
                        data:[]
                    },
                ]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    mode: 'index',
                    intersect: true
                },
                hover: {
                    mode: 'index',
                    intersect: true
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        // display: false,
                        gridLines: {
                            display: true,
                            lineWidth: '4px',
                            color: 'rgba(0, 0, 0, .2)',
                            zeroLineColor: 'transparent'
                        },
                        ticks: $.extend({
                            beginAtZero: true,

                            // Include a dollar sign in the ticks
                            callback: function (value, index, values) {
                                if (value >= 1000) {
                                    value /= 1000
                                    value += 'k'
                                }
                                //return '$' + value
                                return value
                            }
                        }, ticksStyle)
                    }],
                    xAxes: [{
                        display: true,
                        gridLines: {
                            display: false
                        },
                        ticks: ticksStyle
                    }]
                }
            }
        });

        WorldMap = $('#world-map').vectorMap({
            map: 'world_mill_en',
            backgroundColor: "transparent",
            regionStyle: {
                initial: {
                    fill: '#5B5B66',
                    "fill-opacity": 1,
                    stroke: 'none',
                    "stroke-width": 0,
                    "stroke-opacity": 1
                }
            },
            series: {
                regions: [{
                    values: {},
                    scale: ['#C8EEFF', '#0071A4'],
                    normalizeFunction: 'polynomial'
                }]
            },
            onRegionLabelShow: function (e, el, code) {
                if (typeof Visitors[code] != "undefined")
                    el.html(el.html() + ': ' + Visitors[code] + ' new visitors');
            },
            onRegionClick: function(e,  code,  isSelected,  selectedRegions){ console.log(selectedRegions)
                $('#world-map').vectorMap('get','mapObject').setFocus({region: code});
            }
        });

        interval = localStorage.getItem('chart-interval');
        if( interval != null )
            $("#chart-interval").val(interval)
        updateCharts();


        updateMap();
        setInterval(updateMap, 5000);

    });
</script>