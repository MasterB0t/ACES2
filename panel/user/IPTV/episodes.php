<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$db = new \ACES2\DB;

$r_series = $db->query("SELECT id,name FROM iptv_ondemand WHERE type = 'series' ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Episodes </title>

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
                        <h1>Episodes</h1>
                    </div>
                    <div class="col-sm-6">
                        <ul id="realtime" class="nav nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a href="form/.php" class="btn btn-success float-right ml-2">Add </a>
                        <a id="toggleFilter" class="btn btn-success float-right"><i class="fa fa-filter"></i></a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-default">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">
                                <h5>Filters</h5>

                                <form id="formFilters">
                                    <div class="row">

                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Series</label>
                                                <select name="series" class="form-group select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_series->fetch_assoc()) :?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php endwhile; ?>
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
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Series</th>
                                        <th>Episode #</th>
                                        <th>Season #</th>
                                        <th>Rate</th>
                                        <th>Release Date</th>
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
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/main.js"></script>
<script src="/dist/js/user.js"></script>
<script>

    TABLEURL = "/user/IPTV/tables/gTableEpisodes.php";
    var DataName = 'user.iptv.episodes';

    TABLE = $("#table").DataTable({
        layout: {
            top2Start: 'buttons',
            top2End: {  pageLength: {
                    menu: [ 10, 25, 50, 100, 1000 ]
                }},
            topStart: 'info',
            topEnd: 'search',
            bottomStart: null,
            bottomEnd: 'pageLength',
            bottom2Start: 'info',
            bottom2End: 'paging'
        },

        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "info": true,
        "serverSide": true,
        "stateSave": true,
        "ordering": true,
        "searching": true,
        "ajax": {
            url: TABLEURL
        },
        "stateSaveCallback": function (settings, data) {
            window.localStorage.setItem(DataName+"_datatable", JSON.stringify(data));
        },
        "stateLoadCallback": function (settings) {
            return JSON.parse(window.localStorage.getItem(DataName+"_datatable"));
        },
        "fnDrawCallback": function (data) {
            $(".paginate_button > a").on("focus", function () {
                $(this).blur();
            });
        },
        "drawCallback": function (settings) {
            var json = this.api().ajax.json();
            if (json.not_logged == 1) window.location.reload();
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 3, "orderable": false },
        ],
        order: [[0, 'asc']],

    });

    var realTime = false;
    function RealTime() {
        if(realTime)
            TABLE.ajax.reload(function(){
                setTimeout(function() {
                    RealTime();
                }, 5000)
            }, false);
    }

    $("#realtime li a").click(function () {
        if(!realTime) {
            realTime = true;
            RealTime()
            localStorage.setItem(DataName+"_realtime", true);
        } else {
            localStorage.setItem(DataName+"_realtime", false);
            realTime = false;
        }
    });

    $(document).ready(function(){

        if(localStorage.getItem(DataName+"_realtime") === "true") {
            realTime = true;
            RealTime();
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

    });

</script>
</body>
</html>