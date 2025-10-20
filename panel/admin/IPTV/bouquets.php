<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_BOUQUETS))
    Redirect("/admin/profile.php");

$PageName = "Bouquets & Packages";




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

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="card">

                            <div class="card-header">
                                <h5>Bouquets
                                <a href="form/formBouquet.php"
                                   class="btn btn-success float-right ml-2">Add Bouquet</a>
                                </h5>
                            </div>

                            <div class="card-body">
                                <table id="tableBouquets" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
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


                    <div class="col-12 col-md-6">
                        <div class="card">

                            <div class="card-header">
                                <h5>Packages
                                    <a href="form/formBouquetPackage.php"
                                       class="btn btn-success float-right ml-2">Add Package</a>
                                </h5>
                            </div>

                            <div class="card-body">
                                <table id="tablePackages" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
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
<script src="/dist/js/admin.js"></script>
<script>
    var tableBouquets;
    var tablePackages;
    var pagetitle = "bouquets";


    function reloadTable() {
        tableBouquets.ajax.reload(null, false);
        tablePackages.ajax.reload(null, false);
    }


    function remove(bouquet_id) {

        if(!confirm("Are you sure you want to remove this bouquet?"))
            return;

        $.ajax({
            url: 'ajax/pBouquets.php',
            type: 'post',
            dataType: 'json',
            data: {action:'remove_bouquet', bouquet_id : bouquet_id },
            success: function (resp) {
                toastr.success("Bouquets have been removed");
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

    function removePackage(package_id) {
        if(!confirm("Are you sure you want to remove this package?"))
            return;

        $.ajax({
            url: 'ajax/pBouquets.php',
            type: 'post',
            dataType: 'json',
            data: {action:'remove_package', package_id : package_id },
            success: function (resp) {
                toastr.success("Package have been removed");
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

    var realtimeTimer;
    var realTime = false;
    function setRealTime(activate) {
        clearInterval(realtimeTimer);
        if(activate) {
            realTime = true;
            reloadTable()
            realtimeTimer=setInterval(reloadTable,5000);
            localStorage.setItem("iptv."+pagetitle+"_realtime", true);
        } else {
            localStorage.setItem("iptv."+pagetitle+"_realtime", false);
            realTime = false;
        }
    }

    $("#realtime li a").click(function () {
        setRealTime(!realTime);
    });

    $("#toggleFilter").click(function() {
        $(".divFilters").toggle();
    })

    $(document).ready(function () {

        tableBouquets = $("#tableBouquets").DataTable({
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
                url: "/admin/IPTV/tables/gTableBouquets.php"
            },
            "stateSaveCallback": function (settings, data) {
                window.localStorage.setItem("dataTable"+pagetitle, JSON.stringify(data));
            },
            "stateLoadCallback": function (settings) {
                if (!window.localStorage.getItem("dataTable"+pagetitle)) FIRST_TIME_LOADING_PAGE = 1;
                return JSON.parse(window.localStorage.getItem("dataTable"+pagetitle));
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

            ],
            order: [[0, 'asc']],



        });

        tablePackages = $("#tablePackages").DataTable({
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
                url: "/admin/IPTV/tables/gTablePackages.php"
            },
            "stateSaveCallback": function (settings, data) {
                window.localStorage.setItem("dataTable"+pagetitle, JSON.stringify(data));
            },
            "stateLoadCallback": function (settings) {
                if (!window.localStorage.getItem("dataTable"+pagetitle)) FIRST_TIME_LOADING_PAGE = 1;
                return JSON.parse(window.localStorage.getItem("dataTable"+pagetitle));
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

            ],
            order: [[0, 'asc']],



        });


        $(".select2").select2();

        if(localStorage.getItem("iptv."+pagetitle+"_realtime") === "true") {
            setRealTime(true)
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

    });


</script>
</body>
</html>