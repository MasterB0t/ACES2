<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_BOUQUETS))
    Redirect("/admin/profile.php");

$PageTitle = "Add Package";
if($EditID = @(int)$_GET['id']) {
    $PageTitle = "Edit Package";
    $BouquetPackage = new \ACES2\IPTV\BouquetPackage($EditID);
} else
    $BouquetPackage = new \ACES2\IPTV\BouquetPackage();

$db = new \ACES2\DB;
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets");


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
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active"><a href="../bouquets.php">Bouquets & Packages</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formPackage" type="submit" class="btn btn-primary btn-sm float-right">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../bouquets.php"
                           type="button" class="btn btn-default btn-sm float-right mr-3">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formPackage">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_package" />
                    <input type="hidden" name="package_id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_package" />
                <?php } ?>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Package Info</h5>
                            </div>

                            <div class="card-body">

                                <div class="form-group">
                                    <label>Package Name</label>
                                    <input required name="name" class="form-control"
                                           value="<?=$BouquetPackage->name;?>" />
                                </div>

                                <div class="form-group">
                                    <label>Max Connections</label>
                                    <input type="number" name="max_connections" class="form-control"
                                           value="<?=$BouquetPackage->max_connections;?>"/>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Duration Time</label>
                                            <input type="number" class="form-control" name="official_duration"
                                                   value="<?=$BouquetPackage->official_duration;?>" />
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Duration In</label>
                                            <select name="official_duration_in" class="form-control" >
                                                <option value="HOUR">Hour</option>
                                                <option value="DAY">Day</option>
                                                <option value="MONTH">Month</option>
                                                <option value="YEAR">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Trial Duration Time</label>
                                            <input type="number" class="form-control" name="trial_duration"
                                                   value="<?=$BouquetPackage->trial_duration;?>" />
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Trial Duration In</label>
                                            <select name="trial_duration_in" class="form-control" >
                                                <option value="HOUR">Hour</option>
                                                <option value="DAY">Day</option>
                                                <option value="MONTH">Month</option>
                                                <option value="YEAR">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Official Credit </label>
                                            <input type="number" name="official_credits" class="form-control"
                                                   value="<?=$BouquetPackage->official_credits;?>" />
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Trial Credit </label>
                                            <input type="number" name="trial_credits" class="form-control"
                                                   value="<?=$BouquetPackage->trial_credits;?>"/>
                                        </div>
                                    </div>

                                </div>


                            </div>



                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row">

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Account Options</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group form-group-bs">
                                            <label for="chkAutoLock">Auto lock Ip-address</label>
                                            <input id="chkAutoLock" type="checkbox" name="auto_lock_ip" class="bootstrap-switch" />
                                            <p>If the account have more than one connection this will prevent user can use the account
                                                in two different IP-Address preventing user sharing accounts.</p>
                                        </div>

                                        <div class="form-group form-group-bs">
                                            <label for="ckkHideVods">Do not show movies and series on playlist.</label>
                                            <input id="ckkHideVods" type="checkbox" name="hide_vods_from_playlist" class="bootstrap-switch" />
                                        </div>

                                        <div class="form-group form-group-bs">
                                            <label for="chkNoM3u">Prevent user to download the m3u playlist</label>
                                            <input id="chkNoM3u" type="checkbox" name="no_m3u_playlist" class="bootstrap-switch" />
                                        </div>

                                        <div class="form-group form-group-bs">
                                            <label for="chkAllowMag">Allow Stb/Mag Devices</label>
                                            <input id="chkAllowMag" type="checkbox" name="allow_mag" class="bootstrap-switch" />
                                            <p>Allow account that been generated with this package to use on MAG/Stb Emu devices.</p>
                                        </div>

                                        <div class="form-group form-group-bs">
                                            <label for="chkAllowXC">Allow XC Apps (XC API)</label>
                                            <input id="chkAllowMagXC" type="checkbox" name="allow_xc_apps" class="bootstrap-switch" />
                                            <p>Allow account that been generated with this package to use with apps over xc api.</p>
                                        </div>

                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h5>Bouquets</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="checkbox packageGroup">

                                            <h4 style="font-weight:bold;">
                                    <span style='font-size:16px'><a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/
                                        <a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

                                            <input  name="bouquets[]" value="" type="hidden">

                                            <?php

                                            while ($row = $r_bouquets->fetch_assoc()) { ?>

                                                <label style='margin-left:10px; margin-bottom:10px'>
                                                    <input name="bouquets[]" value="<?=$row['id'];?>" type="checkbox">
                                                    <?=$row['name'];?>
                                                </label>

                                            <?php } ?>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="row pb-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm float-right">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../bouquets.php"
                           type="button" class="btn btn-default btn-sm  float-right mr-3">Go Back</a>
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

    function toggleAllBouquets(s) {

        if(s == true ) {
            $("input[name=bouquets\\[\\]]").prop("checked",true);
        } else
            $("input[name=bouquets\\[\\]]").prop("checked",false);
    }

    $(document).ready(function(){

        $("select[name='official_duration_in']").val('<?=$BouquetPackage->official_duration_in?>');
        $("select[name='trial_duration_in']").val('<?=$BouquetPackage->trial_duration_in?>');

        $("input[name='auto_lock_ip']").prop("checked",
            <?=$BouquetPackage->auto_lock_ip ? 'true' : 'false';?>);

        $("input[name='allow_mag']").prop("checked",
            <?=$BouquetPackage->allow_mag ? 'true' : 'false';?>);

        $("input[name='allow_xc_apps']").prop("checked",
            <?=$BouquetPackage->allow_xc_apps ? 'true' : 'false';?>);

        $("input[name='hide_vods_from_playlist']").prop("checked",
            <?=$BouquetPackage->hide_vods_from_playlist ? 'true' : 'false';?>);

        $("input[name='no_m3u_playlist']").prop("checked",
            <?=$BouquetPackage->no_m3u_playlist ? 'true' : 'false';?>);


        <?php foreach($BouquetPackage->bouquets as $b) { ?>
            $("input[name='bouquets[]'][value='<?=$b?>']").prop("checked",true);
        <?php } ?>

        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

    });

    $("#formPackage").submit(function(e) {

        e.preventDefault();
        $.ajax({
            url: '../ajax/pBouquets.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../bouquets.php" }, 1200)
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