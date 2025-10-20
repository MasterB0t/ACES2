<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(""))
    Redirect("/admin/profile.php");

$PageTitle = "Add Admin";
if($EditID = @(int)$_GET['id']) {
    $admin = new \ACES2\Admin($EditID);
    $PageTitle = "Edit Admin";
}


$db = new \ACES2\DB();
$r=$db->query("SELECT id,name FROM `admin_groups` ");

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
                            <li class="breadcrumb-item active"><a href="../admins">Admins</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formAdmin">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_admin" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_admin" />
                <?php } ?>
                <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.admin');?>" />
                <div class="row">
                    <div class="col-12 col-md-8 offset-2">
                        <div class="card">

                            <div class="card-body">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control"  />
                                </div>

                                <div class="form-group">
                                    <label>Username</label>
                                    <input required type="text" name="username" class="form-control" />
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" />
                                </div>

                                <div class="form-group">
                                    <label>Password</label>
                                    <div class="input-group ">
                                        <input type="text" name="password" class="form-control">
                                        <span class="input-group-append">
                                        <button onclick="randomPassword()" type="button" class="btn btn-info btn-flat">Random</button>
                                    </span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Admin Group</label>
                                    <select name="group_id" class="form-control select2">
                                        <option value="0">[FULL ADMIN]</option>
                                        <?php
                                        while($o = $r->fetch_assoc()) {
                                            echo "<option value='{$o['id']}'> {$o['name']} </option>";
                                        }?>
                                    </select>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-sm float-right">Save</button>
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

    function randomPassword() {
        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        pass = "";
        for(x=0;x<8;x++)
        {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
        }
        $("#formAdmin input[name='password']").val(pass);
    }

    $(document).ready(function(){

        <?php if($EditID) { ?>

        $("#formAdmin input[name='name']").val('<?=$admin->name;?>');
        $("#formAdmin input[name='username']").val('<?=$admin->username;?>');
        $("#formAdmin input[name='email']").val('<?=$admin->email;?>');
        $("#formAdmin select[name='group_id']").val(<?=$admin->group_id;?>);
        $("#formAdmin input[name='password']").prop('placeholder','Leave it blank for no change.');

        <?php }  ?>

        $(".select2").select2();

    });

    $("#formAdmin").submit(function(e) {

        e.preventDefault();
        $.ajax({
            url: '../ajax/admin/pAdmin.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../admins.php" }, 1200)
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