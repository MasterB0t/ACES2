<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission())
    Redirect("/admin/profile.php");

$ssh_port = 22;
$bandwidth = 1000;
$PageTitle = "Add Server";
if($EditID = @(int)$_GET['server_id']) {
    $PageTitle = "Edit Server";
    $Server = new \ACES2\IPTV\Server($EditID);
    $ssh_port = $Server->ssh_port;
    $bandwidth = $Server->network_port;
}




?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| <?=$PageTitle;?> </title>
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

<body class="hold-transition sidebar-mini text-sm">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../../header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include '../../sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?=$PageTitle;?></h1>
                        <ol class="breadcrumb ">
                            <li class="breadcrumb-item active"><a href="../servers.php">Servers</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="form" type="submit" class="btn btn-primary btn-sm float-right">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../servers.php"
                           type="button" class="btn btn-default float-right btn-sm mr-3">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="form">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_server" />
                    <input type="hidden" name="server_id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_server" />
                <?php } ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Server Name</label>
                                            <input class="form-control" name="name" value="<?=$Server->name;?>" />
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Server IP-Address</label>
                                            <input <?= $EditID ? 'readonly' : '' ?>  class="form-control" name="ip_address"
                                                   value="<?=$Server->ip_address;?>"/>
                                        </div>
                                    </div>


                                    <?php if($EditID) { ?>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Server Address/DNS</label>
                                                <input class="form-control" name="address" value="<?=$Server->address;?>"/>
                                            </div>
                                        </div>

                                    <?php } else  { ?>

                                        <div class="col-12 col-md-4">
                                            <div class="form-group">
                                                <label>Serial</label>
                                                <input class="form-control" required name="serial" value=""/>
                                            </div>
                                        </div>

                                    <?php }  ?>

                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>SSH Password</label>
                                            <input type="password" name="ssh_password" class="form-control" placeholder=""/>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>SSH Port</label>
                                            <input type="number" name="ssh_port" class="form-control" value="<?=$ssh_port;?>"/>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Bandwidth Port (Megabits)</label>
                                            <input type="number" name="bandwidth_port" class="form-control" value="<?=$bandwidth;?>" />
                                        </div>
                                    </div>

                                </div>


                            </div>


                        </div>
                    </div>
                </div>
                <div class="row pb-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm float-right mr-2">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../servers.php"
                           type="button" class="btn btn-default btn-sm float-right mr-3">Go Back</a>
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include '../../footer.php'; ?>

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

    $(document).ready(function(){


        <?php if($EditID) { ?>
            $("input[name='ssh_password']").prop('placeholder', 'Leave it blank for no change.');

        <?php }  ?>

        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

    });

    $("#form").submit(function(e) {


        e.preventDefault();
        $.ajax({
            url: '../ajax/pServer.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../servers.php" }, 1200)
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

    });


</script>
</body>
</html>