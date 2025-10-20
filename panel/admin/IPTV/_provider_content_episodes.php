<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$PageName = "Provider Content Episodes";

$db = new \ACES2\DB();
$r_series = $db->query("SELECT id,name  FROM iptv_provider_content_vod 
                WHERE is_series = 1 GROUP BY name ORDER BY name ASC ");



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
                            <li class="breadcrumb-item active"><a href="providers.php">Providers</a></li>
                            <li class="breadcrumb-item active"><a href="provider_content.php">Provider Content</a></li>
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
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

                        <a id="toggleFilter" class="btn btn-success float-right">
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

                                <form id="formFilters">

                                    <div class="row">
                                        <div class="col-6 col-md-4 ">
                                            <div class="form-group">
                                                <label>Series</label>
                                                <select name="series" onchange="filter()" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o=$r_series->fetch_assoc()) {?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 ">
                                            <div class="form-group">
                                                <label>Season </label>
                                                <select name="season" onchange="filter()" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php for($i=1;$i<50;$i++) {?>
                                                        <option value="<?=$i;?>">Season <?=$i?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-4 ">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" onchange="filter()" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="added">Added</option>
                                                    <option value="not_added">Not Added</option>
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
                                        <th>Logo</th>
                                        <th>Name</th>
                                        <th>Episode Number</th>
                                        <th>Season Number</th>
                                        <th>Series</th>
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
    var table;
    var pagetitle = "provider_content_episodes";

    function reloadTable() {
        table.ajax.reload(null, false);
    }

    function filter() {
        args = "";

        $('#formFilters select').each(function(){
            if($(this).val()) {
                name = $(this).attr('name');
                args += "&filter_" + name + "=" + $(this).val()
            }
        });

        table.ajax.url("/admin/IPTV/tables/gTableProviderContentEpisodes.php?"+args);
        reloadTable();
        table.page(0);

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
                url: "/admin/IPTV/tables/gTableProviderContentEpisodes.php"
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
                if (json.not_logged == 1) window.location.reload();
            },
            columnDefs: [
                { className: 'select-checkbox'},
                {"targets": 6, "orderable": false },
            ],
            order: [[0, 'asc']],
            select: {
                style: 'multi'
            },
            buttons: [

                {
                    text: 'Select/Unselect All',
                    action: function (e, dt, node, config) {
                        if (table.rows({selected: true}).count() < 1) {
                            table.rows().select();
                        } else {
                            table.rows().deselect();
                        }
                    }
                },

                {
                    text: 'Edit Selected',
                    action: function ( e, dt, node, config ) {

                        var post_id = [];
                        var i = 0;
                        table.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                            idx = table.row(rowIdx).index();
                            post_id[i] = table.row(rowIdx).column(0).cell(idx,0).data();
                            i++;
                        } );

                        if(i>0)
                            MODAL("modals/?ids="+post_id);

                    }
                },

                {
                    text: 'Remove Selected',
                    action: function ( e, dt, node, config ) {

                        var post_id = [];
                        var i = 0;
                        table.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                            idx = table.row(rowIdx).index();
                            post_id[i] = table.row(rowIdx).column(0).cell(idx,0).data();
                            i++;
                        } );
                        if(i > 0)
                            MODAL("modals/?ids="+post_id);
                    }
                },
            ],



        });

        table.on('preXhr', function () {
            let dataScrollTop = $(window).scrollTop();
            table.one('draw', function () {
                $(window).scrollTop(dataScrollTop);
            });
        });

        table.button(1).enable(false);
        table.button(2).enable(false);

        table.on('select', function (e, dt, type, indexes) {
            table.buttons().enable(true);

        }).on('deselect', function (e, dt, type, indexes) {
            if (table.rows({selected: true}).count() < 1) {
                table.button(1).enable(false);
                table.button(2).enable(false);
            }
        });

        if(localStorage.getItem("iptv."+pagetitle+"_realtime") === "true") {
            setRealTime(true)
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

        <?php if($_GET['provider_id']) {  ?>
            $(".divFilters").show();
            //$("select[name='provider']").val('<?=(int)$_GET['provider_id'];?>');
            $("select[name='series']").val('<?=(int)$_GET['series_id'];?>');
            filter();
        <?php } ?>

        $(".select2").select2();

    });


</script>
</body>
</html>