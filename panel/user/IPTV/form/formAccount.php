<?php

use ACES2\IPTV\MagDevice;

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$PageName = "New Account";
$db = new \ACES2\DB;
$USER = new ACES2\IPTV\Reseller2($UserID);

$user_resellers = $USER->getResellers();

$Resellers = array();
if(count($user_resellers) > 0) {
    $sql_resellers = implode(",", $user_resellers);

    $r = $db->query("SELECT id,username FROM users WHERE id IN ($sql_resellers) ");
    $Resellers = $r->fetch_all(MYSQLI_ASSOC);
}

$Packages = [];
$OverridePackage = $USER->getOverridePackage();
//MAKE SURE PACKAGE STILL EXIST.

$r=$db->query("SELECT id,name FROM iptv_bouquet_packages ");
while($row=$r->fetch_assoc()) {
    if($OverridePackage[$row['id']]['disabled'] != 1 )
        $Packages[] = $row;
}


$force_theme = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::STB_FORCE_THEME);

if($EditID= (int)$_REQUEST['id']) {
    $Account = new \ACES2\IPTV\Account($EditID);
    $PageName = "Edit Account";
    $resellers = $USER->getResellers();
    $resellers[]= $USER->id;
    if(!in_array($Account->owner_id, $resellers)) {
        http_response_code(404);
        exit;
    }

    if($Account->mac_address && $Account->allow_mag ) {
        $MagDevice = new \ACES2\IPTV\MagDevice($EditID);

        $sql_bouquets  = implode(",",$Account->bouquets);

        $r_videos = $db->query("SELECT v.id,v.name FROM iptv_ondemand_in_bouquet b 
            RIGHT JOIN iptv_ondemand v ON v.id = b.video_id
            WHERE b.bouquet_id IN ($sql_bouquets) 
            GROUP BY v.id  ");

        $r_streams = $db->query("SELECT c.id,c.name FROM iptv_channels_in_bouquet b 
            RIGHT JOIN iptv_channels c ON c.id = b.chan_id
            WHERE b.bouquet_id IN ($sql_bouquets) 
            GROUP BY c.id  ");
    }


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
                            <li class="breadcrumb-item "><a href="../accounts.php">Accounts</a></li>
                            <li class="breadcrumb-item active"><?=$PageName;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formAccount" type="submit" class="btn btn-primary btn-sm float-right btnSubmit">
                            <?=$EditID ? 'Update Account' : 'Add Account'?>
                        </button>
                        <?php if(!$EditID) { ?>
                        <button type="button" class="btn btn-warning btn-sm float-right mr-3 btnTrial btnSubmit">
                            Add as Trail
                        </button>
                        <?php } ?>
                        <a href="../accounts.php" class="btn btn-default btn-sm float-right mr-3  ">
                           Go Back
                        </a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form id="formAccount" >
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-default">

                                <div class="card-body">

                                    <?php if($EditID) { ?>
                                        <input type="hidden" name="action" value="update_account" />
                                        <input type="hidden" name="account_id" value="<?=$EditID?>" />

                                    <?php } else { ?>
                                        <input type="hidden" name="action" value="new_account" />
                                        <input type="hidden" name="trial" value="0" />
                                    <?php } ?>

                                    <div class="form-group">
                                        <label>Account Name</label>
                                        <input class="form-control" name="name" value="<?=$Account->name;?>" />
                                    </div>

                                    <?php if($USER->isAllowAccountUsername()) { ?>
                                    <div class="form-group">
                                        <label>Username</label>
                                        <div class="form-group input-group ">
                                            <input autocomplete="OFF" class="form-control" name="username" value="<?=$Account->username;?>" />
                                            <span class="input-group-append">
                                                <button class="btn btn-info btn-flat" onclick=genRandomStr($("input[name='username']"))
                                                        type="button">Generate</button>
                                            </span>
                                        </div>
                                    </div>
                                    <?php } ?>

                                    <?php if($USER->isAllowAccountPassword()) { ?>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <div class="form-group input-group ">
                                            <input autocomplete="OFF" class="form-control" name="password" value="<?=$Account->password;?>" />
                                            <span class="input-group-append">
                                                <button class="btn btn-info btn-flat" onclick=genRandomStr($("input[name='password']"))
                                                        type="button">Generate</button>
                                            </span>
                                        </div>
                                    </div>
                                    <?php } ?>

                                    <div class="form-group">
                                        <label>Mac Address For MAG/STB</label>
                                        <input class="form-control" name="mac_address" onkeyup="formatMAC(this)"
                                               value="<?=$Account->mac_address;?>" placeholder="00:00:00:00:00"
                                               style="text-transform:uppercase"/>
                                    </div>

                                    <div class="form-group">
                                        <label>Owner</label>
                                        <select class="form-control select2" name="owner_id" >
                                            <option value="0">(YOU)</option>
                                            <?php foreach($Resellers as $o) { ?>
                                                <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <lavel>Package</lavel>
                                        <select <?=$EditID ? 'disabled' : ''?> name="package_id" class="select2 form-control" onchange="checkPackage(this.value)">
                                            <option value="">Select Package</option>
                                            <?php foreach($Packages as $p) {
                                                echo "<option value='{$p['id']}'> 
                                                        {$p['name']} Costs {$p['official_credits']} credits </option> ";
                                            }?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>PIN</label>
                                        <input type="number" class="form-control" value="<?=$Account->pin?>" name="pin">
                                    </div>

                                    <div class="form-group">
                                        <label>Comments</label>
                                        <textarea name="reseller_notes" class="form-control" rows="6"><?=$Account->reseller_notes?></textarea>
                                    </div>

                                    <div class="form-group form-group-bs">
                                            <label for="chkStatus">Account status</label>
                                            <input id="chkStatus" type="checkbox" class="bootstrap-switch" name="status" checked
                                                   data-on-text="ON"
                                                   data-off-text="OFF"
                                            />
                                        </div>

                                    <div class="form-group form-group-bs">
                                        <label for="chkAdult">Add Adult Content</label>
                                        <input id="chkAdult" type="checkbox" class="bootstrap-switch" name="allow_adult_content" />
                                    </div>

                                    <div class="form-group form-group-bs">
                                        <label for="chkLockAdult">Lock adult with pin</label>
                                        <input id="chkLockAdult" type="checkbox" class="bootstrap-switch" name="adults_with_pin" />
                                    </div>


                                </div>

                            </div>


                            <!-- MAG/STB -->
                            <?php if($EditID && $Account->mac_address && $Account->allow_mag) { ?>
                                <div class="row">
                                    <!--MAIN -->
                                    <div class="col-12 mt-3">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5>Mag/Stb Settings</h5>
                                            </div>

                                            <div class="card-body">

                                                <div class="form-group">
                                                    <label>Model</label>
                                                    <input class="form-control" readonly value="<?=$MagDevice->model?>" />
                                                </div>

                                                <div class="form-group ">
                                                    <label>Serial</label>
                                                    <input class="form-control" readonly value="<?=$MagDevice->serial?>" />
                                                </div>

                                                <div class="form-group ">
                                                    <label>Image</label>
                                                    <input class="form-control" readonly value="<?=$MagDevice->image?>" />
                                                </div>

                                                <div class="form-group">
                                                    <label>Theme</label>
                                                    <select <?=$force_theme ? 'disabled' : '' ?>
                                                            name="theme" class="form-control select2">
                                                        <?php foreach(MagDevice::getThemes() as $val => $theme) { ?>
                                                            <option value="<?=$val?>"><?=$theme;?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <?php if($force_theme) { ?>
                                                        <p class="p-2 text-danger">
                                                            Theme is forced in settings. Cannot be changed.
                                                        </p>
                                                    <?php } ?>
                                                </div>

                                                <div class="form-group">
                                                    <label>Stream Format</label>
                                                    <select name="stream_format" class="form-control select2">
                                                        <option selected value="ts">TS</option>
                                                        <option value="m3u8">HLS</option>
                                                    </select>
                                                </div>

                                                <div class="form-group-bs pt-3 pb-3">
                                                    <label for="chkPlayByOk">Play In Preview By Ok</label>
                                                    <input id="chkPlayByOk" type="checkbox" class="bootstrap-switch"
                                                           name="play_in_preview_by_ok"
                                                           data-on-text="Yes"
                                                           data-off-text="No"
                                                            <?= $MagDevice->play_in_preview_by_ok ? 'checked' : '' ?>
                                                    />
                                                </div>

                                                <!-- MAG/STB FAVORITES -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <h5>Favorites Streams</h5>
                                                            <select multiple name="favorites_streams[]" class="form-control select2">
                                                                <?php while($o=$r_streams->fetch_assoc()) {
                                                                    $s = in_array($o['id'],$MagDevice->favorite_streams) ? 'selected' : '';
                                                                    ?>
                                                                    <option
                                                                            <?=$s?>
                                                                            value="<?=$o['id']?>"><?=$o['name']?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <h5>Favorites Videos</h5>
                                                            <select multiple name="favorites_videos[]" class="form-control select2">
                                                                <?php while($o=$r_videos->fetch_assoc()) {
                                                                    $s = in_array($o['id'],$MagDevice->favorite_videos) ? 'selected' : '';
                                                                    ?>
                                                                    <option
                                                                            <?=$s?>
                                                                            value="<?=$o['id']?>"><?=$o['name']?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>

                        <div class="col-12 pb-3">

                            <button form="formAccount" type="submit" class="btn btn-sm btn-primary float-right btnSubmit">
                                <?=$EditID ? 'Update Account' : 'Add Account'?>
                            </button>

                            <?php if(!$EditID) { ?>
                            <button type="button" class="btn btn-warning btn-sm float-right mr-3 btnTrial btnSubmit">
                                Add as Trail
                            </button>
                            <?php } ?>

                            <a href="../accounts.php" class="btn btn-sm btn-default float-right mr-3  ">
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

    $("select[name='owner_id']").val(<?=$Account->owner_id;?>);

    $("input[name='allow_adult_content']").prop('checked',<?=$Account->allow_adult_content ? 'true' : 'false'?>);
    $("input[name='adults_with_pin']").prop('checked',<?=$Account->adults_with_pin ? 'true' : 'false'?>);

    <?php if($EditID= (int)$_REQUEST['id']) { ?>
        $("input[name='status']").prop('checked', <?=$Account->status == $Account::STATUS_DISABLED ? 'false' : 'true'?>);
    <?php } ?>
    function genRandomStr(obj) {
        chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        pass = "";
        for(x=0;x<8;x++)
        {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
        }
        $(obj).val(pass);
    }

    function formatMAC(e) {
        var r = /([a-f0-9]{2})([a-f0-9]{2})/i,
            str = e.value.replace(/[^a-f0-9]/ig, "");

        while (r.test(str)) {
            str = str.replace(r, '$1' + ':' + '$2');
        }

        e.value = str.slice(0, 17);
    }

    function checkPackage(pack_id) {
        if(!pack_id)
            return;
        $.ajax({
            url: '../ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: {action : 'get_package', 'package_id' : pack_id },
            success: function (resp) {
                if(resp.data.allow_mag == 0 )
                    $("input[name='mac_address']").prop('disabled', true);
                else
                    $("input[name='mac_address']").prop('disabled', false);
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

    $(".btnTrial").click(function(){
        $("input[name='trial']").val(1);
        $("#formAccount").submit();
        $("input[name='trial']").val(0);
    })

    $("#formAccount").submit(function(e){

        e.preventDefault();
        $(".btnSubmit").prop('disabled', true);

        $.ajax({
            url: '../ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Account have been added!");
                setTimeout(function() { window.location = '../accounts.php'}, 800 );
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
