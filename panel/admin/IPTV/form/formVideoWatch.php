<?php

use ACES2\IPTV\Settings;

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL))
    Redirect("/admin/profile.php");

$db = new \ACES2\DB;

$interval = "60";
$do_not_download_images = Settings::get( Settings::VOD_DONT_DOWNLOAD_LOGOS );
$tmdb_lang =  Settings::get( Settings::TMDB_LANGUAGE );
$enabled = true;
$directory = "/home/";
$parallel_downloads = 1;
$xc_account = (int)$_GET['xc_account'];
$import_from_xc_cat = 0;
$xc_import_movies = false;
$xc_import_series = false;
$watch_type = 'movie';


$get_info_from = "tmdb";

$server_id = 1;

$PageTitle = "Add Folder Watch";

if($EditID = @(int)$_GET['id']) {

    $r = $db->query("SELECT * FROM iptv_folder_watch WHERE ID = '$EditID'");
    $Edit = $r->fetch_assoc();
    $interval = $Edit['interval_mins'];
    $params = json_decode($Edit['params'], true)?: unserialize($Edit['params']);
    $do_not_download_images = (bool)$params['do_not_download_images'];
    $enabled = (bool)$Edit['enabled'];
    $server_id = $Edit['server_id'];
    $xc_account = (int)$params['xc_account'];
    $watch_type = $Edit['type'];

    if(!empty($params['tmdb_lang']))
        $tmdb_lang = $params['tmdb_lang'];


    if($xc_account ) {

        $XCAccount = new \ACES2\IPTV\XCAPI\XCAccount((int)$params['xc_account']);

        $parallel_downloads = $params['parallel_download'];
        $import_from_xc_cat = $params['import_from_category'];
        $xc_categories = array_merge($XCAccount->getVodCategories(), $XCAccount->getSeriesCategories());
        $PageTitle = "Edit XC Watch";
        $xc_import_movies = (bool)$params['import_movies'];
        $xc_import_series = (bool)$params['import_series'];

        if(!empty($params['get_info_from']))
            $get_info_from = $params['get_info_from'];

    } else {

        $PageTitle = "Edit Folder Watch";
        $directory = $params['directory'];

    }


} else {

    if($xc_account) {
        $PageTitle = "Add XC Watch";
        $XCAccount = new \ACES2\IPTV\XCAPI\XCAccount($xc_account);
        $xc_categories = array_merge($XCAccount->getVodCategories(), $XCAccount->getSeriesCategories());
        $parallel_downloads = (int)$XCAccount->getMaxConnections() -  $XCAccount->getActiveConnections();
    }

}

$r_category = $db->query("SELECT id,name FROM iptv_stream_categories ORDER BY name ");
$r_servers = $db->query("SELECT id,name FROM iptv_servers ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets  ");

