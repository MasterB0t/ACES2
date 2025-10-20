<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$PageName = "Accounts";
$DB = new \ACES2\DB;
$USER = new \ACES2\IPTV\Reseller2($UserID);
$SubResellers = $USER->getResellers();
$sql_resellers = implode(",", $SubResellers);

$Owners= [];
if(count($SubResellers) > 0) {
    $r_users = $DB->query("SELECT id,username FROM `users` WHERE id IN ($sql_resellers) ");
    $Owners = $r_users->fetch_all(MYSQLI_ASSOC);
}


?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| <?=$PageName?> </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
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

<body class="hold-transition text-sm layout-top-nav" >
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../navbar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?=$PageName?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ul id="realtime" class="nav nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a href="form/formAccount.php" class="btn btn-primary btn-sm float-right ml-2">Add Account</a>
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
                        <div class="card card-default">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">
                                <h5>Filters</h5>

                                <form id="formFilters">
                                    <div class="row">

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Owner</label>
                                                <select name="owner"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="<?=$USER->id;?>">(YOU)</option>
                                                    <?php foreach ($Owners as $o ) {  ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Device</label>
                                                <select name="device"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="android">Android Apps / Users</option>
                                                    <option value="mag">Mag</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 col-xl-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status"  class="form-control select2">
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
                                        <th>Connections </th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Bouquet Package</th>
                                        <th>Expire On</th>
                                        <th>Last IP</th>
                                        <th>Last Activity</th>
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
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/main.js"></script>
<script src="/dist/js/user.js"></script>
<script>

    $("#toggleFilter").click(function() {
        $(".divFilters").toggle();
    });

    TABLEURL = "/user/IPTV/tables/gTableAccounts.php";
    var DataName = 'user.iptv.accounts';

    function resetMagDevice(account_id) {

        if(!confirm('Are you sure you want to reset mag device?'))
            return;

        $.ajax({
            url: 'ajax/pAccounts.php',
            type: 'post',
            dataType: 'json',
            data: {'action':'reset_mag', 'id': account_id },
            success: function (resp) {
                toastr.success("Mag have been reset.");
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

    function massRemoveAccounts(ids) {

        if(!confirm("Are you sure you want to remove the selected accounts?"))
            return;

        if( typeof ids == "string" )
            ids = [ids];


        $.ajax({
            url: 'ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: {action:'mass_account_remove' , ids },
            success: function (resp) {
                toastr.success("Account have been removed!");
                TABLE.ajax.reload();
                GetUserCredits();
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

    TABLE = $("#table").DataTable({
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
            url: TABLEURL
        },
        "stateSaveCallback": function (settings, data) {
            window.localStorage.setItem(DataName+"_datatable", JSON.stringify(data));
        },
        "stateLoadCallback": function (settings) {
            return JSON.parse(window.localStorage.getItem(DataName+"_datatable"));
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
            {"targets": 4, "orderable": false },
            {"targets": 5, "orderable": false },
            {"targets": 6, "orderable": false },
            {"targets": 8, "orderable": false },
            {"targets": 9, "orderable": false },
            {"targets": 10, "orderable": false },
            {"targets": 11, "orderable": false },
            {"targets": 12, "orderable": false }
        ],
        order: [[0, 'desc']],
        select: {
            style: 'multi'
        },
        buttons: [

            {
                text: 'Select/Unselect All',
                action: function (e, dt, node, config) {
                    if (TABLE.rows({selected: true}).count() < 1) {
                        TABLE.rows().select();
                    } else {
                        TABLE.rows().deselect();
                    }
                }
            },

            {
                text: 'Edit Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLE.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLE.row(rowIdx).index();
                        post_id[i] = TABLE.row(rowIdx).column(0).cell(idx,0).data();
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
                    TABLE.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLE.row(rowIdx).index();
                        post_id[i] = TABLE.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );
                    if(i > 0) {
                        massRemoveAccounts(post_id);
                    }

                }
            },
        ],

    });

    TABLE.button(1).enable(false);
    TABLE.button(2).enable(false);

    TABLE.on('select', function (e, dt, type, indexes) {
        TABLE.buttons().enable(true);

    }).on('deselect', function (e, dt, type, indexes) {
        if (TABLE.rows({selected: true}).count() < 1) {
            TABLE.button(1).enable(false);
            TABLE.button(2).enable(false);
        }
    });


    var realTime = false;
    function RealTime() {
        if(realTime)
            TABLE.ajax.reload(function(){
                setTimeout(function() {
                    RealTime();
                }, 5000)
            }, false);
    }

    $("#realtime li a").click(function () {
        if(!realTime) {
            realTime = true;
            RealTime()
            localStorage.setItem(DataName+"_realtime", true);
        } else {
            localStorage.setItem(DataName+"_realtime", false);
            realTime = false;
        }
    });

    $(document).ready(function(){

        if(localStorage.getItem(DataName+"_realtime") === "true") {
            realTime = true;
            RealTime();
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

    });

</script>
</body>
</html>