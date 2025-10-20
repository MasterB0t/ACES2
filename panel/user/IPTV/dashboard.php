<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$USER = new \ACES2\IPTV\Reseller2($UserID);
use ACES2\IPTV\CreditLog;

$DB = new \ACES2\DB();



?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Dashboard </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">

</head>

<body class="hold-transition text-sm layout-top-nav" >
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../navbar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Dashboard</h1>
                    </div>
                    <div class="col-sm-2 offset-4">
                        <div class="form-group">
                            <select class="form-control" onchange="updateChats()" id="chart-interval">
                                <option value="7">Last 7 Days</option>
                                <option value="15">Last 15 Days</option>
                                <option value="30">Last 30 Days</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-12 ">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4>Credits Expended On Accounts</h4>
                            </div>
                            <div class="box-body">
                                <div class="chart tab-pane active" id="chart-credits-on-account"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 pt-5">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4>New Accounts</h4>
                            </div>
                            <div class="box-body">
                                <div class="chart tab-pane active" id="chart-new-accounts"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 pt-5">
                        <div class="box box-solid  ">
                            <div class="box-header">
                                <h4>Credits Sales From Resellers</h4>
                            </div>
                            <div class="box-body">
                                <div class="chart tab-pane active" id="chart-reseller-sales"></div>
                            </div>
                        </div>
                    </div>

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
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="/plugins/morris/morris.min.js" type="text/javascript"></script>
<!--ChartJS-->
<script src="/plugins/chart.js/Chart.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>

<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/main.js"></script>
<script src="/dist/js/user.js"></script>
<script>

    var line_color = localStorage.getItem('admin.dark_mode') == 'true'  ? "#fff" : "#343a40";
    var ticksStyle = {
        fontColor: line_color,
        fontStyle: 'bold'
    }

    var chartAccountCredits;
    var chartNewAccounts;
    var chartSalesFromResellers;
    var chartByMonth ;


    $("#chart-interval").change(function() {
        localStorage.setItem('chart-interval', $(this).val() );
    });

    function updateChats() {

        intv = $("#chart-interval").val();

        $.ajax({
            url: 'ajax/pDashboard.php?interval='+intv,
            type: 'post',
            dataType: 'json',
            success: function (data) {
                chartAccountCredits.setData(data.chart_account_credits);
                chartNewAccounts.setData(data.chart_new_accounts);
                chartSalesFromResellers.setData(data.chart_sales_resellers);

                chartByMonth.data.labels = data.chart_sales_months_labels;
                chartByMonth.data.datasets[0].data = data.chart_sales_months_vals;
                chartByMonth.update();

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
    
    $(document).ready(function(){

        chartAccountCredits = new Morris.Line({
            element: 'chart-credits-on-account',
            resize: true,
            xkey: 'date',
            ykeys: ['Credits'],
            labels: ['Credits'],
            xLabels: 'day',
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
            element: 'chart-new-accounts',
            resize: true,
            xkey: 'date',
            ykeys: ['Accounts'],
            labels: ['Accounts'],
            xLabels: 'day',
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

        chartSalesFromResellers = new Morris.Line({
            element: 'chart-reseller-sales',
            resize: true,
            xkey: 'date',
            ykeys: ['Credits'],
            labels: ['Credits'],
            xLabels: 'day',
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

        chartByMonth = new Chart( $('#sales-chart'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        backgroundColor: '#007bff',
                        borderColor: '#007bff',
                        data: []
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


        interval = localStorage.getItem('chart-interval');
        if( interval != null )
            $("#chart-interval").val(interval)

        updateChats();




    });

</script>
</body>
</html>