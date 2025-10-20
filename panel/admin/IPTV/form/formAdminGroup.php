<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(""))
    Redirect("/admin/profile.php");

$PageTitle = "Add Admin Group";
if($EditID = @(int)$_GET['id']) {
    $Group = new \ACES2\AdminGroup($EditID);
    $PageTitle = "Edit Admin Group";
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
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active"><a href="../admins.php">Admins</a></li>
                            <li class="breadcrumb-item active"><a href="../admin_group.php">Admin Groups</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formAdminGroup">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_admin_group" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_admin_group" />
                <?php } ?>
                <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.admin');?>" />
                <div class="row">
                    <div class="col-12 col-md-8 offset-2">
                        <div class="card">

                            <div class="card-body">

                                <div class="form-group">
                                    <label>Group Name</label>
                                    <input type="text" name="name" class="form-control" />
                                </div>

                                <h5>Stream Permissions</h5>
                                <div class="row pl-4 pr-4 pb-4">
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.streams]" />
                                        <label for="chkStream">Allow Manage Streams (Start, Restart, Stop)</label>
                                        <input id="chkStream" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.streams]" />
                                    </div>
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.streams_full]" />
                                        <label for="chkFullStream">Allow Full Manage Streams (Start, Restart, Stop, Add, Edit)</label>
                                        <input id="chkFullStream" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.streams_full]" />
                                    </div>

                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.bouquets]" />
                                        <label for="chkFullBouquets">Allow Full Manage Bouquets And Packages</label>
                                        <input id="chkFullBouquets" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.bouquets]" />
                                    </div>
                                </div>

                                <h5>Accounts Permissions</h5>
                                <div class="row pl-4 pr-4 pb-4">
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.accounts]" />
                                        <label for="chkAccounts">Allow View Accounts</label>
                                        <input id="chkAccounts" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.accounts]" />
                                    </div>
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.accounts_full_manage]" />
                                        <label for="chkAccountsFull">Allow Manage Accounts</label>
                                        <input id="chkAccountsFull" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.accounts_full_manage]" />
                                    </div>
                                </div>


                                <h5>Movies/Series Permissions</h5>
                                <div class="row pl-4 pr-4 pb-4">
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.ondemand]" />
                                        <label for="chkOndemand">Allow View Movies and Series</label>
                                        <input id="chkOndemand" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.ondemand]" />
                                    </div>
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.ondemand_full]" />
                                        <label for="chkOndemandFull">Allow Manage Movies and Series</label>
                                        <input id="chkOndemandFull" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.ondemand_full]" />
                                    </div>
                                </div>

                                <h5>Reseller Permissions</h5>
                                <div class="row pl-4 pr-4 pb-4">
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.resellers]" />
                                        <label for="chkReseller">Allow View Resellers</label>
                                        <input id="chkReseller" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.resellers]" />
                                    </div>
                                    <div class="form-group form-group-bs">
                                        <input type="hidden" name="permission[iptv.reseller_full]" />
                                        <label for="chkResellerFull">Allow Manage Resellers</label>
                                        <input id="chkResellerFull" type="checkbox" class="boostrap-switch"
                                               name="permission[iptv.reseller_full]" />
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-success float-right">Save</button>
                            </div>

                        </div>
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

            $("#formAdminGroup input[name='name']").val('<?=$Group->name;?>');
            <?php foreach ($Group->permissions as $k => $v) {
                if($v) { ?>
                    $("#formAdminGroup input[type=checkbox][name='permission[<?=$k?>]']").prop('checked', true);
                <?php } ?>
            <?php } ?>

        <?php } ?>

        $(".boostrap-switch").bootstrapSwitch();
    })

    $("#formAdminGroup").submit(function(e) {

        e.preventDefault();
        $.ajax({
            url: '../ajax/admin/pAdmin.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../admin_group.php" }, 1500)
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