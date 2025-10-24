<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$db = new \ACES2\DB;
$r_server = $db->query("SELECT id,name FROM iptv_servers ");


?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Video Reports </title>
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
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
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
                        <h1>Video Reports</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"><a href="videos.php">Videos</a></li>
                            <li class="breadcrumb-item active">Video Reports</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul datatable-realtime="#table" class="nav nav-sm nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a id="toggleFilter" class="btn btn-sm btn-primary float-right"><i class="fa fa-filter"></i></a>

                        <a href="#" title="Remove All Reports" class="btn btn-sm btn-danger float-right mr-2 "
                            onclick="MODAL('modals/mVideoReportRemove.php?ids=all');">
                            <i class="fa fa-trash mr-2"></i> Clear All Reports</a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row row-progress pr-2 pl-2"></div>

<!--                <div  id="m3u_importer" class="col-12 col-md-6">-->
<!--                    <div class="card collapsed-card">-->
<!--                        <div class="card-header with-border">-->
<!--                            <h3 class="card-title">M3U Imports</h3>-->
<!--                            <div class="card-tools pull-right">-->
<!--                                <button class="btn btn-box-tool"-->
<!--                                        data-card-widget="collapse"><i class="fa fa-plus"></i></button>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                        <div  class="collapse card-body">-->
<!--                            <div id="m3u_importer_body">-->
<!--                                <div class="progress-group"><span class="progress-text ">Process Name</span>-->
<!--                                    <span class="progress-number float-right"><a href=#! >Stop It</a>-->
<!--                                         </span>-->
<!---->
<!--                                    <div class="progress sm">-->
<!--                                        <div class="progress-bar progress-bar-green" style="width: 50%"></div>-->
<!--                                    </div></div>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->



                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">
                                <h5>Filters</h5>

                                <form datatable-filter="#table" id="formFilters">

                                    <div class="row">
<!--                                        <div class="col-6 col-md-3 ">-->
<!--                                            <div class="form-group">-->
<!--                                                <label>Status</label>-->
<!--                                                <select name="report_type" class="form-control select2">-->
<!--                                                    <option value="">All</option>-->
<!--                                                    <option value="file_missing">File Missing</option>-->
<!--                                                    <option value="wrong_info">Wrong Info</option>-->
<!--                                                </select>-->
<!--                                            </div>-->
<!--                                        </div>-->

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Type</label>
                                                <select name="video_type" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="movie">Movie</option>
                                                    <option value="episode">Episode</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Server</label>
                                                <select name="server_id" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_server->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id']?>"><?=$o['name']?></option>
                                                    <?php } ?>
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
                                        <th>Report Type</th>
                                        <th>Video </th>
                                        <th>Server</th>
                                        <th>Source File</th>
                                        <th>Actions</th>
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
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script src="/dist/js/Process.js"></script>
<script>

    function removeReport( file_id ) {

        rvod = 0;
        if(confirm("Are you want to remove the the vod from panel as well?"))
            rvod = 1;

        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: {action: 'remove_video_report', file_id : file_id, remove_vod : rvod },
            success: function (resp) {
                reloadTable();
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

        PROCESS.get({ processToGet : ['iptv.remove_video_reports','iptv.check_videos'], 'appendTo' : '.row-progress' });

    })

    TABLES = $("#table").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableVideoReports.php"
        },

        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 1, "orderable": false },
            {"targets": 2, "orderable": false },
            {"targets": 3, "orderable": false },
            {"targets": 4, "orderable": false },
            {"targets": 5, "orderable": false },


        ],
        order: [[0, 'asc']],
        select: {
            style: 'multi'
        },
        buttons: [
            {
                text: 'Select/Unselect All',
                action: function (e, dt, node, config) {
                    if (TABLES.rows({selected: true}).count() < 1) {
                        TABLES.rows().select();
                    } else {
                        TABLES.rows().deselect();
                    }
                }
            },

            {
                text: 'Remove Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );

                    if(i>0)
                        MODAL('modals/mVideoReportRemove.php?ids='+post_id);

                }
            },


        ],

    });

    TABLES.button(1).enable(false);
    TABLES.button(2).enable(false);

    TABLES.on('select', function (e, dt, type, indexes) {
        TABLES.buttons().enable(true);

    }).on('deselect', function (e, dt, type, indexes) {
        if (TABLES.rows({selected: true}).count() < 1) {
            TABLES.button(1).enable(false);
            TABLES.button(2).enable(false);
        }
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