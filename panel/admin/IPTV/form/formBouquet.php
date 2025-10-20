<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_BOUQUETS))
    Redirect("/admin/profile.php");

$db = new \ACES2\DB();
$r_streams = $db->query("SELECT id,name,category_id FROM iptv_channels  ");
$r_vods = $db->query("SELECT id,name,category_id FROM iptv_ondemand  ");

$Disabled = (bool) $r_streams->num_rows + $r_vods->num_rows > 10000;

if(!$Disabled) {
    $r_cats_streams = $db->query("SELECT c.id,c.name FROM iptv_stream_categories c "
        . "LEFT JOIN iptv_channels s ON s.category_id = c.id  "
        . "GROUP BY s.category_id ");
    $StreamsCats = $r_cats_streams->fetch_all(MYSQLI_ASSOC);

    $StreamNotInBouquet = [];
    while($row=$r_streams->fetch_assoc())
        $StreamNotInBouquet[$row['id']] = $row;

}


if(!$Disabled) {
    $r_cats_vods = $db->query("SELECT c.id,c.name  FROM iptv_stream_categories c "
        . "LEFT JOIN iptv_ondemand s ON s.category_id = c.id  "
        . "GROUP BY s.category_id  ");

    $VodsCats = $r_cats_vods->fetch_all(MYSQLI_ASSOC);

    $VodNotInBouquet = [];
    while($row=$r_vods->fetch_assoc())
        $VodNotInBouquet[$row['id']] = $row;
}


