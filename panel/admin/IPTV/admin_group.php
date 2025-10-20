<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(""))
    Redirect("/admin/profile.php");



?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Admin Groups </title>
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
                        <h1>Admin Groups</h1>
                        <ol class="breadcrumb pb-2">
                            <li class="breadcrumb-item active"><a href="admins">Admins</a></li>
                            <li class="breadcrumb-item active">Admin Groups</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <a href="form/formAdminGroup.php"
                           class="btn btn-sm btn-primary float-right" >Add Group </a>
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

                        <div class="card-body">
                            <table id="table" class="table table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Admin In Group</th>
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

    function reloadTable() {
        table.ajax.reload(null, false);
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
                url: "/admin/IPTV/tables/gTableAdminGroups.php"
            },
            "stateSaveCallback": function (settings, data) {
                window.localStorage.setItem("dataTableAdminGroup", JSON.stringify(data));
            },
            "stateLoadCallback": function (settings) {
                if (!window.localStorage.getItem("dataTableAdminGroup")) FIRST_TIME_LOADING_PAGE = 1;
                return JSON.parse(window.localStorage.getItem("dataTableAdminGroup"));
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
                {"targets": 3, "orderable": false },
            ],
            select: {
                style: 'multi'
            },
            order: [[0, 'asc']],
        });

    });


</script>
</body>
</html>