include DOC_ROOT . "/includes/languages.php";

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
                        <ol class="breadcrumb float-sm-left">
                            <li class="breadcrumb-item active"><a href="../watch.php">Watch</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formFolderWatch">
                <input type="hidden" name="action" value="folder_watch" />
                <?php if($xc_account) { ?>
                    <input type="hidden" name="xc_account" value="<?=$xc_account;?>" />
                <?php } if($EditID) { ?>
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php }  ?>
                <div class="row">
                    <div class="col-12 col-md-6 ">
                        <div class="card">

                            <div class="card-body">

                                <div class="form-group">
                                    <label>Interval Minutes</label>
                                    <input class="form-control" type="number" name="interval"
                                           required placeholder="Run Every X Minutes" value="<?=$interval;?>" />
                                </div>

                                <?php if($xc_account) { ?>



                                    <div class="form-group" >
                                        <label> Transcoding Option </label>
                                        <select name="transcoding" class="form-control select2">
                                            <option value="copy"> COPY Do not transcode file. </option>
                                            <option value="h264:aac"> H264 AAC </option>
                                            <option selected title='Create a symbolic link instead of copy or processing local files or redirect if is a web source.'
                                                    value="redirect"> Symlink / Redirect </option>
                                            <option title=''
                                                    value="stream"> Stream </option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-4 form-group">
                                            <label>Parallel Downloads</label>
                                            <input type="number" name="parallel_download" value="<?=$parallel_downloads?>"
                                                   class="form-control"/>
                                        </div>

                                        <div class="col-8 form-group">
                                            <label>Import XC From Category</label>
                                            <input type="hidden" name="import_from_category_name" value="" />
                                            <select onchange="importFromCat();" name="import_from_category" class="form-control select2">
                                                <option value="0">All Categories</option>
                                                <?php foreach($xc_categories as $i => $c ) { ?>
                                                    <option value="<?=$c['category_id'];?>"><?=$c['category_name'];?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <labe>Get Vod Info From</labe>
                                        <select class="form-control" name="get_info_from"  >
                                            <option value="tmdb">The Movie Database</option>
                                            <option value="provider">Provider</option>
                                        </select>
                                    </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"  name="import_movies" >
                                                Import Movies</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkbox">
                                            <label><input type="checkbox"  name="import_series" >
                                                Import Series</label></div>
                                    </div>

                                </div>

                                <?php } else { ?>

                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="type" class="form-control select2">
                                            <option value="movie">Movies</option>
                                            <option value="series">Series</option>
                                        </select>
                                    </div>

                                <?php } ?>

                                <div class="form-group">
                                    <label> Select Categories to be added to those movies/series </label>
                                    <select onchange="addCategory()" name="sub_category" class="form-control select2">
                                        <option value="">Categories</option>
                                        <option value="-1"><?=$xc_account ? "Add XC Category" : "Add TMDB Categories" ?></option>
                                        <?php while($c = $r_category->fetch_assoc()) {
                                            echo "<option value=\"{$c['id']}\">{$c['name']}</option>";
                                        } ?>
                                    </select>
                                    <div class="list-categories">
                                        <?php if($EditID) {
                                            foreach($params['categories'] as $c) {
                                                if($c ==  -1 )
                                                    $c_name = $xc_account ? "Add XC Category" : "Add TMDB Categories";
                                                else {
                                                    try {
                                                        $cat = new \ACES2\IPTV\Category($c);
                                                        $c_name = $cat->name;

                                                        ?>
                                                            <span class='badge badge-success'>
                                                            <input type='hidden' class='otherCats' name='categories[]' value='<?=$c?>' />
                                                            <?=$c_name;?><a href='#!' onclick='removeCat(this)'>X</a></span>
                                                        <?php

                                                    } catch(\Exception $e) { $ignore = 1;}
                                                }

                                            }
                                        } ?>
                                    </div>
                                </div>

                                <div class="form-group "  >
                                    <label>Select Server</label>
                                    <select id="server_id" onchange="getContent($('#browser'),'/home','directories');"
                                            name="server_id" class="form-control select2">
                                        <?php
                                        while($s = $r_servers->fetch_assoc()) {
                                            echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                                        }
                                        ?>

                                    </select>
                                </div>

                                <?php if(!$xc_account) { ?>

                                    <div class="form-group">
                                        <label> Select Folder </label>
                                        <select id="browser" onchange="getContent(this,this.value,'directories'); $(this).select2('open');"
                                                class="select2 form-control" name="directory" >
                                        </select>
                                    </div>
                                <?php } ?>

                                <div class="form-group">
                                    <labe>TMDB Lang</labe>
                                    <select class="form-control select2" name="tmdb_lang"  >
                                        <?php foreach($__LANGUAGES as $i => $l ) { echo "<option value='{$i}'>{$l}</option>"; } ?>
                                    </select>
                                </div>

                                <div class="form-group-bs pb-3">
                                    <label> Enabled</label>
                                    <input type="checkbox" class="bootstrap-switch"  name="enabled" />
                                </div>

                                <div class="form-group-bs">
                                    <label>Do not download images.</label>
                                    <input type="checkbox" class="bootstrap-switch"  name="do_not_download_images"/>
                                </div>

                            </div>

                            <div class="card-footer">
                                    <button type="submit" class="btn btn-primary btn-sm float-right">
                                        <?=$EditID ? 'Save' : 'Add';?>
                                    </button>
                            </div>

                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Bouquets</h4>
                            </div>
                            <div class="card-body">
                                <h4 style="font-weight:bold;"><span style='font-size:16px'>
                                        <a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/<a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

                                <input  name="bouquets[]" value="" type="hidden">

                                <?php

                                while ($row = $r_bouquets->fetch_assoc()) { ?>

                                    <label style='margin-left:10px; margin-bottom:10px'>
                                        <input name="bouquets[]"
                                               value="<?php echo $row['id'];?>"
                                               type="checkbox"> <?php echo $row['name']; ?>
                                    </label>

                                <?php } ?>

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

    function importFromCat() {

        name = $( "select[name='import_from_category'] option:selected").text();
        $("input[name='import_from_category_name']").val(name);

    }

    function getContent(obj,path='/home/',type='directories'){

        formats  = [".mp4", ".mp3", ".avi", ".mkv", '.flv', '.mp2', '.wav', '.ts', '.wav', 'srt' ];

        if(formats.some(path.includes.bind(path))) {
            return false;
        }

        server_id = $("#server_id").val();
        if(!server_id) server_id = 1;

        $.ajax({
            async:false,
            url: '../ajax/pBrowseContent.php',
            type: 'post',
            dataType: 'json',
            data: { 'get_server_content' : server_id, path:path, type:type },
            success: function(resp) {
                $(obj).html(resp.content);
                $(obj).select2();
            }
        });
    }

    function addCategory() {

        txt = $("select[name='sub_category']>option:selected").text();
        val = $("select[name='sub_category']>option:selected").val();


        if( $("input[value='"+val+"'].otherCats").length == 0 )
            $(".list-categories").append("<span class='badge badge-success'>" +
                "<input type='hidden' class='otherCats' name='categories[]' value='"+val+"'/>"
                +txt+"<a href='#!' onclick='removeCat(this)'>X</a></span>");

        $("select[name='sub_category']").val(0);
        $("select[name='sub_category']").select2();


    }

    function removeCat(obj) {
        $(obj).parent('span').fadeOut('slow', function() { $(this).remove(); })
    }

    function toggleAllBouquets(s) {

        if(s == true ) {
            $("input[name=bouquets\\[\\]]").prop("checked",true);
        } else
            $("input[name=bouquets\\[\\]]").prop("checked",false);

    }

    $(document).ready(function(){

        $("#formFolderWatch select[name='server_id']").val('<?=$server_id;?>');
        $("select[name='import_from_category']").val('<?=$import_from_xc_cat;?>');
        $("select[name='tmdb_lang']").val('<?=$tmdb_lang?>');
        $("select[name='get_info_from']").val('<?=$get_info_from;?>');
        $("select[name='type']").val('<?=$watch_type?>');
        $("select[name='tmdb_lang']").val('<?=$tmdb_lang?>');

        importFromCat();

        $("input[name='import_movies']").prop('checked', <?= (bool)$xc_import_movies ? 'true' : 'false';?> );
        $("input[name='import_series']").prop('checked', <?= (bool)$xc_import_series ? 'true' : 'false';?> );

        <?php if(!$xc_account) {?>
            getContent($("#browser"),'<?=$directory?>','directories');
        <?php } ?>

        $("#formFolderWatch input[name='enabled']").prop("checked", <?=$enabled ? 'true' : 'false'; ?>);

        $("#formFolderWatch input[name='do_not_download_images']")
            .prop("checked", <?=$do_not_download_images ? 'true' : 'false'; ?>);

        <?php if($EditID) {

            foreach($params['bouquets'] as $b )
                echo "$('#formFolderWatch input[name=\"bouquets[]\"][value=$b]').prop('checked', true);";
            ?>

        <?php }  ?>

        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

    });

    $("#formFolderWatch").submit(function(e) {

        e.preventDefault();
        $.ajax({
            url: '../ajax/pVideoWatch.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../watch.php" }, 1200)
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