<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT))
    Redirect("/admin/profile.php");

$PageName = "Accounts";

$DB = new \ACES2\DB;

$r_admins = $DB->query("SELECT id,username FROM `admins`");
$r_users = $DB->query("SELECT id,username FROM `users`");
$r_bouquets_pkgs = $DB->query("SELECT id,name FROM iptv_bouquet_packages ");
$Users = $r_users->fetch_all(MYSQLI_ASSOC);

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
                <div class="row ">
                    <div class="col-sm-6">
                        <h1><?=$PageName;?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <ul datatable-realtime="#table" class="nav nav-sm nav-pills ml-auto float-right pl-2 ">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a href="form/formAccount.php"
                           class="btn btn-primary btn-sm float-right ml-2 ">Add Account</a>
                        <a id="toggleFilter" class="btn btn-sm btn-primary float-right"><i class="fa fa-filter"></i></a>


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

                                        <div class="d-none col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Account ID</label>
                                                <input disabled class="form-control " name="account_id" />
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Created By Admin</label>
                                                <select name="created_admin"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_admins->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Created By Reseller</label>
                                                <select name="created_user" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php foreach($Users as $o) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Owner</label>
                                                <select name="owner" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php foreach($Users as $o) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Device</label>
                                                <select name="device" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="android">Android Apps / Users</option>
                                                    <option value="mag">Mag</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="active">Active</option>
                                                    <option value="disabled">Disabled</option>
                                                    <option value="blocked">Blocked</option>
                                                    <option value="expired">Expired</option>
                                                    <option value="expire_in_week">Expire in a week</option>
                                                    <option value="expire_in_month">Expire in a month</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Bouquet Package</label>
                                                <select name="bouquet_package" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="no_bouquet_package">[NO BOUQUET PACKAGE]</option>
                                                    <?php while($o = $r_bouquets_pkgs->fetch_assoc()) { ?>
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
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Owner</th>
                                        <th>Connections</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Bouquet Package</th>
                                        <th>Expire On</th>
                                        <th>Last IP</th>
                                        <th>State</th>
                                        <th>Create On</th>
                                        <th>Create By</th>
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

    function reloadTable() {
        TABLES.ajax.reload(null, false);
    }


    function resetMag(id) {

        if(!confirm("Are you sure you want to reset this account?"))
            return false;

        $.ajax({
            url: 'ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: { action:'reset_mag', 'id': id },
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

    function removeAccount(ids) {
        if(!confirm("Are you sure you want to remove this account?"))
            return false;

        $.ajax({
            url: 'ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: { action:'remove_account', 'ids': ids },
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


    $(document).ready(function () {

        $(".select2").select2();


    });

    TABLES = $("#table").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableAccounts.php"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 6, "orderable": false },
            {"targets": 8, "orderable": false },
            {"targets": 12, "orderable": false }
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
                        MODAL("modals/mAccountMassEdit.php?ids="+post_id);

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
                    if(i > 0) {
                        removeAccount(post_id);
                    }

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

</script>
</body>
</html>