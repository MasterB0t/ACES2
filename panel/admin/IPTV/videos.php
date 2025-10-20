<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD))
    Redirect("/admin/profile.php");

$PageName = "Videos";

$db = new \ACES2\DB();
$r_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");
$r_servers = $db->query("SELECT id,name FROM iptv_servers  ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?> | <?=$PageName;?> </title>
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

                        <ul datatable-realtime="#table" class="nav nav-sm nav-pills ml-auto float-right pl-2 ">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <a data-toggle="dropdown"
                           class="btn btn-primary btn-sm float-right ml-2 ">Add new</a>
                        <ul  class="dropdown-menu border-0 shadow">
                            <li><a href="form/formVideo.php?type=movie" class="dropdown-item">Add Movie</a></li>
                            <li><a href="form/formVideo.php?type=series" class="dropdown-item">Add Series</a></li>
                            <li><a href="#" onclick="MODAL('modals/XC/mXCAccounts.php');" class="dropdown-item">Import From XC Api</a></li>
                            <li><a href="#" onclick="MODAL('modals/mPlexAccounts.php');" class="dropdown-item">Import From Plex Api</a></li>
                            <li><a href="#" onclick="MODAL('modals/mVideoFolderImport.php');" class="dropdown-item">Add Movies From Folder</a></li>
                            <li><a href="#" onclick="MODAL('modals/mVideoFolderImport.php?series=1');" class="dropdown-item">Add Series From Folder</a></li>
                            <li><a href="#" onclick="MODAL('modals/mVideoFolderImport.php?series=1&no_recursive=1');" class="dropdown-item">Add Series A From Folder</a></li>
                            <li><a href="#" onclick="MODAL('modals/mVideoM3uImport.php')" class="dropdown-item">Add From M3U Playlist</a></li>
                        </ul>

                        <a href="watch.php" title="watch.php" class="btn btn-sm btn-primary float-right ml-2 ">
                            <i class="fa fa-eye"></i></a>

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

                    <div style="display:none;" id="xc_importer" class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-header with-border">
                                <h3 class="card-title">XC Imports</h3>
                                <div class="card-tools pull-right">
                                    <button class="btn btn-box-tool"
                                            data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div style="display:none;" class="collapse card-body" >
                                <div id="xc_importer_body"></div>
                            </div>
                        </div>
                    </div>

                    <div style="display:none;" id="plex_importer" class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-header with-border">
                                <h3 class="card-title">Plex Imports</h3>
                                <div class="card-tools pull-right">
                                    <button class="btn btn-box-tool"
                                            data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div style="display:none;" class="collapse card-body">
                                <div id="plex_importer_body"></div>
                            </div>
                        </div>
                    </div>

                    <div style="display:none;" id="m3u_importer" class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-header with-border">
                                <h3 class="card-title">M3U Imports</h3>
                                <div class="card-tools pull-right">
                                    <button class="btn btn-box-tool"
                                            data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div style="display:none;" class="collapse card-body">
                                <div id="m3u_importer_body"></div>
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
                                    <button datatable-clear-filter="#table"
                                            class="btn btn-danger btn-sm float-right btnClearFilters">
                                        Clear Filters</button>
                                </h5>

                                <form datatable-filter="#table" id="formFilters">

                                    <div class="row">

                                        <div class="d-none col-6 col-md-3">
                                            <div class="form-group">
                                                <label>Video ID</label>
                                                <input disabled name="video_id" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="form-group">
                                                <label>Filter</label>
                                                <select name="type" class="form-control select2">
                                                    <option value="">All</option>
                                                    <option value="movies">Movies</option>
                                                    <option value="series">Series</option>
                                                    <option value="duplicated">Duplicated</option>
                                                    <option value="movie_failed">Movies Failed</option>
                                                    <option value="episodes_failed">Episodes Failed</option>
                                                    <option value="no_tmdb">No TMDB Info</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="form-group">
                                                <label>Filter Category</label>
                                                <select name="category" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_cats->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Filter Server</label>
                                                <select name="server" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_servers->fetch_assoc()) { ?>
                                                        <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3 ">
                                            <div class="form-group">
                                                <label>Filter Bouquet</label>
                                                <select name="bouquets" class="form-control select2">
                                                    <option value="">All</option>
                                                    <?php while($o = $r_bouquets->fetch_assoc()) { ?>
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
                                        <th>Logo</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Quality</th>
                                        <th>Play Count</th>
                                        <th>Server</th>
                                        <th>Add Date</th>
                                        <th>Release Date</th>
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
    var TABLES;

    function updateTMDBInfo(ids) {

        if(!confirm("Are you want to update the video info from TMDB ?"))
            return false;

        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'update_tmdb_info', ids : ids},
            success: function (resp) {
                toastr.success("Updating on background");
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

    function stopImporter(id) {
        $.post("ajax/pVideos.php",{action:'stop_importer',importer_id: id }, getImporters );
    }

    function getImporters() {
        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data:{ 'action': 'get_importers' },
            success: function (resp) {
                
                $("#xc_importer_body").html('');
                $("#plex_importer_body").html('');
                $("#m3u_importer_body").html('');

                for(i=0;i<resp.data.length;i++){
                    var importer = resp.data[i];

                    htm ='';

                    name = "";
                    if(importer.importing_movies)
                        imp = " Movies ";
                    else if(importer.importing_series )
                        imp = " Series "
                    else
                        imp = " Movies and Series ";

                    name ="<b> Importing "+imp+" From "+importer.host+" on server "+importer.server_name+"</b>"

                    htm += '<div class="progress-group"><span class="progress-text ">'+name+'</span>' +
                        '<span class="progress-number float-right"><a href=#! onclick="stopImporter('+importer.id+')">Stop It</a>' +
                        '</span>';
                    htm += '<div class="progress sm"> ' +
                        '<div class="progress-bar progress-bar-green" style="width: '+importer.progress+'%"></div>' +
                        '</div></div>';

                    $("#"+importer.type+"_importer_body").append(htm);

                }

                if($("#xc_importer_body").html().length > 1 ) {
                    $("#xc_importer").show();
                } else {
                    $("#xc_importer").hide();
                    $("#xc_importer_body").html('');
                }

                if($("#plex_importer_body").html().length > 1 ) {
                    $("#plex_importer").show();
                } else {
                    $("#plex_importer").hide();
                    $("#plex_importer_body").html('');
                }

                if($("#m3u_importer_body").html().length > 1 ) {
                    $("#m3u_importer").show();
                } else {
                    $("#m3u_importer").hide();
                    $("#m3u_importer_body").html('');
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

    getImporters();
    setInterval(getImporters, 5000);

    $(document).ready(function () {

        $(".select2").select2();

    });

    TABLES = $("#table").DataTable({
        "ajax": {
            url: "tables/gTableVideos.php"
        },
        columnDefs: [
            { className: 'select-checkbox'},
            {"targets": 1, "orderable": false },
            {"targets": 3, "orderable": false },
            {"targets": 4, "orderable": false },
            {"targets": 5, "orderable": false },
            {"targets": 6, "orderable": false },
            {"targets": 7, "orderable": false },
            {"targets": 10, "orderable": false },
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
                        MODAL("modals/mVideoMassEdit.php?ids="+post_id);

                }
            },

            {
                text: 'Update info From TMDB',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );
                    if(i > 0)
                        updateTMDBInfo(post_id);
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
                        MODAL("modals/mVideoMassRemove.php?remove_videos&ids="+post_id);
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
