<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS))
    Redirect("/admin/profile.php");

$PageName = "Epg Sources";


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
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <a onclick="MODAL('modals/streams/mEpgSource.php');"
                           class="btn btn-sm btn-primary float-right mr-1 ml-1">Add Epg Source </a>

                        <a onclick="buildEPG()"
                           class="btn btn-sm btn-primary float-right ml-1 mr-1 " >Build Epg </a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div style="display: none;" class="info-box bg-info epg-progress">
                        <span class="info-box-icon"><i class="fa fa-gears"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Building EPG</span>
                            <span class="info-box-number"></span>

                            <div class="progress">
                                <div class="progress-bar" style="width: 0%"></div>
                            </div>
                            <span class="progress-description">

                            </span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <table id="table" class="table table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>URL</th>
                                    <th>Last Updated</th>
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
<script src="/dist/js/admin.js"></script>
<script>
    var table;
    var TOKEN = '<?=\ACES2\Armor\Armor::createToken('iptv.epg_source');?>';

    function reloadTable() {
        table.ajax.reload(null, false);
    }

    function updateTVGIDs(epg_id) {
        $.ajax({
            url: 'ajax/streams/pEpg.php',
            type: 'post',
            dataType: 'json',
            data: {action:'update_tvgids', id : epg_id },
            success: function (resp) {
                toastr.success('TVG Ids will be update on background.');
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

    function buildEPG() {

        $.ajax({
            url: 'ajax/streams/pEpg.php',
            type: 'post',
            dataType: 'json',
            data: { action: 'build_epg', token: TOKEN },
            success: function (resp) {
                setTimeout(getEpgProgress, 1000)
                toastr.success("Success.")
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

    function getEpgProgress() {
        $.ajax({
            url: 'ajax/streams/pEpg.php',
            type: 'post',
            dataType: 'json',
            data: { action: 'get_epg_progress', token: TOKEN },
            success: function (resp) {

                if(resp.data.is_running) {
                    $(".epg-progress").show();
                    $(".progress-description").html(resp.data.progress + "% Progress")
                    $(".epg-progress").find('.progress-bar').css('width',resp.data.progress + '%');
                    setTimeout(getEpgProgress, 5000 )
                } else {
                    reloadTable()
                    $(".epg-progress").hide();
                    $(".progress-description").html("")
                    $(".epg-progress").find('.progress-bar').css('width', '0%');
                }

            }, error: function (xhr) {
                $(".epg-progress").hide();
                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;

                }
            }
        });
    }

    $(document).ready(function () {

        table = $("#table").DataTable({
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
                url: "/admin/IPTV/tables/gTableEpgSources.php"
            },
            "stateSaveCallback": function (settings, data) {
                window.localStorage.setItem("dataTableEpgSources", JSON.stringify(data));
            },
            "stateLoadCallback": function (settings) {
                if (!window.localStorage.getItem("dataTableEpgSources")) FIRST_TIME_LOADING_PAGE = 1;
                return JSON.parse(window.localStorage.getItem("dataTableEpgSources"));
            },
            "fnDrawCallback": function (data) {
                $(".paginate_button > a").on("focus", function () {
                    $(this).blur();
                });
            },
            "drawCallback": function (settings) {
                var api = this.api();
                var json = api.ajax.json();
                if (json.not_logged == 1) window.location.reload();;
            },
            columnDefs: [
                { className: 'select-checkbox'},
                {"targets": 2, "orderable": false },
                {"targets": 3, "orderable": false }
            ],
            order: [[0, 'asc']],
        });

        getEpgProgress();

    });


</script>
</body>
</html>