$PageTitle = "Add Bouquet";
$StreamsInBouquet = [];
if($EditID = @(int)$_GET['id']) {
    $Bouquet = new \ACES2\IPTV\Bouquet($EditID);
    $PageTitle = "Edit Bouquet '$Bouquet->name'";
    $r_streams_in_bouquet = $db->query("SELECT c.id,c.name,c.category_id FROM iptv_channels_in_bouquet b 
    RIGHT JOIN iptv_channels c ON c.id = b.chan_id 
    WHERE b.bouquet_id= $EditID");

    while($row=$r_streams_in_bouquet->fetch_assoc()) {
        $StreamsInBouquet[$row['id']] = $row;
        unset($StreamNotInBouquet[$row['id']]);
    }

    $VodsInBouquet = [];
    $r_vods_in_bouquet = $db->query("SELECT o.id, o.name, o.category_id FROM iptv_ondemand_in_bouquet v 
    RIGHT JOIN iptv_ondemand o ON o.id = v.video_id WHERE v.bouquet_id = '$EditID' ");
    while($row=$r_vods_in_bouquet->fetch_assoc()) {
        $VodsInBouquet[$row['id']] = $row;
        unset($VodNotInBouquet[$row['id']]);
    }

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
    <style>
        .sortable-box { width:100%; height:600px; overflow: auto; border:1px solid; border-radius: 5px;}
    </style>
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
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formBouquet">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_bouquet" />
                    <input type="hidden" name="bouquet_id" value="<?=$EditID;?>" />
                    <input type="hidden" name="ignore_in_bouquet" value="<?=$Disabled ? 1 : 0 ?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_bouquet" />
                <?php } ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">

                                <div class="form-group">
                                    <label>Bouquet Name</label>
                                    <input required name="name" class="form-control" value="<?=$Bouquet->name?>" />
                                </div>

                                <div <?=$Disabled ? "style='display:none;'" : '';?> class="row pt-3">

                                    <div class="col-md-6 ">
                                        <h5>Streams Not In Bouquet</h5>
                                        <div class="row p-3 ">
                                            <a href="#!" onclick="enableStream('all')"
                                               class="btn btn-default btn-sm btn-flat w-100">Enable All</a>
                                            <?php foreach($StreamsCats as $row) { ?>
                                                <a href="#!" onclick="enableStream('<?=$row['id']?>')"
                                                   class="btn btn-default btn-sm btn-flat m-1"><?=$row['name']?></a>
                                            <?php } ?>

                                        </div>
                                        <div class="sortable-box">
                                            <ul id="SortableDisabledChannels" class="todo-list ui-sortable connectedSortable">

                                                <?php foreach($StreamNotInBouquet as $row ) { ?>

                                                <li class="liStream liStream<?=$row['id']?> liChannelCategory<?=$row['category_id']?> ui-sortable-handle">
                                                    <input type="hidden" name="streams[]" value="<?=$row['id']?>" />
                                                    <span class="handle"><i class="fa fa-ellipsis-v"></i></span>
                                                    <span><input type="checkbox" class="liCheckboxChannel" value="<?=$row['id']?>">
                                                        <span class="text"><?=$row['name']?> </span> </span>
                                                </li>

                                                <?php } ?>

                                            </ul>
                                        </div>

                                    </div>

                                    <div class="col-md-6 ">
                                        <h5>Stream In Bouquet</h5>
                                        <div class="row p-3">
                                            <a href="#!" onclick="disableStream('all')"
                                               class="btn btn-default btn-sm btn-flat w-100">Enable All</a>
                                            <?php foreach($StreamsCats as $row) { ?>
                                                <a href="#!" onclick="disableStream('<?=$row['id']?>')"
                                                   class="btn btn-default btn-sm btn-flat m-1"><?=$row['name']?></a>
                                            <?php } ?>

                                        </div>
                                        <div class="sortable-box">
                                            <ul id="SortableEnabledChannels" class="todo-list ui-sortable connectedSortable">
                                                <?php foreach($StreamsInBouquet as $row ) { ?>

                                                    <li class="liStream liStream<?=$row['id']?> liChannelCategory<?=$row['category_id']?> ui-sortable-handle">
                                                        <input type="hidden" name="streams[]" value="<?=$row['id']?>" />
                                                        <span class="handle"><i class="fa fa-ellipsis-v"></i></span>
                                                        <span><input type="checkbox" class="liCheckboxChannel" value="<?=$row['id']?>">
                                                        <span class="text"><?=$row['name']?></span> </span>
                                                    </li>

                                                <?php } ?>
                                            </ul>
                                        </div>

                                    </div>


                                    <div class="col-md-6 pt-md-3">
                                        <h5>Vods Not In Bouquets</h5>
                                        <div class="row p-3">
                                            <a href="#!" onclick="enableVod('all')"
                                               class="btn btn-default btn-sm btn-flat w-100">Enable All</a>
                                            <?php
                                                if(!$Disabled)
                                                foreach($VodsCats as $row) { ?>
                                                        <a href="#!" onclick="enableVod('<?=$row['id']?>')"
                                                        class="btn btn-default btn-sm btn-flat m-1"><?=$row['name']?></a>
                                            <?php } ?>

                                        </div>
                                        <div class="sortable-box">
                                            <ul id="SortableDisabledMovies" class="todo-list ui-sortable connectedSortableMovies">
                                                <?php
                                                    if(!$Disabled)
                                                        foreach($VodNotInBouquet as $row ) { ?>

                                                        <li class="liVod liVod<?=$row['id']?> liVodCategory<?=$row['category_id']?> ui-sortable-handle">
                                                            <input type="hidden" name="vods[]" value="<?=$row['id']?>" />
                                                            <span class="handle"><i class="fa fa-ellipsis-v"></i></span>
                                                            <span><input type="checkbox" class="liCheckboxVod" value="<?=$row['id']?>">
                                                            <span class="text"><?=$row['name']?> </span> </span>
                                                        </li>

                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-6 pt-md-3">
                                        <h5 class="">Vods in Bouquets</h5>
                                        <div class="row p-3">
                                            <a href="#!" onclick="disableVod('all')"
                                               class="btn btn-default btn-sm btn-flat w-100">Disable All</a>
                                            <?php
                                                if(!$Disabled)
                                                    foreach($VodsCats as $row) { ?>
                                                    <a href="#!" onclick="disableVod('<?=$row['id']?>')"
                                                        class="btn btn-default btn-sm btn-flat m-1"><?=$row['name']?></a>
                                            <?php } ?>

                                        </div>
                                        <div class="sortable-box">
                                            <ul id="SortableEnabledMovies" class="todo-list ui-sortable connectedSortableMovies">
                                                <?php
                                                if(!$Disabled)
                                                    foreach($VodsInBouquet as $row ) { ?>

                                                        <li class="liVod liVod<?=$row['id']?> liVodCategory<?=$row['category_id']?> ui-sortable-handle">
                                                            <input type="hidden" name="vods[]" value="<?=$row['id']?>" />
                                                            <span class="handle"><i class="fa fa-ellipsis-v"></i></span>
                                                            <span><input type="checkbox" class="liCheckboxVod" value="<?=$row['id']?>">
                                                            <span class="text"><?=$row['name']?> </span> </span>
                                                        </li>

                                                    <?php } ?>
                                            </ul>
                                        </div>
                                    </div>


                                </div>

                            </div>

                            <div class="card-footer">
                                    <button type="submit" class="btn btn-primary btn-sm float-right">
                                        <?=$EditID ? 'Save' : 'Add';?>
                                    </button>
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
<!-- JQuery UI -->
<script src="/plugins/jquery-ui/jquery-ui.min.js"></script>
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

    function enableStream(cat_id) {
        if(cat_id == 'all')
            $("#SortableDisabledChannels > li.liStream").slice().appendTo('#SortableEnabledChannels');
        else
            $("#SortableDisabledChannels > li.liChannelCategory"+cat_id).slice().appendTo('#SortableEnabledChannels');
    }
    function disableStream(cat_id) {
        if(cat_id == 'all')
            $("#SortableEnabledChannels > li.liStream").slice().appendTo('#SortableDisabledChannels');
        else
            $("#SortableEnabledChannels > li.liChannelCategory"+cat_id).slice().appendTo('#SortableDisabledChannels');
    }

    function disableVod(cat_id) {
        if(cat_id == 'all')
            $("#SortableEnabledMovies > li.liVod").slice().appendTo('#SortableDisabledMovies');
        else
            $("#SortableEnabledMovies > li.liVodCategory"+cat_id).slice().appendTo('#SortableDisabledMovies');
    }

    function enableVod(cat_id)  {
        if(cat_id == 'all')
            $("#SortableDisabledMovies > li.liVod").slice().appendTo('#SortableEnabledMovies');
        else
            $("#SortableDisabledMovies > li.liVodCategory"+cat_id).slice().appendTo('#SortableEnabledMovies');
    }

    var clickTimeout = null;
    var clicks = 0;
    $(".ui-sortable li ").click(function() {
        setTimeout(function() { clicks = 0; }, 300);
        clicks++;
        if(clicks>1) {
            switch ($(this).parent().attr('id')) {
                case 'SortableEnabledChannels':
                    $(this).slice().appendTo('#SortableDisabledChannels'); break;
                case 'SortableDisabledChannels':
                    $(this).slice().appendTo('#SortableEnabledChannels'); break;
                case 'SortableEnabledMovies':
                    $(this).slice().appendTo('#SortableDisabledMovies'); break;
                case 'SortableDisabledMovies':
                    $(this).slice().appendTo('#SortableEnabledMovies'); break;

            }
            clicks = 0;
        }

    });


    $(document).ready(function(){

        //$("#SortableDisabledChannels, #SortableEnabledChannels").sortable({connectWith: ".connectedSortable"});
        //$("#SortableEnabledMovies, #SortableDisabledMovies").sortable({connectWith: ".connectedSortableMovies"});

    });

    $("#formBouquet").submit(function(e) {

        $("#SortableEnabledChannels").find("input").prop('disabled',false);
        $("#SortableDisabledChannels").find("input").prop('disabled',true);
        $("#SortableEnabledMovies").find("input").prop('disabled',false);
        $("#SortableDisabledMovies").find("input").prop('disabled',true);

        $("button[type='submit']").prop('disabled',true);

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
<?php