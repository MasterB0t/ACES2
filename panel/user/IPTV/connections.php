<?php

$DB = new \ACES2\DB ();

if (!$UserID=userIsLogged()) die;
//if(!ACESiSAdminisAdminPrivilege('iptv.streams') && !ACESiSAdminisAdminPrivilege('iptv.streams_full')) die;

$User = new \ACES2\IPTV\Reseller2($UserID);

$StreamID = (INT)$_GET['stream_id'];

$u_query = '';
$title = 'All Connections';
if( $DeviceID = (INT)$_GET['device_id']) {
    if(!$User->canManageAccount($DeviceID))
        die;
    $Device = new \ACES2\IPTV\Account($DeviceID);
    $title = "Connections of Account '$Device->name'";
    $u_query = "device_id=$Device->id";
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$title?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css">
    <!-- Flags Icons -->
    <link rel="stylesheet" href="/plugins/flag-icon-css/css/flag-icons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
</head>
<body>
<style>

    * { margin:0; padding:0; }

    table { width:100%;  border-collapse: collapse; }
    th, td { border-bottom: 1px solid #ddd; text-align: left; padding: 6px; }

    tr:hover {background-color: #f5f5f5 ;}

    .head { width:100%; }
    h2 { padding:20px; }
    button { float:right; margin:10px;cursor:pointer;}
    .clr { clear:both;}
    .red { color:red; }

    a { color:red; }

</style>


<h2 align="center"> <?=$title?> </h2>
<button class="red" onClick="stopAll('<?php echo 0; ?>');" >Stop All </button>
<p class="clr"> </p>

<div class="wrapper">
    <div class="row p-3">
        <div class="col-12">
            <table id="table" class="table table-hover">
                <thead>
                <tr>
                    <th>Account</th>
                    <th>Stream</th>
                    <th>Client IP</th>
                    <th>Server</th>
                    <th>Uptime</th>
                    <th>User Agent</th>
                    <th>Stream Format</th>
                    <th>Start Time</th>
                    <th>Stop Connection</th>
                </tr>
                </thead>
                <tbody>
                <tr></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<script>

    var table;
    var realtimeTimer;
    var realTime = false;

    function reloadTable() {
        table.ajax.reload(function() {
            setTimeout(reloadTable, 3000);
        }, false);
    }

    function stopAll() {

        if(!confirm("Are you sure you want to stop all connections? "))
            return false;

        post = {} ;

        <?php if($DeviceID>0) { ?>
            post = { action : 'stop_from_device', 'device_id' : <?=$DeviceID?> } ;
        <?php } else { ?>
            post = { action : 'stop_all_connections' };
        <?php } ?>


        $.ajax({
            url: 'ajax/pConnections.php',
            type: 'post',
            dataType: 'json',
            data: post,
            success: function (resp) {
                reloadTable();
                toastr.success("Connection Stopped.");
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

    function stopConnection(id) {

        $.ajax({
            url: 'ajax/pConnections.php',
            type: 'post',
            dataType: 'json',
            data: { action : 'stop_connection', 'connection_id' : id },
            success: function (resp) {
                reloadTable();
                toastr.success("Connection Stopped.");
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


    $(function () {

        table = $("#table").DataTable({
            layout: {
                top2End: {
                    pageLength: {
                        menu: [ 10, 25, 50, 100, 1000  ]
                    }
                },
                topStart: 'info',

                topEnd: 'search',
                bottomStart: null,
                bottom2Start: 'info',
                bottom2End: null
            },
            "responsive": true,
            "autoWidth": false,
            "info": true,
            "serverSide": true,
            "stateSave": true,
            "ordering": true,
            "searching": true,
            "ajax": {
                url: "/user/IPTV/tables/gTableConnections.php?<?=$u_query;?>"
            },
            order: [[0, 'asc']],

        });

        reloadTable();

    });

</script>
</body>
</html>