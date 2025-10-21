<?php
    $ADMIN = new \ACES2\Admin();
    if(!$ADMIN->is_logged)
        Redirect("../login.php");

    if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS))
        Redirect("../profile.php");

    $db = new \ACES2\DB();

    $r_server = $db->query("SELECT id,name FROM iptv_servers ");
    $r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");
    $r_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");
    $r_stream_profiles = $db->query("SELECT id,name FROM iptv_stream_options 
               WHERE only_chan_id = 0 ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Streams </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="../../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css ">
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
                        <h1>Streams</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active">Streams</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                        <ul datatable-realtime="#table" class="nav nav-sm nav-pills ml-auto pt-0 float-right ">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab">Off</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link"  data-toggle="tab">Live Data</a>
                            </li>
                        </ul>

                        <div>
                            <a class="btn btn-sm btn-primary float-right ml-1 mr-1" data-toggle="dropdown"><i class="fa fa-play"></i></a>
                            <ul  class="dropdown-menu border-0 shadow">
                                <li><a onclick="sendAction('start_all_channels')" href="#" class="dropdown-item">Start All Streams</a></li>
                                <li><a onclick="sendAction('stop_all_channels')" href="#" class="dropdown-item">Stop All Streams</a></li>
                                <li><a onclick="sendAction('restart_all_channels')" href="#" class="dropdown-item">Restart All Streams</a></li>
                            </ul>
                        </div>



                        <div>
                            <a class="btn btn-sm btn-primary float-right ml-1 mr-1" data-toggle="dropdown"><i class="fa fa-wrench"></i></a>
                            <ul  class="dropdown-menu border-0 shadow">
                                <li><a onclick="MODAL('modals/streams/mDnsReplace.php')" href="#" class="dropdown-item">DNS Replace</a></li>
                            </ul>
                        </div>

                        <div>
                            <a onclick="$('.divFilters').toggle();" class="btn btn-sm btn-primary float-right ml-1 mr-1 ">
                                <i class="fa fa-filter"></i></a>
                        </div>

                        <div >
                            <a  class=" btn btn-sm btn-primary float-right ml-1 mr-1" data-toggle="dropdown">ADD CHANNEL/STREAM</a>
                            <ul  class="dropdown-menu border-0 shadow">
                                <li><a href="form/streams/formStream.php" class="dropdown-item">Add Stream</a></li>
                                <li><a href="form/streams/formChannel.php" class="dropdown-item">Add Channel 24/7</a></li>
                                <li><a onclick="MODAL('modals/streams/mImportM3U.php', { size:'large' })" href="#" class="dropdown-item">Import Playlist</a></li>
                                <li><a onclick="MODAL('modals/XC/mXCAccounts.php?import_streams', { size:'large' })" href="#" class="dropdown-item">Import From XC</a></li>
                            </ul>
                        </div>



                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <!-- HEADER BOXES -->
                <div class="row">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div id="streams-box" class="info-box bg-green ">
                            <span class="info-box-icon"><i class="fa fa-video-camera"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Running Streams</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">
                                  Loading...
                                </span>
                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12" title='Amount of active accounts.'>
                        <div id="clients-box" class="info-box bg-blue">
                            <span class="info-box-icon"><i class="fa fa-desktop"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active Accounts</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">
                                  Loading...
                                </span>
                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12" title='Amount of connections.'>
                        <div id="connections-box" class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-plug"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Connections</span>
                                <span class="info-box-number">0/0</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <a onclick=" window.open('<?php HOST ?>/admin/IPTV/tb_stream_clients.php','Account Connections',
                                        'width=700,height=500,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,left=100,top=100');return false;"  style="cursor:pointer; color:white;" class="progress-description">
                                    Loading...
                                </a>
                            </div><!-- /.info-box-content -->
                        </div><!-- /.info-box -->
                    </div>

                </div>

                <div class="row importRow">
                    <div class="col-12">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div style="display:none;" class="card-header divFilters">
                                <h5>Filters
                                    <button datatable-clear-filter="#table" class="btn btn-danger btn-sm float-right btnClearFilters">
                                        Clear Filters</button>
                                </h5>

                                <!-- FILTERS -->
                                <form datatable-filter="#table" id="formFilters">

                                    <input type="hidden" name="conns" value="" />

                                    <div class="row pt-3">

                                        <div class="d-none col-md-6 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Stream ID</label>
                                                <input disabled class="form-control" name="stream_id" />
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select  name="status" class="form-control select2">
                                                    <option value="">All </option>
                                                    <option value="streaming">Streaming </option>
                                                    <option value="connecting">Connecting </option>
                                                    <option value="ondemand">On Demand</option>
                                                    <option value="stopped">Stopped</option>
                                                    <option value="connected_backup">Connected To Backup Source</option>
                                                    <option value="redirect">Redirect Streams</option>
                                                    <option value="disabled">Disabled</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Filter Server</label>
                                                <select  name="server" class="form-control select2">
                                                    <option value="">All Servers</option>
                                                    <?php while($opt = $r_server->fetch_assoc()) { ?>
                                                        <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-5 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Filter In Bouquets</label>
                                                <select name="bouquets" class="form-control select2">
                                                    <option value="">In All Bouquets</option>
                                                    <?php while($opt = $r_bouquets->fetch_assoc()) { ?>
                                                        <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-5 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Filter By Category</label>
                                                <select  name="category" class="form-control select2">
                                                    <option value="">In All Categories</option>
                                                    <?php while($opt = $r_cats->fetch_assoc()) { ?>
                                                        <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-5 col-lg-4 col-xl-3">
                                            <div class="form-group">
                                                <label>Filter By Stream Profile</label>
                                                <select  name="stream_profile" class="form-control select2">
                                                    <option value="">All Stream Profile</option>
                                                    <?php while($opt = $r_stream_profiles->fetch_assoc()) { ?>
                                                        <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
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
                                        <th>Connection</th>
                                        <th>Up/Down Time</th>
                                        <th>Clients</th>
                                        <th>Reconnect</th>
                                        <th>Codecs</th>
                                        <th>Bitrate</th>
                                        <th>Server</th>
                                        <th>Stream Profile</th>
                                        <th>Category</th>
                                        <th>Edit</th>
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
        TABLES.ajax.reload();
    }

    function removeStream(stream_id) {

        if(!confirm("Are you sure you want to remove this Stream/Channel?"))
            return;

        $.ajax({
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: { action : 'remove_stream', 'stream_id': stream_id },
            success: function (resp) {

                setTimeout(reloadTable, 1000 );
                toastr.success("Stream Removed");
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

    function sendAction(action, data, postFunc) {

        var post = Object.assign({ action : action} , data )

        $.ajax({
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: post,
            success: function (resp) {
                setTimeout(reloadTable, 1000 );
                if(typeof postFunc === 'function')
                    postFunc();

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

    function getImports() {
        $.ajax({
            url: 'ajax/streams/gGetImports.php',
            type: 'get',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                i=0;

                $(".importRow .col-12").html('');
                if(resp.length > 0) {
                    htm = '';
                    while (i < resp.length) {
                        obj = resp[i];
                        htm += '<div class="info-box bg-info">' +
                            '<span class="info-box-icon"><i class="fa fa-download"></i></span>' +
                            '<div class="info-box-content">' +
                            '<span class="info-box-text">Importing '+obj.description+'</span>' +
                            ' <span class="info-box-number"></span>' +

                            '<div class="progress">' +
                            '<div class="progress-bar" style="width: '+obj.progress+'%"></div>' +
                            '</div>' +
                            '<span class="progress-description">' +
                            '<a onclick="sendAction(\'stop_importer\', {importer_id:'+obj.id+'}, getImports )" href=#!> Stop Import </a>' +
                            '</span>' +
                            '</div>' +
                            '</div>';



                        i++;
                    }

                    $(".importRow .col-12").html(htm);
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

    function massEdit(act,post) {

        var data;

        if(act == 'restart') data = { action : 'mass_restart' , channels : post };
        else if(act == 'stop') data = { action : 'mass_stop', channels : post };
        else if(act == 'remove') data = { action : 'mass_remove', channels : post };
        else return false;

        $.ajax({
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: data,
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
                        if (response.error)
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");

                }
            }
        });

    }

    function restartStream(id) { massEdit("restart", [id]); }
    function stopStream(id) { massEdit("stop", [id]); }

    function getStats() {
        $.ajax({
            url: "ajax/streams/gStats.php",
            type: 'post',
            dataType: 'json',
            data: { },
            success: function(resp) {

                data = resp.data;

                $("#streams-box .info-box-number").html(data.online_streams+'/'+data.total_running_streams);
                $("#streams-box .progress-bar ").css('width', data.online_streamms_percent+'%');
                $("#streams-box .progress-description").html(data.online_streamms_percent+"% Streams Connected");

                $("#clients-box .info-box-number").html(data.active_clients+'/'+data.total_clients);
                $("#clients-box .progress-bar ").css('width', data.active_clients_percent+'%');
                $("#clients-box .progress-description").html(data.active_clients_percent+"% Clients Active");


                $("#connections-box .info-box-number").html(data.active_connections+'/'+data.total_connections);
                $("#connections-box .progress-bar ").css('width', data.active_connections_percent+'%');
                $("#connections-box .progress-description").html(data.active_connections_percent+"% Active Connections " +
                    "<i class='mr-5 fa fa-hand-point-up'></i>" );


            }
        });
    }

    $(document).ready(function () {

        $(".select2").select2();

        getImports();
        setInterval(getImports, 5000 );

        getStats();
        setInterval(getStats, 5000);

    });

    TABLES = $("#table").DataTable({

        "ajax": {
            url: "/admin/IPTV/tables/gTableStreams.php"
        },
        columnDefs: [{
            className: 'select-checkbox'
        }],
        select: {
            style: 'multi'
        },
        order: [[0, 'asc']],
        columns: [
            {responsivePriority: 3},
            {responsivePriority: 1},
            {responsivePriority: 11},
            {responsivePriority: 1},
            {responsivePriority: 3},
            {responsivePriority: 3},
            {responsivePriority: 4},
            {responsivePriority: 11},
            {responsivePriority: 11},
            {responsivePriority: 5},
            {responsivePriority: 10},
            {responsivePriority: 10},
            {responsivePriority: 3},

        ],

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
                text: 'Restart',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );
                    massEdit('restart',post_id);
                    setTimeout(reloadTable, 1000);

                }
            },

            {
                text: 'Stop',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );
                    massEdit('stop',post_id);
                    setTimeout(reloadTable, 1000);

                }
            },
            {
                text: 'Remove Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;

                    if(!confirm('Are you sure you want to remove selected channels ?')) return false;

                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;


                    });
                    massEdit('remove',post_id);
                    setTimeout(reloadTable, 1000);

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

                    MODAL("modals/streams/mEditSelected.php?ids="+post_id);

                }
            },

            {
                text: 'Load Balance Selected',
                action: function ( e, dt, node, config ) {

                    var post_id = [];
                    var i = 0;
                    TABLES.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                        idx = TABLES.row(rowIdx).index();
                        post_id[i] = TABLES.row(rowIdx).column(0).cell(idx,0).data();
                        i++;
                    } );

                    MODAL("modals/mStreamLoadBalance.php?ids="+post_id);

                }
            },

        ]
    });

    TABLES.on('preXhr', function () {
        let dataScrollTop = $(window).scrollTop();
        TABLES.one('draw', function () {
            $(window).scrollTop(dataScrollTop);
        });
    });

    TABLES.button(1).enable(false);
    TABLES.button(2).enable(false);
    TABLES.button(3).enable(false);
    TABLES.button(4).enable(false);

    TABLES.on('select', function (e, dt, type, indexes) {
        TABLES.buttons().enable(true);
        TABLES.button(5).enable(true);

    }).on('deselect', function (e, dt, type, indexes) {
        if (TABLES.rows({selected: true}).count() < 1) {
            TABLES.button(1).enable(false);
            TABLES.button(2).enable(false);
            TABLES.button(3).enable(false);
            TABLES.button(4).enable(false);
        }
    });

</script>
</body>
</html>