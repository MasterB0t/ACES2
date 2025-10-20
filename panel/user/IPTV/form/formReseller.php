<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$PageName = "New Reseller";

$USER = new ACES2\IPTV\Reseller2($UserID);
if(!$USER->can_add_resellers)
    Redirect("../resellers.php");

$resellers = $USER->getResellers();

$sql = implode(",", $resellers);

$db = new \ACES2\DB();
$Result = array();
if(count($resellers)>0) {
    $r_users = $db->query("SELECT id,name,username FROM users WHERE id in ($sql) ");
    $Resellers = $r_users->fetch_all(MYSQLI_ASSOC);
}

if($EditID= (int)$_REQUEST['id']) {
    $EditReseller = new \ACES2\IPTV\Reseller2($EditID);
    $PageName = "Edit Reseller '$EditReseller->name'";

    if(!in_array($EditID, $resellers)) {
        http_response_code(404);
        exit;
    }
}

$OverridePackage = $EditID ?
    $EditReseller->override_packages :
    $USER->override_packages;

$r_packages = $db->query("SELECT id,name,official_credits,trial_credits FROM iptv_bouquet_packages ");
$PACKAGES = [];
while($row=$r_packages->fetch_assoc()){
    $OP = $OverridePackage[$row['id']];

    if( is_numeric($OP['official_credits']) )
        $row['official_credits'] = $OP['official_credits'];

    if( is_numeric($OP['trial_credits']) )
        $row['trial_credits'] = $OP['trial_credits'];

    if($OP['disabled'] != 1 )
        $PACKAGES[] = $row;

}



?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| <?=$PageName?> </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
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

