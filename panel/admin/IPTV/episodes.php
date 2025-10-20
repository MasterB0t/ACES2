<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$PageName = "Episodes";
$SeriesID = (int)$_GET['series_id'];

$DB = new \ACES2\DB();
$r_series = $DB->query("SELECT id,name FROM iptv_ondemand WHERE type = 'series' ");
$r_server = $DB->query("SELECT id,name FROM iptv_servers  ");

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
                        <h1><?=$PageName;?></h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item"><a href="videos.php">All Videos</a></li>
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul datatable-realtime="#table"
                            class="nav nav-sm nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a href="form/formEpisode.php"
                           class="btn btn-primary float-right ml-2 btnAdd btn-sm">Add new</a>

                        <a id="toggleFilter" class="btn btn-sm btn-primary float-right">
                            <i class="fa fa-filter"></i></a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">
                                <h5>Filters
                                    <button datatable-clear-filter="#table" class="btn btn-danger btn-sm float-right btnClearFilters">
                                        Clear Filters</button>
                                </h5>

                                <form datatable-filter="#table" id="formFilters">

                                    <div class="row">

                                        <div class="d-none col-6 col-md-3">
                                            <div class="form-group">
                                                <label>Episode ID</label>
                                                <input disabled name="episode_id" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Series</label>
                                                <select name="series" onchange="getSeasons()" class="form-control select2">
                                                    <option value="">From All Series</option>
                                                    <?php while($o = $r_series->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Series</label>
                                                <select name="season" class="form-control select2">
                                                    <option value="">From All Seasons</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="1">Ok</option>
                                                    <option value="0">Processing</option>
                                                    <option value="-2">Failed</option>]
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Server</label>
                                                <select name="server" class="form-control select2">
                                                    <option value="">From All Server</option>
                                                    <?php while($o = $r_server->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
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
                                        <th>Logo</th>
                                        <th>Title</th>
                                        <th>Series</th>
                                        <th>Quality</th>
                                        <th>Episode #</th>
                                        <th>Season #</th>
                                        <th>Server</th>
                                        <th>Rate</th>
                                        <th>Release Date</th>
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
<script>

    function getSeasons() {

        series_id = $("#formFilters select[name='series']").val();

        $(".btnAdd").attr('href', "form/formEpisode.php?series_id="+series_id);

        $("#formFilters select[name='season']").empty();
        $("#formFilters select[name='season']").append($('<option>', {
            value: '',
            text: 'From All Seasons'
        }));

        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'get_seasons', id : series_id},
            success: function (resp) {
                for(i=0;resp.data.length > i; i++ ) {
                    season = resp.data[i];
                    $("#formFilters select[name='season']").append($('<option>', {
                        value: season['id'],
                        text: 'Season '+season['number']
                    }));
                }

                $("#formFilters select[name='season']").select2();

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


    $(document).ready(function () {

        $(".select2").select2();

    });


    TABLES = $("#table").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableEpisodes.php"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 1, "orderable": false },
            {"targets": 4, "orderable": false },
            {"targets": 10, "orderable": false },
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
                text: 'Edit Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );

                    if(i>0)
                        window.location.href="form/formMassEpisodeEdit.php?ids="+post_id


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
                    if(i > 0)
                        MODAL("modals/mVideoMassRemove.php?remove_episodes=1&ids="+post_id);
                }
            },
        ],



    });

    TABLES.on('preXhr', function () {
        let dataScrollTop = $(window).scrollTop();
        TABLES.one('draw', function () {
            $(window).scrollTop(dataScrollTop);
        });
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


</script>
</body>
</html>