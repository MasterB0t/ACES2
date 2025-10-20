<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

if(!$ADMIN->hasPermission(""))
    Redirect("/admin/profile.php");

include DOC_ROOT . "/includes/languages.php";

use \ACES2\IPTV\Settings;

$db = new \ACES2\DB();
$r_backup_locations = $db->query("SELECT * FROM backup_locations");


$TEMPLATES = [];
$templates_dir = DOC_ROOT . 'c/template/';
foreach( glob( "$templates_dir/*" , GLOB_ONLYDIR) as $dir ) {

    $dir = str_replace($templates_dir.'/' , '' , $dir);
    $name = str_replace("_", ' ', $dir);
    $name = strtoupper($name);
    $TEMPLATES[$dir] = $name;

}



?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Settings </title>
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
    <style>
        .maintenance { list-style: none; padding-top:30px; padding-left:10px; }
        .maintenance li { border-bottom: 1px solid #ccc; padding-bottom:15px; padding-top:15px; }
        .maintenance-info { width:50%; float:left;  }
        .maintenance-info h5 { font-size: 22px;  }
        .maintenance-info span { font-size: 16px;  }
        .maintenance-action { float:right; width:auto; padding-top:25px; padding-right:20px;}
        .maintenance-action .dropdown-menu li { padding-bottom: 10px; }
        #custom-tabs-three-tab .nav-link { font-size: 18px; }
    </style>
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
                        <h1>Settings</h1>
                        <ol class="breadcrumb float-sm-left">
                            <li class="breadcrumb-item active">Settings</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">

                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <div style="min-height:705px;" class="row">
                                <div class="col-12 col-md-2">

                                    <div class="nav flex-column nav-tabs h-100" id="custom-tabs-three-tab"
                                         role="tablist" aria-orientation="vertical">


                                        <a class="nav-link active"
                                           id="tabStreamEpg" data-toggle="pill"
                                           href="#custom-tabs-1-content" role="tab"
                                           aria-controls="custom-tabs-1-home"
                                           aria-selected="true">Stream & EPG</a>


                                        <a class="nav-link"
                                           id="tabVods" data-toggle="pill"
                                           href="#custom-tabs-2-content" role="tab"
                                           aria-controls="custom-tabs-2-profile"
                                           aria-selected="false">Movie & Series</a>


                                        <a class="nav-link"
                                           id="tabAccounts" data-toggle="pill"
                                           href="#custom-tabs-3-content" role="tab"
                                           aria-controls="custom-tabs-3"
                                           aria-selected="false">Accounts & Resellers</a>

                                        <a class="nav-link"
                                           id="tabBackups" data-toggle="pill"
                                           href="#custom-tabs-4-content" role="tab"
                                           aria-controls="custom-tabs-4"
                                           aria-selected="false">Backups
                                            <i style="display:none;" id="backupIcon" class="fas fa-gear float-right mt-1 fa-spin"></i>
                                        </a>

                                        <a class="nav-link"
                                           id="tabBackupLocations" data-toggle="pill"
                                           href="#custom-tabs-5-content" role="tab"
                                           aria-controls="custom-tabs-5"
                                           aria-selected="false">Backups Locations</a>


                                        <a class="nav-link"
                                           id="tabMaintenance" data-toggle="pill"
                                           href="#custom-tabs-6-content" role="tab"
                                           aria-controls="custom-tabs-6"
                                           aria-selected="false">Maintenance</a>

                                    </div>

                                </div>

                                <div class="col-12 col-md-10 p-sm-4 pb-5 ">

                                    <form id="formSettings"></form>

                                        <div class="tab-content mt-3 pr-3" id="custom-tabs-three-tabContent">

                                            <!--STREAMS & EPG-->
                                            <div class="tab-pane fade show active" id="custom-tabs-1-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">

                                                <h3>Streams & Epg</h3>

                                                <div class="form-group form-group-bs ">
                                                    <label for="chkStreamBoot">Start streams at boot</label>
                                                    <input type="hidden" form="formSettings" name="types[<?=Settings::STREAMS_AT_BOOT?>]" value="bool" />
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::STREAMS_AT_BOOT?>]" value="0" />
                                                    <input form="formSettings" id="chkStreamBoot" type="checkbox" class="bootstrap-switch" value="1"
                                                           name="settings[<?=Settings::STREAMS_AT_BOOT?>]" />
                                                </div>


                                                <div class="form-group">
                                                    <label>Stream Logs</label>
                                                    <input type="hidden" form="formSettings" name="types[<?=Settings::STREAMS_LOGS?>]" value="int" />
                                                    <select form="formSettings" class="form-control" name="settings[<?=Settings::STREAMS_LOGS?>]">
                                                        <option value="0">NO LOGS</option>
                                                        <option value="1"> LOGS ERRORS </option>
                                                        <option value="2"> LOGS ERRORS AND VERBOSE MESSAGES </option>
                                                        <option value="3"> LOGS ERRORS, VERBOSE MESSAGES AND DEBUG </option>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>RTMP Key</label>
                                                    <div class="input-group ">
                                                        <input form="formSettings" name="settings[iptv.rtmp_auth_key]" type="text"
                                                               value='<?=Settings::get(Settings::RTMP_AUTH_KEY)?>' class="form-control">
                                                        <span class="input-group-append">
                                                            <button onclick="generate_rtmp_key()" type="button"
                                                                    class="btn btn-info btn-flat">Generate</button>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label>EPG Length</label>
                                                    <input form="formSettings" type="number" class="form-control"
                                                           value='<?=Settings::get(Settings::EPG_LENGTH)?>' name="settings[iptv.epglength]" />
                                                </div>

