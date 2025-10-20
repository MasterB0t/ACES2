<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$PageName = "Provider Content";

$db = new \ACES2\DB();
$r_providers = $db->query("SELECT id,host as name FROM iptv_xc_videos_imp ");

//ALL CATS
$r_categories = $db->query("SELECT category_name as name,category_id as id FROM iptv_provider_content_vod GROUP BY category_name ORDER BY category_name");

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
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul id="realtime" class="nav nav-sm nav-pills ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a id="toggleFilter" class="btn btn-primary btn-sm float-right ">
                            <i class="fa fa-filter"></i></a>

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
                            <div style="display:none;" class="collapse card-body" >
                                <div id="provider_update_body"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <!-- FILTERS -->
                            <div class="card-header divFilters">

                                <h5>Filters
                                    <a datatable-clear-filter="#table"
                                            class="btn btn-sm btn-danger float-right btnClearFilters">Clear Filters</a>
                                </h5>

                                <form datatable-filter="#table" id="formFilters">

                                    <div class="row">
                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status"  class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="added">Added</option>
                                                    <option value="not_added">Not Added</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Provider</label>
                                                <select name="provider" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_providers->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Type</label>
                                                <select name="type" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="movies">Movies</option>
                                                    <option value="series">Series</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Category</label>
                                                <select name="category" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_categories->fetch_assoc()) { ?>
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
                                        <th>Provider ID</th>
                                        <th>Provider</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Added</th>
                                        <th>Added Time</th>
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
    TABLES;
    var DataName = "admin.provider_content";

    function stopProgress( pid ) {
        if(!confirm("Are you sure you want to stop this process ?"))
            return false;

        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'stop_process', process_id : pid },
            success: function (resp) {
                toastr.success("Process have been stopped.");
                getProgress();
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

    function getFilterCats(pid=0) {
        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: { action: 'get_provider_categories', provider_id : pid },
            success: function (resp) {

                $("select[name='category']").empty();
                $("select[name='category']").append($('<option>', {
                    value: '',
                    text: 'All Categories'
                }));

                for( i=0; resp.data.length > i; i++) {

                    opt = resp.data[i];

                    $("select[name='category']").append($('<option>', {
                        value: opt.id,
                        text: opt.name
                    }));
                }


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

    function getProgress() {

        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: { action: 'get_progress' },
            success: function (resp) {

                $("#provider_update_body").html('');

                for(i=0;i<resp.data.length;i++) {
                    var importer = resp.data[i];

                    if( $("#process").length > 0 ) {
                        $(".progress"+importer.id).find('progress-bar').style('width',importer.progress + "%");
                    } else {
                        htm = '';
                        htm += '<div id="process'+importer.id+'" class="progress-group "><span class="progress-text ">'+importer.description+'</span>' +
                            '<span class="progress-number float-right"><a href=#! onclick="stopProgress('+importer.id+')">Stop It</a>' +
                            '</span>';
                        htm += '<div class="progress sm"> ' +
                            '<div class="progress-bar progress-bar-green" style="width: '+importer.progress+'%"></div>' +
                            '</div></div>';

                        $("#provider_update_body").html(htm);
                    }

                    if( resp.data.length > 0 ) {
                        $("#provider_update").show();

                    } else {
                        $("#provider_update").hide();
                    }

                }

            }, error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                }
            }
        });
    }
    setInterval(getProgress, 5000 );
    getProgress();


    var realTime = false;
    function RealTime() {
        if(realTime)
            TABLES.ajax.reload(function(){
                setTimeout(function() {
                    RealTime();
                }, 5000);
            }, false);
    }

    $("#realtime li a").click(function () {
        if(!realTime) {
            realTime = true;
            RealTime()
            localStorage.setItem("iptv."+DataName+"_realtime", true);
        } else {
            localStorage.setItem("iptv."+DataName+"_realtime", false);
            realTime = false;
        }
    });


    $("select[name='provider']").change(function(){
        getFilterCats($(this).val());
    });

    $(document).ready(function () {

        $(".select2").select2();

        if(localStorage.getItem("iptv."+DataName+"_realtime") === "true") {
            realTime = true;
            RealTime();
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }


    });

    TABLES = $("#table").DataTable({
        "ajax": {
            url: "/admin/IPTV/tables/gTableProviderContent.php"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 4, "orderable": false },
            {"targets": 5, "orderable": false },
            {"targets": 6, "orderable": false },
            {"targets": 8, "orderable": false },
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
                text: 'Add Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );

                    if(i>0)
                        MODAL("modals/mProviderContent.php?ids="+post_id);

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