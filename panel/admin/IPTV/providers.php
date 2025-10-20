<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$PageName = "Providers";



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
                    <div class="col-sm-6 ">

                        <ul id="realtime" class="nav nav-pills nav-sm ml-auto float-right pl-2">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a href="#" onclick="MODAL('modals/mXCAccount.php');"
                           class="btn btn-primary btn-sm float-right ml-2 ">Add new</a>

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row-process">

                </div>

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

                            <div class="card-body">
                                <table id="table" class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Connections</th>
                                        <th>Expire Date</th>
                                        <th>Streams</th>
                                        <th>Movies</th>
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
<script src="/dist/js/Process.js"></script>
<script>
    var table;
    var pagetitle = "providers";

    function removeProvider(account_id) {

        if(!confirm("Are you sure you want to remove this account ?"))
            return false;

        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'remove_provider_account', account_id : account_id },
            success: function (resp) {

                toastr.success("Account have been removed");
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

                        $("#provider_update_body").append(htm);
                    }


                }


                if( resp.data.length > 0 ) {
                    $("#provider_update").show();

                } else {
                    $("#provider_update").hide();
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
    //setInterval(getProgress, 5000 );
    //getProgress();

    function reloadTable() {
        table.ajax.reload(null, false);
    }

    function updateContent(acc_id) {
        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'update_content', id : acc_id},
            success: function (resp) {
                toastr.success("Content will be updated on background.");
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

    function clearContent(pid) {

        if(!confirm("Are you sure you want to clear all content from this provider?"))
            return false;

        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: { action: 'clear_content', provider_id : pid },
            success: function (resp) {
                toastr.success("Success")
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

    function forceRefresh(acc_id) {
        $.ajax({
            url: 'ajax/pXC.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'force_refresh', id : acc_id },
            success: function (resp) {
                toastr.success("Done");
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

    var realtimeTimer;
    var realTime = false;
    function setRealTime(activate) {
        clearInterval(realtimeTimer);
        if(activate) {
            realTime = true;
            reloadTable()
            realtimeTimer=setInterval(reloadTable,10000);
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

        PROCESS.get({  appendTo : '.row-process', processToGet : ['IPTV.UPDATE_PROVIDER_CONTENT'] })

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
            "stateSave": false,
            "ordering": true,
            "searching": true,
            pageLength: 50,
            "ajax": {
                url: "/admin/IPTV/tables/gTableProviders.php"
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
                if (json.not_logged == 1) window.location.reload();;
            },
            columnDefs: [
                { className: 'select-checkbox'},
                {"targets": 1, "orderable": false },
                {"targets": 2, "orderable": false },
                {"targets": 3, "orderable": false },
                {"targets": 4, "orderable": false },
                {"targets": 5, "orderable": false },
                {"targets": 6, "orderable": false },

            ],
            order: [[0, 'asc']],
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

        $(".select2").select2();

        if(localStorage.getItem("iptv."+pagetitle+"_realtime") === "true") {
            setRealTime(true)
            $("#realtime li:nth-child(2) a").addClass('active');
            $("#realtime li:nth-child(1) a").removeClass('active');
        }

    });


</script>
</body>
</html>