<body class="hold-transition text-sm layout-top-nav" >
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include DOC_ROOT.'/user/navbar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?=$PageName?></h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item "><a href="../resellers.php">Resellers</a></li>
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formReseller" type="submit" class="btn btn-primary btn-sm float-right btnSubmit">
                            <?=$EditID ? 'Update Reseller' : 'Add Reseller'?>
                        </button>
                        <a href="../resellers.php" class="btn btn-default btn-sm float-right mr-3  ">
                            Go Back
                        </a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form id="formReseller" >
                    <div class="row">

                        <!-- Reseller Info -->
                        <div class="col-12 <?=$USER->can_override_package ? 'col-md-6' :  'col-md-12'?> ">
                            <div class="card card-default">

                                <div class="card-header">
                                    <h5>Reseller Info</h5>
                                </div>

                                <div class="card-body">

                                    <?php if($EditID) { ?>
                                        <input type="hidden" name="action" value="update_reseller" />
                                        <input type="hidden" name="reseller_id" value="<?=$EditID?>" />

                                    <?php } else { ?>
                                        <input type="hidden" name="action" value="add_reseller" />
                                    <?php } ?>

                                    <div class="form-group">
                                        <label>Name</label>
                                        <input class="form-control" name="name" value="<?=$EditReseller->name;?>" />
                                    </div>

                                    <div class="form-group">
                                        <label>Username</label>
                                        <input class="form-control" name="username" value="<?=$EditReseller->username;?>" />
                                    </div>

                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" class="form-control" name="password" value="" />
                                    </div>

                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="<?=$EditReseller->email;?>" />
                                    </div>

                                    <div class="form-group">
                                        <label>Reseller of</label>
                                        <select class="form-control select2" name="reseller_of" >
                                            <option value="">(YOU)</option>
                                            <?php foreach($Resellers as $o) { ?>
                                                <option value="<?=$o['id']?>"><?="{$o['username']} - {$o['name']}"?></option>
                                            <?php } ?>
                                        </select>
                                    </div>


                                    <?php if($USER->can_add_resellers) { ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkCanAddResellers">Can Add Resellers</label>
                                            <input id="chkCanAddResellers" type="checkbox" class="bootstrap-switch" checked
                                                    name="can_add_resellers" value="1"/>
                                        </div>
                                    <?php } ?>

                                    <?php if($USER->can_override_package) { ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkCanOverride">Can Override Package</label>
                                            <input id="chkCanOverride" type="checkbox" class="bootstrap-switch" checked
                                                   name="can_override_package" value="1"/>
                                            <p>If this enabled user will be able to override package to his resellers.</p>
                                        </div>
                                    <?php } ?>

                                    <?php if($USER->allow_channel_list) { ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkChannelList">Allow Channel List</label>
                                            <input id="chkChannelList" type="checkbox" class="bootstrap-switch" checked
                                                   name="allow_channel_list" value="1"/>
                                            <p></p>
                                        </div>
                                    <?php } ?>

                                    <?php if($USER->allow_vod_list) { ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkVodList">Allow Channel List</label>
                                            <input id="chkVodList" type="checkbox" class="bootstrap-switch" checked
                                                   name="allow_vod_list" value="1"/>
                                            <p></p>
                                        </div>
                                    <?php } ?>

                                    <?php if($USER->isAllowAccountUsername()) {  ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkCanAccountUsername">Allow set username for accounts</label>
                                            <input id="chkCanAccountUsername" name="set_account_username" checked
                                                   type="checkbox" class="bootstrap-switch" />
                                        </div>
                                    <?php } ?>

                                    <?php if($USER->isAllowAccountPassword()) {  ?>
                                        <div class="form-group form-group-bs">
                                            <label for="chkCanAccountPassword">Allow set password for accounts</label>
                                            <input id="chkCanAccountPassword" name="set_account_password" checked
                                                   type="checkbox" class="bootstrap-switch" />
                                        </div>
                                    <?php } ?>

                                </div>

                            </div>
                        </div>

                        <?php if($USER->can_override_package) { ?>
                        <!-- Credits-->
                        <div class="col-12 col-md-6">

                            <div class="card card-default">
                                <div class="card-header">
                                    <h5>Credits / Override Package</h5>
                                </div>

                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Credits</label>
                                        <input type="number" class="form-control" name="credits"
                                              <?=$EditID ? 'disabled' : ''?> value="" />
                                    </div>

                                    <div class="row pt-3 pb-2">
                                        <div class="col-12 col-md-6">
                                            <h4>Official Credit Cost</h4>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <h4>Trial Credit Cost</h4>
                                        </div>
                                    </div>

                                    <?php foreach($PACKAGES as $package) { ?>

                                        <div class="row">
                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <h5 class=""><?=$package['name']?></h5>
                                                    <input type="number" class="form-control" value="<?=$package['official_credits']?>"
                                                           name="official_credits[<?=$package['id']?>]" />
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <h5 class="pb-4"></h5>
                                                <div class="form-group">
                                                    <input type="number" class="form-control" value="<?=$package['trial_credits']?>"
                                                            name="trial_credits[<?=$package['id']?>]" />
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                </div>


                            </div>
                        </div>
                        <?php } ?>

                        <div class="col-12 pb-3">

                            <button form="formReseller" type="submit" class="btn btn-primary btn-sm float-right btnSubmit">
                                <?=$EditID ? 'Update Reseller' : 'Add Reseller'?>
                            </button>

                            <a href="../resellers.php" class="btn btn-default btn-sm float-right mr-3  ">
                                Go Back
                            </a>

                        </div>


                    </div>
                </form>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include DOC_ROOT.'/user/footer.php'; ?>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/main.js"></script>
<script src="/dist/js/user.js"></script>
<script>

    <?php if($EditID) { ?>
        $("input[name='password']").prop('placeholder',"leave it empty to keep current password");

        $("select[name=owner_of]").val('<?=$EditReseller->owner_of?>');

        $("input[name='credits']").val('<?=$EditReseller->getCredits()?>');

        $("input[name='enabled']").prop('checked', <?=$EditReseller->status == 1 ? 'true' : 'false'?>);
        $("input[name='can_add_resellers']").prop('checked', <?=$EditReseller->can_add_resellers ? 'true' : 'false'?>);
        $("input[name='can_override_package']").prop('checked', <?=$EditReseller->can_override_package ? 'true' : 'false'?>);
        $("input[name='allow_vod_list']").prop('checked', <?=$EditReseller->allow_vod_list ? 'true' : 'false'?>);
        $("input[name='allow_channel_list']").prop('checked', <?=$EditReseller->allow_channel_list ? 'true' : 'false'?>);

        $(' input[name=set_account_username]')
            .prop('checked',<?=$EditReseller->isAllowAccountUsername() ? 'true':'false';?> );

        $(' input[name=set_account_password]')
            .prop('checked',<?=$EditReseller->isAllowAccountPassword() ? 'true':'false';?> );

    <?php } ?>


    $("#formReseller").submit(function(e){ 

        e.preventDefault();
        $(".btnSubmit").prop('disabled', true);

        $.ajax({
            url: '../ajax/pReseller.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Reseller have been added!");
                setTimeout(function() { window.location = '../resellers.php'}, 800 );
            }, error: function (xhr) {
                $(".btnSubmit").prop('disabled', false);

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