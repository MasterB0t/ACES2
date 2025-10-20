<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$User = new \ACES2\IPTV\Reseller2($UserID);
if(!$User->allow_channel_list) {
    http_response_code(404);
    exit;
}

$db = new \ACES2\DB;
$r_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>| Streams </title>

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

<body class="hold-transition text-sm layout-top-nav">
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
                        <h1>Channels</h1>
                    </div>
                    <div class="col-sm-6">
                        <ul id="realtime" class="nav nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab">Live Data</a>
                            </li>
                        </ul>
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

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select class="form-control select2" name="status">
                                                    <option value="">All</option>
                                                    <option value="streaming">Streaming</option>
                                                    <option value="connecting">Connecting</option>
                                                    <option value="stopped">Stopped</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Category</label>
                                                <select class="form-control select2" name="category">
                                                    <option value="">All</option>
                                                    <?php while($c = $r_cats->fetch_assoc()): ?>
                                                        <option value="<?=$c['id'];?>"><?=$c['name'];?></option>
                                                    <?php endwhile;  ?>
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
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Uptime/Downtime</th>
                                        <th>Codecs/Bitrate</th>
                                        <th>Category</th>
                                        <th>Action</th>
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

    TABLEURL = "/user/IPTV/tables/gTableStreams.php";
    var DataName = 'user.iptv.channels';

    TABLE = $("#table").DataTable({
        layout: {
            top2Start: 'buttons',
            top2End: {
                pageLength: {
                    menu: [10, 25, 50, 100, 1000]
                }
            },
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
            window.localStorage.setItem(DataName + "_datatable", JSON.stringify(data));
        },
        "stateLoadCallback": function (settings) {
            return JSON.parse(window.localStorage.getItem(DataName + "_datatable"));
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
            {className: 'select-checkbox'},
            {"targets": 2, "orderable": false},
            {"targets": 4, "orderable": false},
        ],
        order: [[0, 'asc']],
        select: {
            style: 'multi'
        },
        buttons: [

            {
                text: 'Select/Unselect All',
                action: function (e, dt, node, config) {
                    if (TABLE.rows({selected: true}).count() < 1) {
                        TABLE.rows().select();
                    } else {
                        TABLE.rows().deselect();
                    }
                }
            },

            {
                text: 'Restart Selected',
                action: function (e, dt, node, config) {

                    var post_id = [];
                    var i = 0;
                    TABLE.rows({selected: true}).every(function (rowIdx, tableLoop, rowLoop) {
                        idx = TABLE.row(rowIdx).index();
                        post_id[i] = TABLE.row(rowIdx).column(0).cell(idx, 0).data();
                        i++;
                    });

                    if (i > 0)
                        restartStream( post_id);

                }
            },

        ],

    });

    var realTime = false;

    function restartStream(streamIDs) {
        $.ajax({
            url: 'ajax/pStreams.php',
            type: 'post',
            dataType: 'json',
            data: {action:'restart_stream', 'stream_ids' : streamIDs },
            success: function (resp) {
                toastr.success("Stream have been restart")

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

    function RealTime() {
        if (realTime)
            TABLE.ajax.reload(function () {
                setTimeout(function () {
                    RealTime();
                }, 5000)
            }, false);
    }

    $("#realtime li a").click(function () {
        if (!realTime) {
            realTime = true;
            RealTime()
            localStorage.setItem(DataName + "_realtime", true);
        } else {
            localStorage.setItem(DataName + "_realtime", false);
            realTime = false;
        }
    });

    $(document).ready(function () {

        if (localStorage.getItem(DataName + "_realtime") === "true") {
            realTime = true;
            RealTime();
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

    });

</script>
</body>
</html>