<?php

    if(!$AdminID=adminIsLogged()){
        Redirect('/admin/login.php');
    }

    $ADMIN = new \ACES2\Admin($AdminID);

    if(!$ADMIN->hasPermission("")) {
        Redirect('/admin/profile.php');
    }

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>| firewall </title>
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
    <?php include 'header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include 'sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Firewall</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active">Firewall</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <a href="#!" onclick="MODAL('modals/mFirewallRule.php')"
                           class="btn btn-primary btn-sm float-right ml-2">Add Rule</a>


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
                                        <th>IP-ADDRESS</th>
                                        <th>RULE</th>
                                        <th>Options</th>
                                        <th>Comments</th>
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

    <?php include 'footer.php'; ?>

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


    function removeRule(ids) {
        if(!confirm('Are you sure you want to remove this firewall rule ?'))
            return;
        $.ajax({
            url: 'ajax/pFirewall.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'REMOVE_RULE', ids : ids },
            success: function (resp) {
                TABLES.ajax.reload();
                toastr.success("Rule have been removed.");
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
            url: "/admin/tables/gTablesFirewall.php"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 0, "orderable": false },
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
                    if(i > 0)
                        removeRule(post_id)
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