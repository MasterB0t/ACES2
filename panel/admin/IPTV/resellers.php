<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS))
    Redirect("/admin/profile.php");

$PageName = "Resellers";

$db = new \ACES2\DB();
$r_owners = $db->query("SELECT id,username,name  FROM users ");


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
                        <a href="form/formReseller.php"
                           class="btn btn-primary btn-sm float-right ml-2">Add Reseller</a>
                        <a id="toggleFilter" class="btn btn-primary btn-sm float-right"><i class="fa fa-filter"></i></a>
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

                                        <div class="d-none col-12 col-md-4">
                                            <div class="form-group">
                                                <label>Reseller ID</label>
                                                <input disabled name="user_id" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <div class="form-group">
                                                <label>Owner</label>
                                                <select name="owner" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_owners->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id']?>"><?=$o['username']?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">Disabled</option>
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
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Reseller Of</th>
                                        <th>Credits</th>
                                        <th>Active Accounts</th>
                                        <th>Last Login </th>
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
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    $(document).ready(function () {

        $(".select2").select2();

    });

    TABLES = $("#table").DataTable({

        "ajax": {
            url: "/admin/IPTV/tables/gTableResellers.php"
        },

        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 6, "orderable": false },
            {"targets": 8, "orderable": false }
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
                        MODAL("modals/mMassEditReseller.php?ids="+post_id);

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
                        MODAL("modals/mRemoveReseller.php?ids="+post_id);
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