<?php

use ACES2\IPTV\MagDevice;

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_ACCOUNTS))
    Redirect("/admin/profile.php");

$db = new \ACES2\DB;

$Account = new \ACES2\IPTV\Account();
$ids = null;
$PageTitle = "Add Account";

$force_theme = (bool)\ACES2\IPTV\Settings::get(\ACES2\IPTV\Settings::STB_FORCE_THEME);

if($EditID = @(int)$_GET['id']) {
    $Account = new \ACES2\IPTV\Account($EditID);
    $PageTitle = "Edit Account '$Account->name' ";
    if($Account->mac_address) {
        $MagDevice = new \ACES2\IPTV\MagDevice($EditID);

        $sql_bouquets  = count($Account->bouquets) > 0 ? implode(",",$Account->bouquets) : '0';

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



$r_users=$db->query("SELECT id,username FROM users ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");
$r_packages = $db->query("SELECT id,name FROM iptv_bouquet_packages ");

$AllowedIps = [];
$AllowedUAs = [];


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
    <!-- daterange picker -->
    <link rel="stylesheet" href="/plugins/daterangepicker/daterangepicker.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
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
                            <li class="breadcrumb-item active"><a href="../accounts.php">All Accounts</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button type="submit" form="formAccount" class="btn btn-primary btn-sm float-right ml-3">Save</button>
                        <a href="../accounts.php" class="btn btn-default btn-sm float-right">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formAccount">

                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_account" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                    <input type="hidden" name="reload_portal" value="0" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_account" />
                <?php } ?>

                <div class="row">

                    <div class="col-12 col-md-6">
                        <!--ACCOUNT INFO -->
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Account Info</h5>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label>Account Name</label>
                                    <input class="form-control" name="name" value="<?=$Account->name;?>" />
                                </div>

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

                                <div class="form-group">
                                    <label>Owner</label>
                                    <select class="form-control select2" name="owner_id" >
                                        <option value="">[Admins]</option>
                                        <?php while($o = $r_users->fetch_assoc()) { ?>
                                            <option value="<?=$o['id'];?>"><?=$o['username'];?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Mac Address</label>
                                    <input class="form-control" name="mac_address" onkeyup="formatMAC(this)"
                                           value="<?=$Account->mac_address;?>" placeholder="00:00:00:00:00"
                                           style="text-transform:uppercase"/>
                                </div>

                                <div class="form-group">
                                    <label>Expiration Date:</label>
                                    <div class="input-group date" id="reservationdate" data-target-input="nearest">
                                        <input name="expire_on" type="text" class="form-control datetimepicker-input"
                                               data-target="#reservationdate" value="<?=$Account->getExpirationDate();?>"
                                                placeholder="DD/MM/YYYY HH:MM"/>
                                        <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <!-- BOUQUETS -->
                        <div class="card card-default" >
                            <div class="card-header">
                                <h5>Bouquets & Package</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Package</label>
                                    <select onchange="updateBouquets(this.value)" class="select2 form-control" name="package_id" >
                                        <option value="0">[No Package]</option>
                                        <?php while($o = $r_packages->fetch_assoc()) { ?>
                                            <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                        <?php }?>
                                    </select>
                                </div>

                                <div class="checkbox packageGroup">

                                    <h4 style="margin-top:30px; font-weight:bold;">Bouquets
                                        <span style='font-size:16px'>
                                            <a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/<a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

                                    <?php
                                    while ($row = $r_bouquets->fetch_assoc() ) { ?>

                                        <label style='margin-left:10px; margin-bottom:10px'>
                                            <input name="bouquets[]" value="<?=$row['id'];?>" type="checkbox"> <?=$row['name']; ?>
                                        </label>

                                    <?php } ?>

                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- CARD BLOCK & RESTRICTIONS -->
                    <div class="col-12 col-md-6">

                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Block & Restrictions</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Account PIN</label>
                                    <input name="pin" class="form-control" minlength="4" maxlength="4"
                                           value="<?=$Account->pin;?>" />
                                </div>

                                <div class="form-group">
                                    <label>Account Status</label>
                                    <select class="select2 form-control" name="status" >
                                        <option value="1">Active</option>
                                        <option value="2">Disabled</option>
                                        <option value="3">Blocked</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Limit Connections</label>
                                    <input name="limit_connections" type="number" class="form-control"
                                           value="<?=$Account->limit_connections;?>" />
                                </div>

                                <div class="form-group form-group-bs pt-1 ">
                                    <label for="chkAutoLockIp">Auto Lock IP</label>
                                    <input id="chkAutoLockIp" type="checkbox" class="bootstrap-switch" name="auto_ip_lock"
                                           data-on-text="Yes"
                                           data-off-text="No"
                                    />
                                    <p class="info">If this is checked client can watch from only one ip-address.
                                        This option is recommended to prevent client share their accounts.</p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkIgnBlockRules">Ignore block rules for this account.</label>
                                    <input id="chkIgnBlockRules" type="checkbox" class="bootstrap-switch" name="ignore_block_rules"
                                           data-on-text="Yes"
                                           data-off-text="No"/>
                                    <p class="info">If this its checked the rules of blacklist will be ignored for this account.</p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkAllowAdultsContent">Show Adults Content</label>
                                    <input id="chkAllowAdultsContent" type="checkbox" class="bootstrap-switch" name="allow_adult_content"
                                           data-on-text="Yes"
                                           data-off-text="No"
                                           checked/>
                                    <p class="info"></p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkAdultsWithPin">Lock Adults With PIN</label>
                                    <input id="chkAdultsWithPin" type="checkbox" class="bootstrap-switch" name="adults_with_pin" checked
                                           data-on-text="Yes"
                                           data-off-text="No"/>
                                    <p class="info"></p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkNoM3U">Do not allow download M3U Playlist.</label>
                                    <input id="chkNoM3U" type="checkbox" class="bootstrap-switch" name="no_m3u_playlist"
                                           data-on-text="Yes"
                                           data-off-text="No" />
                                    <p class="info">Prevent user to download the m3u playlist</p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkHideVodsOnM3U">Hide VOD on playlist.</label>
                                    <input id="chkHideVodsOnM3U" type="checkbox" class="bootstrap-switch" name="hide_vods_from_playlist"
                                           data-on-text="Yes"
                                           data-off-text="No"/>
                                    <p class="info"></p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkAllowMag">Allow Ministra/MAG/STB devices.</label>
                                    <input id="chkAllowMag" type="checkbox" class="bootstrap-switch" name="allow_mag" checked
                                           data-on-text="Yes"
                                           data-off-text="No" />
                                    <p class="info">Allow this account to use on MAG/Stb Emu devices.</p>
                                </div>

                                <div class="form-group form-group-bs pt-1 pb-1">
                                    <label for="chkAllowXC">Allow Android Apps AKA Xtream API</label>
                                    <input id="chkAllowXC" type="checkbox" class="bootstrap-switch" name="allow_xc" checked
                                           data-on-text="Yes"
                                           data-off-text="No"/>
                                    <p class="info"></p>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <!-- ADMIN RESELLER NOTES-->
                <div class="row">
                    <div class="col-12 col-md-6">
                        <!-- ADMIN NOTES -->
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Admin Notes</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <textarea class="form-control" name="admin_notes"><?=$Account->admin_notes;?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <!-- RESELLER NOTES -->
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Reseller Notes</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <textarea class="form-control" name="reseller_notes"><?=$Account->admin_notes;?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ALLOWED IPS & USER-AGENT -->
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Allowed IP-ADDRESS</h5>
                            </div>
                            <div class="card-body">
                                <div  class="form-group">
                                    <label></label>
                                    <div class="form-group input-group">
                                        <input  type="text" id='inputIpLock' class="form-control"
                                                data-inputmask="'alias': 'ip'" data-mask="">
                                        <span class="input-group-append">
                                              <button class="btn btn-info btn-flat" onclick="addIpToLock()"
                                                      type="button">Add</button>
                                        </span>
                                    </div>
                                </div>

                                <div style='max-height:150px; overflow:auto'>
                                    <ul  class="todo-list ulIpLockList ui-sortable">
                                        <?php foreach($Account->getLockedIps() as $ip) { ?>
                                            <li>
                                                <input type='hidden' form='formAccount' name='allowed_ip_address[]'
                                                        value='<?=$ip;?>' />
                                                <span class='text'><?=$ip;?></span>
                                                <div class='tools'>
                                                    <i onclick="$(this).parent().parent().remove();" class='fa fa-trash'></i>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </div>


                    <div class="col-12 col-md-6">
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Allowed User Agents</h5>
                            </div>
                            <div class="card-body">
                                <div  class="form-group">
                                    <label></label>
                                    <div class="form-group input-group">
                                        <input  type="text" id='inputUALock' class="form-control" />
                                        <span class="input-group-append">
                                              <button class="btn btn-info btn-flat" onclick="addUALock()" type="button">
                                                  Add</button>
                                        </span>
                                    </div>
                                </div>

                                <div style='max-height:150px; overflow:auto'>
                                    <ul  class="todo-list ulUALockList ui-sortable">
                                        <?php
                                        $c=0; foreach($Account->getLockedUserAgents() as $ua ) { $c++; ?>
                                            <li >
                                                <input  type='hidden' form='formAccount' name='allowed_user_agent[]'
                                                        value='<?php echo $ua; ?>' />
                                                <span class='text'><?php echo $ua; ?></span>
                                                <div class='tools'><i onclick="$(this).parent().parent().remove();"
                                                                      class='fa fa-trash'></i>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>

                            </div>
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
                <!-- BUTTONS -->
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm float-right mb-3 ml-3 bntSubmit">Save</button>
                        <a href="../accounts.php" class="btn btn-sm btn-default float-right mb-3">Go Back</a>
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
<!-- date-range-picker -->
<script src="/plugins/daterangepicker/daterangepicker.js"></script>
<!-- InputMask -->
<script src="/plugins/moment/moment.min.js"></script>
<script src="/plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>

<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

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

    function toggleAllBouquets(s) {

        if( $("select[name=package_id]").val() != 0 ) return false;

        if(s == true ) {
            $("input[name=bouquets\\[\\]]").prop("checked",true);
        } else
            $("input[name=bouquets\\[\\]]").prop("checked",false);
    }

    function updateBouquets(pkg_id) {

        if( pkg_id == 0 ) {
            $("input[name='bouquets[]']").prop('disabled',false);
            $("input[name='bouquets[]']").prop('checked',false);
            return true;
        }

        $.ajax({
            async:false,
            url: '../ajax/pPackage.php',
            type: 'post',
            dataType: 'json',
            data: { action:'get_package', 'package_id': pkg_id },
            success: function(resp) {

                $.each(resp.data.bouquets, function(index, value) {

                    id = resp.data.bouquets[index]['id'];
                    $(" input[name='bouquets[]'][value='"+id+"']").prop('disabled',true);

                    if(resp.data.bouquets[index]['enabled'] == 1 )
                        $(" input[name='bouquets[]'][value='"+id+"']").prop('checked',true);
                    else
                        $(" input[name='bouquets[]'][value='"+id+"']").prop('checked',false);

                });

                <?php if(empty($EDIT['id'])) { ?>


                    $("#formAccount input[name='limit_connections']").val(resp.data.max_connections);
                    $("#formAccount input[name='limit_connections']").fadeOut( function(){
                        $("#formAccount input[name='limit_connections']").fadeIn();
                    });

                    $("#formAccount input[name='expire_on']").val(resp.data.expire_in);
                    $("#formAccount input[name='expire_on']").fadeOut(function(){
                        $("#formAccount input[name='expire_on']").fadeIn();
                    });

                <?php } ?>
            }

        });
    }

    function addIpToLock() {

        ip = $("#inputIpLock").val();
        if(ip == '' || $("#formAccount input[name='allowed_ip_address[]']").val() == ip )
            return false;

        var htm = "<li><input type='hidden' form='formAccount' name='allowed_ip_address[]' value='"+ip+"' />" +
            "<span class='text'>"+ip+"</span>" +
            "<div class='tools'><i onclick=\"$(this).parent().parent().remove();\" class='fas fa-trash'></i>" +
            "</div></li>  ";


        $(".ulIpLockList").append(htm);
        $("#inputIpLock").val('');
    }

    function addUALock() {

        ua = $("#inputUALock").val();
        if(ua == '' || $("input[name='allowed_user_agent[]']").val() == ua ) return false;


        var htm = "<li><input type='hidden' form='formAccount' name='allowed_user_agent[]' value='"+ua+"' />" +
            "<span class='text'>"+ua+"</span>" +
            "<div class='tools'>" +
            "<i onclick=\"$(this).parent().parent().remove();\" class='fas fa-trash'></i>" +
            "</div></li>  ";


        $(".ulUALockList").append(htm);
        $("#inputUALock").val('');
    }

    var macAddress = $("#formAccount input[name='mac_address']");
    macAddress.on("keyup", formatMAC);
    function formatMAC(e) {
        var r = /([a-f0-9]{2})([a-f0-9]{2})/i,
            str = e.target.value.replace(/[^a-f0-9]/ig, "");

        while (r.test(str)) {
            str = str.replace(r, '$1' + ':' + '$2');
        }

        e.target.value = str.slice(0, 17);
    };

    $(document).ready(function(){

        <?php if($EditID) { ?>

            <?php if($Account->mac_address) { ?>
                $("select[name='theme']").val("<?=$MagDevice->theme?>");

                $("select[name='stream_format']").val("<?=$MagDevice->stream_format?>");
            <?php } ?>

            $("#formAccount select[name='owner_id']").val('<?=$Account->owner_id;?>');
            $("#formAccount select[name='status']").val('<?=$Account->status;?>');
            $("#formAccount select[name='package_id']").val('<?=$Account->package_id;?>');

            $("#formAccount input[name='auto_ip_lock']").prop('checked',
                <?=$Account->auto_ip_lock ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='ignore_block_rules']").prop('checked',
                <?=$Account->ignore_block_rules ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='allow_adult_content']").prop('checked',
                <?=$Account->allow_adult_content ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='adults_with_pin']").prop('checked',
                <?=$Account->allow_adult_content ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='no_m3u_playlist']").prop('checked',
                <?=$Account->no_m3u_playlist ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='hide_vods_from_playlist']").prop('checked',
                <?=$Account->hide_vods_from_playlist ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='allow_mag']").prop('checked',
                <?=$Account->allow_mag ? 'true' : 'false' ?>
            );

            $("#formAccount input[name='allow_xc']").prop('checked',
                <?=$Account->allow_xc_apps ? 'true' : 'false' ?>
            );

            <?php
                foreach($Account->bouquets as $b ) {
                    echo "$('#formAccount input[name=\"bouquets[]\"][value={$b}]').prop('checked',true);";
                }

                if($Account->package_id)
                    echo "$('#formAccount input[name=\"bouquets[]\"]').prop('disabled',true);";

            ?>

        <?php }  ?>

        $(":input").inputmask();
        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();
        $('#reservationdate').datetimepicker({
            format: 'YYYY/MM/DD HH:mm'
        });

    });

    $(".submitAndReload").click(function() {
        $("input[name='reload_portal']").val(1);
        $('#formAccount').submit();
    });

    $("#formAccount").submit(function(e) {

        $("button[type='submit']").prop('disabled',true);

        e.preventDefault();

        $.ajax({
            url: '../ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../accounts.php" }, 1200)
            }, error: function (xhr) {

                $("input[name='reload_portal']").val(0);
                $("button[type='submit']").prop('disabled',false);

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