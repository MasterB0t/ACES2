<?php
if(!$AdminID=adminIsLogged()){
    Redirect("/admin/login.php");
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
    Redirect("/admin/profile.php");
}
$db = new \ACES2\DB;
$r_accounts = $db->query("SELECT id,name,username FROM `iptv_devices` ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>| Account Logs </title>
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
    <?php include '../sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Account Logs</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"><a href="accounts.php">All Accounts</a></li>
                            <li class="breadcrumb-item active">Account Logs</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul datatable-realtime="#tableAccountLogs" class="nav nav-sm nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

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
                                    <button datatable-clear-filter="#tableAccountLogs"
                                            class="btn btn-danger btn-sm float-right btnClearFilters">
                                        Clear Filters
                                    </button>
                                </h5>

                                <form datatable-filter="#tableAccountLogs" id="formFilters">

                                    <div class="row">
                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Account</label>
                                                <select name="account_id" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o=$r_accounts->fetch_assoc()) {?>
                                                        <option value="<?=$o['id']?>"><?="{$o['name']} - {$o['username']}"?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                </form>

                            </div>

                            <div class="card-body">
                                <table id="tableAccountLogs" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Log</th>
                                        <th>IP-Address</th>
                                        <th>User-Agent</th>
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


    //TEST COMMENT.
    data = JSON.parse(localStorage.getItem('DataTables_tableAccountLogs_/admin/IPTV/account_logs.php'))
    if(data != null ) {
        if(typeof data.childRows == 'object') {
            delete data.childRows
            localStorage.setItem('DataTables_tableAccountLogs_/admin/IPTV/account_logs.php', data);
        }
    }

    $(document).ready(function () {

        $(".select2").select2();

    });

    TABLES = $("#tableAccountLogs").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableAccountLogs.php"
        },
        columnDefs: [
            {className: 'select-checkbox'},
            {"targets": 2, "orderable": false},
        ],
        order: [[0, 'desc']],
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