<!--                                                <div class="form-group form-group-bs ">-->
<!--                                                    <label for="chkEpgAutoBuild">Auto Build Every Day</label>-->
<!--                                                    <input form="formSettings" type="hidden" name="settings[--><?php //=Settings::EPG_AUTO_BUILD?><!--]" value="0" />-->
<!--                                                    <input form="formSettings" id="chkEpgAutoBuild" type="checkbox" class="bootstrap-switch"-->
<!--                                                           value="1" name="settings[--><?php //=Settings::EPG_AUTO_BUILD?><!--]"/>-->
<!--                                                </div>-->

                                                <div class="form-group form-group-bs ">
                                                    <label for="chkEpgBuildXml">Build XML Format</label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::EPG_BUILD_XML?>]" value="0" />
                                                    <input form="formSettings" id="chkEpgBuildXml" type="checkbox" class="bootstrap-switch"
                                                           value="1" name="settings[<?=Settings::EPG_BUILD_XML?>]" />
                                                </div>


                                                <div class="form-group form-group-bs ">
                                                    <label for="chkEpgBuildGz">Auto Build GZ Format </label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::EPG_BUILD_GZIP?>]" value="0" />
                                                    <input form="formSettings" id="chkEpgBuildGz" type="checkbox" class="bootstrap-switch"
                                                           value="1" name="settings[<?=Settings::EPG_BUILD_GZIP?>]" />
                                                </div>

                                                <div class="form-group form-group-bs ">
                                                    <label for="chkEpgBuildZip">Auto Build Zip Format </label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::EPG_BUILD_ZIP?>]" value="0" />
                                                    <input form="formSettings" id="chkEpgBuildZip" type="checkbox" class="bootstrap-switch"
                                                           value="1" name="settings[<?=Settings::EPG_BUILD_ZIP?>]"" />
                                                </div>

                                                <div class="form-group form-group-bs ">
                                                    <label for="chkEpgBuildVods">Build Epg For Movies and Series</label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::EPG_BUILD_FOR_VODS?>]" value="0" />
                                                    <input form="formSettings" id="chkEpgBuildVods" type="checkbox" class="bootstrap-switch"
                                                           value="1" name="settings[<?=Settings::EPG_BUILD_FOR_VODS?>]" />
                                                </div>

                                                <div class="form-group form-group-bs ">
                                                    <label for="chkEpgBuild274">Build Epg For Created Channels.</label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::EPG_BUILD_247_CHANNEL?>]" value="0" />
                                                    <input form="formSettings" id="chkEpgBuild274" type="checkbox" class="bootstrap-switch"
                                                           value="1" name="settings[<?=Settings::EPG_BUILD_247_CHANNEL?>]" />
                                                </div>

                                                <button style="position:absolute; right:35px; bottom:20px;"
                                                        form="formSettings" class="btn btn-success" type="submit">Save</button>

                                            </div>

                                            <!-- VODS SETTINGS -->
                                            <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">
                                                <h3>Movies & Series</h3>

                                                <div class="form-group">
                                                    <label>TMDB API KEY</label>
                                                    <input form="formSettings" class="form-control" name="settings[<?=Settings::TMDB_API_KEY?>]"
                                                           value="<?=Settings::get(Settings::TMDB_API_KEY)?>" />
                                                </div>

                                                <div class="form-group">
                                                    <label>Default TMDB Language</label>
                                                    <select form="formSettings" name="settings[iptv.videos.tmdb_lang]" class="form-control select2">
                                                        <?php foreach($__LANGUAGES as $v => $n) {
                                                            echo '<option value="'.$v.'">'.$n.'</option>';
                                                        } ?>
                                                    </select>
                                                </div>

                                                <div class="form-group form-group-bs">
                                                    <label for="chkNoDownloadLogos">Do not download logos</label>
                                                    <input form="formSettings" type="hidden" name="settings[iptv.do_not_download_logos]" value="0" />
                                                    <input form="formSettings" id="chkNoDownloadLogos" type="checkbox" class="bootstrap-switch"
                                                           name="settings[<?=Settings::VOD_DONT_DOWNLOAD_LOGOS?>]" />
                                                </div>

                                                <div class="form-group form-group-bs">
                                                    <label for="chkDuplicateEpisode">Allow duplicated episodes on series if source file is different.</label>
                                                    <input type="hidden" form="formSettings" name="types[<?=Settings::DUPLICATE_EPISODE?>]" value="bool" />
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::DUPLICATE_EPISODE?>]" value="0" />
                                                    <input form="formSettings" id="chkDuplicateEpisode" type="checkbox" class="bootstrap-switch"
                                                           name="settings[<?=Settings::DUPLICATE_EPISODE?>]" />
                                                </div>

                                                <button style="position:absolute; right:35px; bottom:20px;"
                                                        form="formSettings" class="btn btn-success" type="submit">Save</button>


                                            </div>

                                            <!-- ACCOUNTS -->
                                            <div class="tab-pane fade show " id="custom-tabs-3-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">
                                                <h3>Accounts & Resellers</h3>

                                                <h5 class="mt-4">Resellers</h5>
                                                <div class="form-group form-group-bs">
                                                    <label for="chkUserSetUsername">Reseller Can Set/Edit Account Username</label>
                                                    <input type="hidden" form="formSettings" name="types[<?=Settings::RESELLER_CAN_SET_ACCOUNT_USERNAME?>]" value="bool" />
                                                    <input type="hidden" form="formSettings" name="settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_USERNAME?>]" value="0" />
                                                    <input id="chkUserSetUsername" form="formSettings" type="checkbox" class="bootstrap-switch"
                                                           name="settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_USERNAME?>]" value="1"/>
                                                </div>

                                                <div class="form-group form-group-bs">
                                                    <label for="chkUserSetPass">Reseller Can Set/Edit Account Password</label>
                                                    <input form="formSettings" type="hidden"
                                                           name="types[<?=Settings::RESELLER_CAN_SET_ACCOUNT_PASSWORD?>]" value="bool" />
                                                    <input form="formSettings"
                                                           type="hidden" name="settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_PASSWORD?>]" value="0" />
                                                    <input id="chkUserSetPass" form="formSettings" type="checkbox" class="bootstrap-switch"
                                                           name="settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_PASSWORD?>]" value="1"/>
                                                </div>

                                                <div class="form-group">
                                                    <label>Credit Refund</label>
                                                    <input form="formSettings" type="number" name="settings[<?=Settings::RESELLER_REFUND_HOURS?>]" class="form-control"
                                                           value="<?=Settings::get(Settings::RESELLER_REFUND_HOURS)?>"/>
                                                    <p class="pt-1 pl-2">If reseller remove an account within this time credit will be refunded.</p>
                                                </div>


                                                <h5 class="mt-4">Accounts</h5>
                                                <div class="form-group">
                                                    <label>STB Theme</label>
                                                    <select form="formSettings" name="settings[<?=Settings::STB_THEME?>]" class="form-control select2">
                                                        <?php foreach($TEMPLATES as $k => $v) { ?>
                                                            <option value="<?=$k?>"><?=$v?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <div class="form-group form-group-bs">
                                                    <label>Force Stb Theme</label>
                                                    <input form="formSettings" type="hidden" name="settings[<?=Settings::STB_FORCE_THEME?>]" value="0" />
                                                    <input form="formSettings" type="checkbox" class="bootstrap-switch"
                                                           name="settings[<?=Settings::STB_FORCE_THEME?>]" value="1" />

                                                    <p>If this enabled client won't be able to change theme on settings</p>
                                                </div>

                                                <div class="form-group">
                                                    <label>Stb Language and locale</label>
                                                    <select form="formSettings" class="form-control select2" name="settings[<?=Settings::STB_LOCALE?>]" >
                                                        <option value="en_GB.utf8">en_GB.utf8 </option>
                                                        <option value="ru_RU.utf8">ru_RU.utf8 </option>
                                                        <option value="es_ES.utf8">es_ES.utf8</option>
                                                    </select>
                                                </div>

                                                <button style="position:absolute; right:35px; bottom:20px;"
                                                        form="formSettings" class="btn btn-success" type="submit">Save</button>

                                            </div>

                                            <!-- BACKUPS -->
                                            <div class="tab-pane fade show" id="custom-tabs-4-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">

                                                <div style="display:none;" id="backupInfo" class="row pb-3 ">
                                                    <div class="info-box mb-3 bg-info">
                                                        <span class="info-box-icon"><i class="fas fa-spin fa-gear"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text ">Creating Backup. This could take several minutes.</span>
                                                            <span class="info-box-number"></span>
                                                        </div>
                                                        <!-- /.info-box-content -->
                                                    </div>
                                                </div>

                                                <div class="row pb-3">
                                                    <h3>Backups</h3>
                                                    <button style="margin:0 0 0 auto;" type="button"
                                                            onclick="MODAL('/admin/modals/mCreateBackup.php');"
                                                            class="btn btn-success float-right">Create Backup</button>

                                                </div>

                                                <table id="table" class="table table-hover">
                                                    <thead>
                                                    <tr>
                                                        <th>Create Time</th>
                                                        <th>Name</th>
                                                        <th>File Size KB</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr></tr>
                                                    </tbody>
                                                </table>

                                            </div>

                                            <!-- BACKUP LOCATIONS -->
                                            <div class="tab-pane fade show" id="custom-tabs-5-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">

                                                <div class="row pb-3">
                                                    <h3>Backup Exports</h3>
                                                    <button style="margin:0 0 0 auto;" type="button"
                                                            onclick="addBackupLocation()"
                                                            class="btn btn-success float-right">Add Location</button>
                                                </div>

                                                <form id="formBackupLocations" > <input type="hidden" name="action" value="add_backup_locations" /></form>
                                                <table id="tableBackupLocations" class="table table-hover pt-3">
                                                    <thead>
                                                    <tr>
                                                        <th>Protocol</th>
                                                        <th>Location</th>
                                                        <th>Username</th>
                                                        <th>Password</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php while($row=$r_backup_locations->fetch_assoc()) {

                                                        $checked = $row['enabled'] ? 'checked' : '';
                                                        $location = '';
                                                        $location_src = '';
                                                        $loc_hide = '';

                                                        $err_msg = $row['error_msg'] ?
                                                            "<span class=' text-danger'>{$row['error_msg']}</span>" :
                                                            '';

                                                        if($row['protocol'] == 'dir' ) {
                                                            $c_hidden = 'd-none';
                                                            $location = $row['location'];
                                                            $protocol = 'Directory';
                                                        } else {
                                                            $loc_hide = 'd-none';
                                                            $c_hidden = '';
                                                            $location_src = $row['location'];
                                                            $protocol = 'SCP/SSH';
                                                        }
                                                        ?>
                                                    <tr>
                                                        <input form="formBackupLocations" type="hidden" name="id[]" value="<?=$row['id']?>" />
                                                        <td>
                                                            <input type="hidden" form="formBackupLocations" name="protocol[]" value="<?=$row['protocol'];?>" />
                                                            <input disabled class="form-control" value="<?=$protocol?>" />
                                                            <?=$err_msg;?>
                                                        </td>

                                                        <td>
                                                            <input form="formBackupLocations" class="form-control <?=$loc_hide?>" name="location[]" value="<?=$location;?>"
                                                            placeholder="Enter the full path of directory. IE: /home/user/backups/" />

                                                            <input form="formBackupLocations" class="form-control <?=$c_hidden;?>"  name="location_scp[]"
                                                                   value="<?=$location_src;?>"
                                                                   placeholder="server-ip:/path/of/directory IE 127.0.0.1:/home/usr/backups/"  />

                                                        </td>


                                                        <td>
                                                            <input form="formBackupLocations" class="form-control <?=$c_hidden?> " name="username[]"
                                                                   value="<?=$row['username']?>" />
                                                        </td>

                                                        <td>
                                                            <input form="formBackupLocations" type="password"
                                                                   placeholder="Leave it blank to keep current password."
                                                                   class="form-control <?=$c_hidden;?> " name="password[]" />
                                                        </td>

                                                        <td>
                                                            <a href='#' title='Remove' onclick="removeBackupLocation(this,'<?=$row['id']?>')" >
                                                                <i style="margin:5px;" class="fa fa-trash fa-lg"></i> </a>
                                                        </td>

                                                    </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>

                                                <div class="row pt-3">
                                                    <button onclick="saveBackupLocation()" class="btn btn-success btn-right" type="button">Save</button>
                                                </div>

                                            </div>

                                            <!-- MAINTENANCE -->
                                            <div class="tab-pane fade show" id="custom-tabs-6-content" role="tabpanel"
                                                 aria-labelledby="custom-tabs-info-tab">

                                                <div class="row row-maintenance-process"></div>

                                                <h3>Maintenance</h3>

                                                <ul class="maintenance">

                                                    <li>
                                                        <div class="maintenance-info ">
                                                            <h5>Clear All</h5>
                                                            <span>Clear All Content stream,categories,accounts,bouquets,movies and series.</span>
                                                        </div>
                                                        <div class="maintenance-action">
                                                            <button onClick="MODAL('modals/mMaintenanceConfirm.php?action=clear_all')"
                                                                    type="button" class="btn btn-danger float-right">Clear All</button>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>

                                                    <li>
                                                        <div class="maintenance-info ">
                                                            <h5>Clear Vod</h5>
                                                            <span>Clear movies and series.</span>
                                                        </div>
                                                        <div class="maintenance-action">
                                                            <button onClick="MODAL('modals/mMaintenanceConfirm.php?action=clear_vods')"
                                                                    type="button" class="btn btn-danger float-right">Clear Vod</button>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>


                                                    <li>
                                                        <div class="maintenance-info ">
                                                            <h5>Clear Streams</h5>
                                                            <span>Clear streams.</span>
                                                        </div>
                                                        <div class="maintenance-action">
                                                            <button onClick="MODAL('modals/mMaintenanceConfirm.php?action=clear_streams')"
                                                                    type="button" class="btn btn-danger float-right">Clear Vod</button>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>



                                                    <li>
                                                        <div class="maintenance-info ">
                                                            <h5>Clear Logs</h5>
                                                            <span>Clear system error, access, stream logs and video logs.</span>
                                                        </div>
                                                        <div class="maintenance-action">
                                                            <button onClick="MODAL('modals/mMaintenanceConfirm.php?action=clear_system_logs')"
                                                                    type="button" class="btn btn-danger float-right">Clear System Logs</button>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>


                                                    <li>
                                                        <div class="maintenance-info">
                                                            <h5>Check Vods</h5>
                                                            <span>Check for missing vods on servers.</span>
                                                        </div>
                                                        <div class="maintenance-action form-group">
                                                            <button onClick="MODAL('modals/mMaintenanceConfirm.php?action=check_vods')"
                                                                    type="button" class="btn-danger btn float-right">Check Vods</button>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>


                                                </ul>

                                            </div>

                                        </div>


                                </div>

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
<script src="/dist/js/admin.js"></script>
<script src="/dist/js/Process.js"></script>
<script>

    var table;
    var pagetitle = 'backups';
    var intervalBackupRunning = null;

    function reloadTable() {
        table.ajax.reload(null, false);
    }

    function generate_rtmp_key() {

        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        pass = "";
        for(x=0;x<32;x++)
        {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
        }
        $(" input[name='settings[iptv.rtmp_auth_key]']").val(pass);

    }

    function removeBackup(backup_name) {
        if(!confirm('Are you sure you want to remove this backup?'))
            return false;

        $.ajax({
            url: '/admin/ajax/pBackups.php',
            type: 'post',
            dataType: 'json',
            data: {action: 'remove_backup', filename: backup_name},
            success: function (resp) {
                toastr.success("Backup have been removed");
                setTimeout(reloadTable,800);
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

    function removeBackupLocation(obj,id){

        if(!id) {
            $(obj).parent().parent().remove();
            return;
        }

        if(!confirm("Are you sure you want to remove this backup location?"))
            return;

        $.ajax({
            url: '/admin/ajax/pBackups.php',
            type: 'post',
            dataType: 'json',
            data: {action: 'remove_backup_location', id : id },
            success: function (resp) {

                $(obj).parent().parent().remove();

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

    function addBackupLocation() {

        var htm = '<tr><input type=hidden form="formBackupLocations" name="id[]" value=0 />';

        htm += '<td><select onchange="backupLocationChangeProtocol(this)" form="formBackupLocations" class="form-control" name="protocol[]">' +
            '<option value="dir">Directory</option>' +
            '<option value="scp">SCP/SSH</option></select></td>';


        htm += '<td>' +
            '<input form="formBackupLocations" class="form-control" ' +
            'placeholder="Enter the full path of directory. IE: /home/user/backups/" name="location[]" />' +

            '<input form="formBackupLocations" class="form-control" ' +
            'placeholder="server-ip:/path/of/directory IE 127.0.0.1:/home/usr/backups/" name="location_scp[]" />' +

            '</td>';

        htm += '<td>' +
            '<input form="formBackupLocations" class="form-control" name="username[]" />' +
            '</td>';
        htm += '<td>' +
            '<input form="formBackupLocations" type="password" class="form-control" name="password[]" />' +
            '</td>';

        htm += "<td><a href='#' title='Remove' onclick=\"removeBackupLocation(this);\"> "+
            '<i style="margin:5px;" class="fa fa-trash fa-lg"></i> </a></td>' ;

        htm += '</tr>';

        $("#tableBackupLocations tbody").append(htm);


        $("input[name='location_scp[]']").last().hide();
        $("input[name='username[]']").last().hide();
        $("input[name='password[]']").last().hide();
        $("input[name='enabled[]'").last().bootstrapSwitch();
    }

    function backupLocationChangeProtocol(obj) {
        var inx = $(obj).parent().parent().index();

        $("input[name='location[]']").eq(inx).toggle();
        $("input[name='location_scp[]']").eq(inx).toggle();
        $("input[name='username[]']").eq(inx).toggle();
        $("input[name='password[]']").eq(inx).toggle();

    }

    function saveBackupLocation(){

        $.ajax({
            url: '/admin/ajax/pBackups.php',
            type: 'post',
            dataType: 'json',
            data: $('#formBackupLocations').serialize(),
            success: function (resp) {
                toastr.success("Saved");
                setTimeout(function() { window.location.href = 'settings.php?tabBackupLocations'; },800)
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

    function isBackupRunning() {
        $.ajax({
            url: '/admin/ajax/pBackups.php',
            type: 'post',
            dataType: 'json',
            data: {'action':'is_backup_running'},
            success: function (resp) {
                $("#backupInfo").hide();

                if(resp.data.backup_is_running == 1) {
                    $("#backupInfo").show();
                    $("#backupIcon").show();
                    intervalBackupRunning = 1;
                    setTimeout( isBackupRunning ,5000);
                } else if(intervalBackupRunning) {
                    console.log("BACKUP DONE");
                    $("#backupInfo").hide();
                    $("#backupIcon").hide();
                    intervalBackupRunning = 0;
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

    $("#formSettings").submit(function(e){

        e.preventDefault();

        $.ajax({
            url: 'ajax/pSettings.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                toastr.success("Settings have been saved.");

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


    $(document).ready(function () {

        PROCESS.get({
            url: '/admin/IPTV/ajax/pProcess.php',
            appendTo : '.row-maintenance-process',
            processToGet : 'iptv.check_videos'
        });


        $("select[name='settings[iptv.videos.tmdb_lang]']")
            .val('<?=Settings::get(Settings::TMDB_LANGUAGE);?>');

        $("input[name='settings[<?=Settings::VOD_DONT_DOWNLOAD_LOGOS?>]']")
            .prop('checked', <?=  Settings::get(Settings::VOD_DONT_DOWNLOAD_LOGOS) ? 'true' : 'false' ?> );

        $("input[name='settings[<?=Settings::DUPLICATE_EPISODE?>]']")
            .prop('checked', <?=  Settings::get(Settings::DUPLICATE_EPISODE) ? 'true' : 'false' ?> );


        $("select[name='settings[<?=Settings::STREAMS_LOGS?>]']").val('<?=Settings::get(Settings::STREAMS_LOGS);?>');

        $("input[name='settings[<?=Settings::STREAMS_AT_BOOT?>]']").prop('checked', <?=  Settings::get(Settings::STREAMS_AT_BOOT) ? 'true' : 'false' ?> );

        $("input[name='settings[<?=Settings::EPG_AUTO_BUILD?>]']").prop('checked', <?=  Settings::get(Settings::EPG_AUTO_BUILD) ? 'true' : 'false' ?> );
        $("input[name='settings[<?=Settings::EPG_BUILD_XML?>]']").prop('checked', <?=  Settings::get(Settings::EPG_BUILD_XML) ? 'true' : 'false' ?> );
        $("input[name='settings[<?=Settings::EPG_BUILD_GZIP?>]']").prop('checked', <?=  Settings::get(Settings::EPG_BUILD_GZIP) ? 'true' : 'false' ?> );
        $("input[name='settings[<?=Settings::EPG_BUILD_ZIP?>]']").prop('checked', <?=  Settings::get(Settings::EPG_BUILD_ZIP) ? 'true' : 'false' ?> );
        $("input[name='settings[<?=Settings::EPG_BUILD_FOR_VODS?>]']").prop('checked', <?=  Settings::get(Settings::EPG_BUILD_FOR_VODS) ? 'true' : 'false' ?> );
        $("input[name='settings[<?=Settings::EPG_BUILD_247_CHANNEL?>]']").prop('checked', <?=  Settings::get(Settings::EPG_BUILD_247_CHANNEL) ? 'true' : 'false' ?> );


        $("input[type=checkbox][name='settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_USERNAME?>]']")
            .prop('checked', <?=  Settings::get(Settings::RESELLER_CAN_SET_ACCOUNT_USERNAME) ? 'true' : 'false' ?> );

        $("input[type=checkbox][name='settings[<?=Settings::RESELLER_CAN_SET_ACCOUNT_PASSWORD?>]']")
            .prop('checked', <?=  Settings::get(Settings::RESELLER_CAN_SET_ACCOUNT_PASSWORD) ? 'true' : 'false' ?> );

        $("select[name='settings[<?=Settings::STB_THEME?>]']").val('<?=Settings::get(Settings::STB_THEME);?>');
        $("input[type=checkbox][name='settings[<?=Settings::STB_FORCE_THEME?>]']")
            .prop('checked', <?=  Settings::get(Settings::STB_FORCE_THEME) ? 'true' : 'false' ?> );
        $("select[name='settings[<?=Settings::STB_LOCALE?>]']").val('<?=Settings::get(Settings::STB_LOCALE);?>');


        table = $("#table").DataTable({
            layout: {
                top2Start: 'buttons',
                top2End: {  pageLength: {
                        menu: [ 10, 25, 50, 100, 1000, 10000 ]
                    }},
                topStart: 'info',
                topEnd: 'search',
                bottomStart: null,
                bottomEnd: 'pageLength',
                bottom2Start: 'info',
                bottom2End: 'paging'
            },

            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "info": true,
            "serverSide": true,
            "stateSave": true,
            "ordering": true,
            "searching": true,
            "ajax": {
                url: "/admin/tables/gTableBackups.php"
            },
            "stateSaveCallback": function (settings, data) {
                window.localStorage.setItem("dataTable"+pagetitle, JSON.stringify(data));
            },
            "stateLoadCallback": function (settings) {
                if (!window.localStorage.getItem("dataTable"+pagetitle)) FIRST_TIME_LOADING_PAGE = 1;
                return JSON.parse(window.localStorage.getItem("dataTable"+pagetitle));
            },
            "fnDrawCallback": function (data) {
                $(".paginate_button > a").on("focus", function () {
                    $(this).blur();
                });
            },
            "drawCallback": function (settings) {
                var api = this.api();
                var json = api.ajax.json();
                if (json.not_logged == 1) window.location.reload();;
            },
            columnDefs: [
                { className: 'select-checkbox'},
            ],
            order: [[0, 'desc']],
            select: {
                style: 'multi'
            },
            buttons: [

                {
                    text: 'Select/Unselect All',
                    action: function (e, dt, node, config) {
                        if (table.rows({selected: true}).count() < 1) {
                            table.rows().select();
                        } else {
                            table.rows().deselect();
                        }
                    }
                },

                {
                    text: 'Remove Selected',
                    action: function ( e, dt, node, config ) {

                        var post_id = [];
                        var i = 0;
                        table.rows( { selected: true } ).every( function ( rowIdx, tableLoop, rowLoop ) {
                            idx = table.row(rowIdx).index();
                            post_id[i] = table.row(rowIdx).column(0).cell(idx,0).data();
                            i++;
                        } );

                        if(i>0)
                            removeBackups(post_id);

                    }
                },


            ],


        });

        table.button(1).enable(false);

        table.on('select', function (e, dt, type, indexes) {
            table.buttons().enable(true);

        }).on('deselect', function (e, dt, type, indexes) {
            if (table.rows({selected: true}).count() < 1) {
                table.button(1).enable(false);
            }
        });

        table.on('preXhr', function () {
            let dataScrollTop = $(window).scrollTop();
            table.one('draw', function () {
                $(window).scrollTop(dataScrollTop);
            });
        });


        isBackupRunning();

        $(".select2").select2();

        $(".bootstrap-switch").bootstrapSwitch();


    });

</script>
</body>
</html>