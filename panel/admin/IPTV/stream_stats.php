<?php

    if(!$AdminID=adminIsLogged()){
        Redirect('/admin/login.php');
        exit;
    }

    $ADMIN = new \ACES2\Admin($AdminID);

    if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
        Redirect('/admin/profile.php');
    }

    try {
        $StreamID = (int)$_GET['filter_stream_id'];
        $Stream = new \ACES2\IPTV\Stream($StreamID);
    } catch(\Exception $e) {
        Redirect('streams.php');
    }

    $start_date = isset($_GET['filter_start_date'])
        ? date('%d/%m/%Y',strtotime($_GET['filter_start_date']))
        : date('%d/%m/%Y');

    $end_date = isset($_GET['filter_end_date'])
        ? date('%d/%m/%Y',strtotime($_GET['filter_end_date']))
        : date('%d/%m/%Y');

    $StartDate = isset($_GET['filter_start_date']) ? $_GET['filter_start_date'] : '';
    $EndDate = isset($_GET['filter_end_date']) ? $_GET['filter_end_date'] : '';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>|  </title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- ChartJS -->
    <link rel="stylesheet" href="/plugins/chart.js/Chart.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- DateRange Picker-->
    <link rel="stylesheet" href="/plugins/daterangepicker/daterangepicker.css">
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>
        ul.stream-timeline { display: inline-flex; list-style-type: none; width: 100% !important; padding:0; margin:0; }
        ul.stream-timeline li.shutoff { background-color:#d2d6de }
        ul.stream-timeline li.standby { background-color:#f39c12 }
        ul.stream-timeline li.connecting { background-color:#f56954 }
        ul.stream-timeline li.streaming { background-color:#00a65a }
        ul.stream-timeline li span { padding-left:5px; }
        ul.stream-timeline li { cursor:pointer; }
    </style>
</head>

<body class="hold-transition sidebar-mini text-sm layout-footer-fixed">
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
                        <h1>Stream Stats from <i><?=$Stream->name;?></i></h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"><a href="streams.php">Streams</a></li>
                            <li class="breadcrumb-item active">Stream Stats</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul id="realtime" datatable-realtime="#table" class="nav nav-sm nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a id="toggleFilter" class="btn btn-sm btn-primary float-right">
                            <i class="fa fa-filter"></i></a>

                        <a onclick="clearLogs()" class="btn btn-sm btn-danger float-right mr-2">
                            <i class="fa fa-filter pr-2"></i>Clear Logs</a>


                        <button type="button" class="btn btn-sm btn-default float-right mr-2 " id="daterange-btn">
                            <i class="far fa-calendar-alt"></i> Date range picker
                            <i class="fas fa-caret-down"></i>
                        </button>


                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12 col-xl-8">
                        <div class="card">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">
                                <h5>Filters</h5>

                                <form datatable-filter="#table" id="formFilters">
                                    <input type="hidden" name="start_date" />
                                    <input type="hidden" name="end_date" />
                                    <input type="hidden" name="stream_id" />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control select2">
                                                    <option value="">All </option>
                                                    <option value="0">Shutoff</option>
                                                    <option value="1">Stand by</option>
                                                    <option value="2">Connecting </option>
                                                    <option value="3">Streaming </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                </form>

                            </div>

                            <div class="card-body">
                                <table id="table" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Log Time</th>
                                        <th>Log</th>
                                        <th>Time</th>
                                        <th>Server Name</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-center no-stats-msg">No Stats yet for this stream.</h5>
                                <canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display:none;"></canvas>
                            </div>
                            <div class="card-footer p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item">
                                        <a href="#" class="nav-link ">
                                            Shutoff
                                            <span class="float-right li-shutoff">No data yet</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            Connecting/Down
                                            <span class="float-right text-danger li-connecting">No data yet</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            Standby
                                            <span class="float-right text-warning li-standby">No data yet</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            Streaming
                                            <span class="float-right text-success li-streaming">No data yet</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5>Top Source Streamed</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column ul-source-stats">
                                </ul>
                            </div>
                        </div>
                    </div>


                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Timeline</h5>
                            </div>
                            <div style="padding-bottom:50px;" class="card-body">
                                <ul class="stream-timeline"></ul>
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
<script src="/plugins/bootstrap/js/bootstrap.bundle.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- ChartJS -->
<script src="/plugins/chart.js/Chart.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- InputMask -->
<script src="/plugins/moment/moment.min.js"></script>
<script src="/plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- DateRange Picker-->
<script src="/plugins/daterangepicker/daterangepicker.js">
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    var StreamID = <?=$StreamID;?> ;
    var chartInterval ;
    var StartDate = '<?=$StartDate;?>';
    var EndDate = '<?=$EndDate;?>';
    var UpdateInterval = null;

    var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
    var donutData = {
        labels: [
            'ShutOff',
            'StandBy',
            'Connecting',
            'Streaming',
        ],
        datasets: [
            {
                data: [25,25,25,25],
                backgroundColor : ['#d2d6de', '#f39c12', '#f56954', '#00a65a' ],
            }
        ]
    }

    //Create pie or douhnut chart
    // You can switch between pie and douhnut using the method below.
    var ChartJS = new Chart(donutChartCanvas, {
        type: 'doughnut',
        data: donutData,
        options: {
            maintainAspectRatio : false,
            responsive : true,
        }
    });


    $("#realtime li a ").click(function() {
        if(_REALTIME['#table'])
            UpdateInterval = setInterval(function() {
                updateChart();
                getTimeLine();
                getSourceStats();
            }, 5000);
        else
            clearInterval(UpdateInterval)
    });

    //Date range as a button
    $('#daterange-btn').daterangepicker(
        {
            timePicker: false,
            locale: {
                format: 'DD/MM/YYYY'
            },
            ranges   : {
                'Today'       : [moment(), moment()],
                'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month'  : [moment().startOf('month'), moment().endOf('month')],
                'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: '<?=$start_date;?>',
            endDate  : '<?=$end_date;?>'
        },
        function (start, end) {

            StartDate = start.format('YYYYMMDD');
            EndDate = end.format('YYYYMMDD');

            $("input[name='start_date']").val( StartDate )
            $("input[name='end_date']").val( EndDate )
            $("input[name='end_date']").change();

            getSourceStats();
            updateChart();
            getTimeLine();


        }
    )

    $(document).ready(function () {

        $(".select2").select2();

        updateChart();
        getTimeLine();
        getSourceStats();

        if(_REALTIME['#table'])
            UpdateInterval = setInterval(function() {
                getTimeLine();
                getSourceStats()
                updateChart();
            }, 5000);

    });

    function filterSource(source_url) {
        TABLES.search(source_url).draw();
        //$("#dt-search-0").val(source_url);
        $("#dt-search-0").fadeOut(function() {
            $(this).fadeIn();
        })
    }

    function getSourceStats() {

        Url = 'ajax/gStreamStats.php?action=get_source_stats&stream_id='+StreamID;
        if(StartDate && EndDate ) {
            Url = Url + "&filter_start_date="+StartDate + "&filter_end_date="+EndDate;
        }

        $.ajax({
            url: Url,
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                $(".ul-source-stats").html('');

                for(i=0;i<resp.length; i++ ) {

                    $(".ul-source-stats").append("<li class='nav-item'><a onclick=filterSource('"+resp[i].source_url+"'); href='#' class='nav-link'>" +
                        resp[i].source_url + "<span class='float-right text-success'>" +
                        resp[i].time + "</span></a></li>");
                }

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

    function getTimeLine() {

        Url = 'ajax/gStreamStats.php?action=get_timeline&stream_id='+StreamID
        if(StartDate && EndDate ) {
            Url = Url + "&filter_start_date="+StartDate + "&filter_end_date="+EndDate;
        }

        $.ajax({
            url: Url,
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                console.log(resp);
                $("ul.stream-timeline").html('');

                for(i=0;i<resp.length;i++) {
                    data = resp[i];

                    switch(data.type) {
                        case '0':
                            ctype = 'shutoff';break;
                        case '1':
                            ctype = 'standby';break;
                        case '2':
                            ctype = 'connecting';break;
                        default:
                            ctype = 'streaming';break;
                    }

                    var title = " title='"+data.date+", "+data.time+"' ";

                    var htm = "<li "+title+" class='"+ctype+"' style='width:"+data.percent+"%;'>&nbsp</li>";

                    $("ul.stream-timeline").append(htm);
                    pixels = parseInt(window.getComputedStyle($("ul.stream-timeline li:last")[0]).width )
                    if( pixels> 100 )
                        $("ul.stream-timeline li:last").html("<span>"+ data.time +"</span>")

                }

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

    function updateChart() {

        Url = 'ajax/gStreamStats.php?action=get_stats&stream_id='+StreamID;
        if(StartDate && EndDate ) {
            Url = Url + "&filter_start_date="+StartDate + "&filter_end_date="+EndDate;
        }

        $.ajax({
            url: Url,
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                $(".li-shutoff").html(resp[0]['time'])
                $(".li-standby").html(resp[1]['time'])
                $(".li-connecting").html(resp[2]['time'])
                $(".li-streaming").html(resp[3]['time'])

                if( resp[0]['amount'] == 0 && resp[1]['amount'] == 0
                    && resp[2]['amount'] == 0 && resp[3]['amount'] == 0 ) {
                        $(".no-stats-msg").show();
                        $("#donutChart").hide();
                        return;
                } else {
                    $(".no-stats-msg").hide();
                    $("#donutChart").show();
                }

                data = [resp[0]['amount'], resp[1]['amount'], resp[2]['amount'], resp[3]['amount'] ]

                ChartJS.data.datasets[0].data = data;
                ChartJS.update();

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

    function clearLogs() {

        if(!confirm('Are you sure you want to clear stats fot this stream?'))
            return ;

        $.ajax({
            url: 'ajax/gStreamStats.php?action=clear_stats&stream_id='+StreamID,
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Stats have been clear");
                TABLES.ajax.reload();
                updateChart();
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

    TABLES = $("#table").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableStreamStats.php?filter_stream_id="+StreamID
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 1, "orderable": false },
            {"targets": 3, "orderable": false },
        ],
        order: [[0, 'desc']],
        select: {
            style: 'multi'
        },
    });


    TABLES.on('preXhr', function () {
        let dataScrollTop = $(window).scrollTop();
        TABLES.one('draw', function () {
            $(window).scrollTop(dataScrollTop);
        });
    });

</script>
</body>
</html>