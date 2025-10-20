<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS))
Redirect("/admin/profile.php");

$db = new \ACES2\DB;

$PageName = "Credit Logs";
$Reseller = new \ACES2\IPTV\User( (int)$_REQUEST['user_id']) ;
$SubResellers = $Reseller->getResellers();

$user_sql = count($SubResellers)> 0 ? " WHERE id IN (" . implode("," , $SubResellers ) . " ) " : '';
$r_users = $db->query("SELECT id,username FROM users $user_sql ORDER BY username   ");

$SubResellers[] = $Reseller->id;
$owner_sql = count($SubResellers)> 0 ? " WHERE user_id IN (" . implode("," , $SubResellers ) . " ) " : '';
$r_accounts = $db->query("SELECT id,name,username FROM iptv_devices $owner_sql  ");


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
                        <h1><?=$PageName;?> - <i><?=$Reseller->username?></i></h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"> <a href="resellers.php"> All Resellers </a></li>
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul id="realtime" class="nav nav-pills float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" datatable-realtime="#table" datatable-live="off"
                                   data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" datatable-realtime="#table"  datatable-live="on"
                                   data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a id="toggleFilter" class="btn btn-success btn-sm float-right ">
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
                                <h5>Filters</h5>

                                <form datatable-filter="#table" id="formFilters">

                                    <input type="hidden" name="user_id" value="<?=$Reseller->id?>" />

                                    <div class="row">
                                        <div class="col-md-4 col-xl-3">
                                            <div class="form-group">
                                                <label>To Account</label>
                                                <select name="to_account_id"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o=$r_accounts->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?="#{$o['id']} - {$o['name']}";?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-md-4 col-xl-3">
                                            <div class="form-group">
                                                <label>To User</label>
                                                <select name="to_user_id"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o=$r_users->fetch_assoc()) { ?> ?>
                                                        <option value="<?=$o['id'];?>"><?="#{$o['id']} - {$o['username']}";?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>


                                    </div>



                                </form>

                            </div>

                            <div class="card-body">
                                <table id="table" class="table table-hover dataTable">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Account / Reseller</th>
                                        <th>Package</th>
                                        <th>Credit Used</th>
                                        <th>Remaining Credits</th>
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
<!--<script src="/dist/js/main.js"></script>-->
<script src="/dist/js/admin.js"></script>
<script src="/dist/js/table.js"></script>
<script>

    TABLES = $(".dataTable").DataTable({
        "ajax": {
            url: "tables/gTableResellerCreditLogs.php?filter_user_id=<?=$Reseller->id?>"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 1, "orderable": false },
            {"targets": 3, "orderable": false },
            {"targets": 4, "orderable": false },
        ],
    });

    $(document).ready(function () {

        TABLES.button(1).enable(false);
        TABLES.button(2).enable(false);
        TABLES.on('select', function (e, dt, type, indexes) {
            TABLES.buttons().enable(true);

        }).on('deselect', function (e, dt, type, indexes) {
            if (TABLES.rows({selected: true}).count() < 1) {
                TABLES.button(1).enable(false);
                TABLES.button(2).enable(false);
            }
        }).on('preXhr', function () {
            let dataScrollTop = $(window).scrollTop();
            TABLES.one('draw', function () {
                $(window).scrollTop(dataScrollTop);
            });
        });

        var realTime = null;
        var realTimeLock = false;
        var DataNameRealtime = window.location.href + "_realtime";

        function RealTime(){
            if(realTimeLock)
                return;

            realTimeLock = true;

            TABLES.ajax.reload(function(){
                realTimeLock = false;
            }, false);
        }

        $("#realtime li a").click(function () {
            if(!realTime) {
                realTime = setInterval(RealTime, 3000);
                localStorage.setItem(DataNameRealtime, true);
            } else {
                localStorage.setItem(DataNameRealtime, false);
                clearInterval(realTime)
                realTime = null;
            }
        });

        if(localStorage.getItem(DataNameRealtime) === "true") {
            realTime = true;
            RealTime();
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }



        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

    });


</script>
</body>
</html>