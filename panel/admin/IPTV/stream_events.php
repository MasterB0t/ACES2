<?php
if(!$AdminID=adminIsLogged()){
    Redirect("/admin/login.php");
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    Redirect("/admin/profile.php");
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>| Stream Events </title>
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

<body class="hold-transition sidebar-mini text-sm layout-footer-fixed">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include "../sidebar.php"; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Stream Events</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active">Stream Events</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <a href="form/formStreamEvent.php"
                           class="btn btn-primary btn-sm float-right ml-2 ">Add Event</a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div style="display:none;" id="provider_update" class="col-12 col-md-12">
                        <div class="card">
                            <div class="card-header with-border">
                                <h3 class="card-title">Process</h3>
                                <div class="card-tools pull-right">
                                    <button class="btn btn-box-tool"
                                            data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div style="display:none;" class="collapse card-body">
                                <div id="provider_update_body"></div>
                            </div>
                        </div>
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
                                        <th>Event Type</th>
                                        <th>Enabled</th>
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

    <?php include DOC_ROOT."/admin/footer.php"; ?>

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

    function scanEvent(idd) {

        $.ajax({
            url: 'ajax/pStreamEvent.php',
            type: 'post',
            dataType: 'json',
            data: { action : 'scan', event_id : idd },
            success: function (resp) {
                toastr.success("Success")
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

    function removeEvent(id) {

        if(!confirm('Are you sure you want to remove this?'))
            return;

        $.ajax({
            url: 'ajax/pStreamEvent.php',
            type: 'post',
            dataType: 'json',
            data: { action : 'remove' , event_id : id },
            success: function (resp) {
                toastr.success("Removed!");
                setTimeout(function() { TABLES.ajax.reload();}, 800 );
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
            url: "/admin/IPTV/tables/gTableStreamEvents.php"
        },
        columnDefs: [
            {className: 'select-checkbox'},
            {"targets": 0, "orderable": false},
        ],
        order: [[0, 'asc']],
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