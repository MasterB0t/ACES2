<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS))
    Redirect("/admin/profile.php");

$ids = null;
$PageTitle = "Add Reseller";
if($EditID = @(int)$_GET['id']) {
    $Reseller = new \ACES2\IPTV\Reseller2($EditID);
    $PageTitle = "Edit Reseller '$Reseller->username' ";
}

$db = new \ACES2\DB;
$r=$db->query("SELECT id,username FROM users WHERE id != '$Reseller->id'");
$users = $r->fetch_all();

$r_credits = $db->query("SELECT * FROM iptv_bouquet_packages ");

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
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item active"><a href="../resellers.php">All Resellers</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button type="submit" form="formReseller" class="btn-submit btn btn-primary btn-sm float-right ml-3">Save </button>
                        <a href="../resellers.php" class="btn btn-default btn-sm float-right">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formReseller">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_reseller" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_reseller" />
                <?php } ?>
                <div class="row">

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Reseller Info</h5>
                            </div>
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
                                                    <button onclick="randomPassword()" type="button"
                                                            class="btn btn-info btn-flat">Random</button>
                                                </span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control select2" >
                                        <option value="1">Enabled</option>
                                        <option value="0">Disabled</option>
                                        <option value="2">Blocked</option>
                                    </select>
                                </div>



                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Reseller Of</label>
                                    <select class="form-control select2" name="reseller_of" >
                                        <option value="0">[No One]</option>
                                        <?php foreach ($users as $user) { ?>
                                            <option value="<?=$user[0]?>"><?=$user[1]?></option>
                                        <?php } ?>
                                    </select>
                                </div>


                                <div class="form-group">
                                    <label>Credits</label>
                                    <input name="credits" type="number" minlength="0" class="form-control" />
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkCanAddReseller">Can Add Other Reseller?</label>
                                    <input id="chkCanAddReseller" type="checkbox" name="can_add_resellers"
                                           checked class="bootstrap-switch" />
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkCanOverride">Allow user to override credits </label>
                                    <input id="chkCanOverride" type="checkbox" name="can_override_package"
                                           checked class="bootstrap-switch" />
                                    <p>If this enabled user will be able to override credits value of his resellers.</p>
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkCanAccountUsername">Allow set username for accounts</label>
                                    <input id="chkCanAccountUsername" name="set_account_username"
                                           type="checkbox" class="bootstrap-switch" />
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkCanAccountPassword">Allow set password for accounts</label>
                                    <input id="chkCanAccountPassword" name="set_account_password"
                                           type="checkbox" class="bootstrap-switch" />
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkCanRestartStreams">Allow Restart Streams</label>
                                    <input id="chkCanRestartStreams" name="allow_restart_streams"
                                           type="checkbox" class="bootstrap-switch" />

                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkAllowChannelList">Allow to see channel list.</label>
                                    <input id="chkAllowChannelList" type="checkbox" name="allow_channel_list" class="bootstrap-switch" checked />
                                </div>

                                <div class="form-group form-group-bs">
                                    <label for="chkAllowVodList">Allow to see Movies/Tv Series list.</label>
                                    <input id="chkAllowVodList" type="checkbox" name="allow_vod_list" class="bootstrap-switch"  checked />
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Override Package</h5>
                            </div>
                            <div class="card-body">
                                <div class="row pb-2">
                                    <div class="col-md-5">
                                        <h5>Official Credits</h5>
                                    </div>
                                    <div class="col-md-5">
                                        <h5>Trail Credits</h5>
                                    </div>
                                    <div class="col-md-2">
                                        <h5>Enable</h5>
                                    </div>
                                </div>

                                <?php
                                if($r_credits->num_rows == 0 ) {
                                    echo "<h4>You have not add any package yet. Package are needed in order to allow user to create accounts.</h4> "; }
                                else while ($row = $r_credits->fetch_assoc()) {

                                    if($EditID) {
                                        if(isset($Reseller->override_packages[$row['id']]['official_credits']))
                                            $off_val = $Reseller->override_packages[$row['id']]['official_credits'];
                                        else $off_val = '';

                                        if(isset($Reseller->override_packages[$row['id']]['trial_credits']))
                                            $trial_val = $Reseller->override_packages[$row['id']]['trial_credits'];
                                        else $trial_val = '';

                                        $enabled = $Reseller->override_packages[$row['id']]['disabled'] == 1 ? '' : 'checked';

                                    } else {
                                        $off_val = $row['official_credits'];
                                        $trial_val = $row['trial_credits'];
                                        $enabled = 'checked';
                                    }

                                    ?>
                                    <label > <?=$row['name'];?></label>
                                    <div class='row'>
                                        <div class='col-md-5'>
                                            <input id='official-c-<?=$row['id'];?>' type='text' name='official_credits[<?=$row['id'];?>]'
                                                   placeholder='Credits Value'
                                                   title='Enter the amount of credits the user needs to add an account on this package'
                                                   class='form-control credits_input' value='<?=$off_val;?>'/>
                                        </div>
                                        <div class='col-md-5'>
                                            <input id='trial-c-<?=$row['id'];?>' type='text' name='trial_credits[<?=$row['id'];?>]'
                                                   placeholder='Trial Credits Value'
                                                   title='Enter the amount of credits the user needs to create a trial on this package. If there are any the user can create a trial without credit.'
                                                   class='form-control credits_input' value='<?=$trial_val?>' />
                                        </div>
                                        <div class="col-md-2">
                                            <input type="hidden" name='enabled[<?=$row['id'];?>]' value="0" />
                                            <input type="checkbox" name='enabled[<?=$row['id'];?>]' class="bootstrap-switch" <?=$enabled;?> value="1"  />
                                        </div>
                                    </div>

                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 pb-3">
                        <button type="submit" class="btn-submit btn btn-primary btn-sm float-right ml-3">Save</button>
                        <a href="../resellers.php" class="btn btn-default btn-sm float-right">Go Back</a>
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
<script src="/dist/js/main.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    <?php if($EditID) { ?>

    $("#formReseller input[name='name']").val('<?=$Reseller->name;?>');
    $("#formReseller input[name='username']").val('<?=$Reseller->username;?>');
    $("#formReseller input[name='email']").val('<?=$Reseller->email;?>');
    $("#formReseller input[name='credits']").val('<?=$Reseller->getCredits();?>')
    $("#formReseller input[name='password']").prop('placeholder','Leave it blank to keep current password.');
    $("#formReseller select[name='status']").val(<?=$Reseller->status ?>);
    $("#formReseller select[name='reseller_of']").val('<?=$Reseller->reseller_of?>');

    $("#formReseller input[name='can_override_package']")
        .prop('checked', <?=$Reseller->can_override_package ? 'true':'false';?> );

    $('#formReseller input[name=enabled]')
        .prop('checked',<?=$Reseller->status == $Reseller::STATUS_ENABLED ? 'true':'false';?> );

    $('#formReseller input[name=can_add_resellers]')
        .prop('checked',<?=$Reseller->can_add_resellers ? 'true':'false';?> );

    $('#formReseller input[name=allow_vod_list]')
        .prop('checked',<?=$Reseller->allow_vod_list ? 'true':'false';?> );

    $('#formReseller input[name=allow_channel_list]')
        .prop('checked',<?=$Reseller->allow_channel_list ? 'true':'false';?> );

    $('#formReseller input[name=set_account_username]')
        .prop('checked',<?=$Reseller->isAllowAccountUsername() ? 'true':'false';?> );

    $('#formReseller input[name=set_account_password]')
        .prop('checked',<?=$Reseller->isAllowAccountPassword() ? 'true':'false';?> );

    $("#formReseller input[name='allow_restart_streams']")
        .prop("checked", <?=$Reseller->isAllowRestartStreams() ? 'true':'false';?> );


    <?php }  ?>

    function randomPassword() {
        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        pass = "";
        for(x=0;x<8;x++)
        {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
        }
        $("#formReseller input[name='password']").val(pass);
    }


    $("#formReseller").submit(function(e) {

        $(".btn-submit").prop('disabled',true);

        e.preventDefault();
        $.ajax({
            url: '../ajax/pReseller.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../resellers.php" }, 1200)
            }, error: function (xhr) {
                $(".btn-submit").prop('disabled',false);
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