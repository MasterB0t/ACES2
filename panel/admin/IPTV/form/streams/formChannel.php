<?php
$ADMIN = new \ACES2\Admin();
if(!$ADMIN->is_logged)
    Redirect("/admin/login");

$db = new \ACES2\DB();
$r_server = $db->query(" SELECT id,name FROM iptv_servers ");

$r_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");

$r_stream_profiles = $db->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0 ");

$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

$PageTitle = "Add Channel";

if($EditID = (int)$_GET['stream_id'] ) {
    $EditStream = new \ACES2\IPTV\Stream($EditID);
    $PageTitle = "Edit Channel <i>$EditStream->name</i>";

    $r_sources=$db->query("SELECT cf.*,e.title,o.name, e.title, s.number as season_number, e.number as episode_number FROM iptv_channel_files cf "
        . " LEFT JOIN iptv_video_files f ON f.id = cf.file_id "
        . " LEFT JOIN iptv_series_season_episodes e ON e.id = f.episode_id "
        . " LEFT JOIN iptv_series_seasons s ON s.id = e.season_id "
        . " LEFT JOIN iptv_ondemand o ON o.id = f.movie_id OR s.series_id = o.id "
        . " WHERE cf.channel_id = '$EditStream->id' ORDER BY cf.ordering ASC ");

}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| <?=$PageTitle ?> </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- JQuery UI -->
    <link rel="stylesheet" href="/plugins/jquery-ui/jquery-ui.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>
    </style>
</head>

<datalist id="list-files"></datalist>
<body class="hold-transition sidebar-mini text-sm">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../../../header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include '../../../sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?=$PageTitle;?></h1>
                        <ol class="breadcrumb float-sm-left">
                            <li class="breadcrumb-item active"><a href="../../streams.php">Streams</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <?php if($EditID) { ?>
                            <button onclick=submitAndRestart() type="button" class="btn btn-warning float-right btnSubmit">Save & Restart</button>
                            <button onclick=submitForm() type="button" class="btn btn-success float-right btnSubmit mr-3">Save</button>
                        <?php } else { ?>
                            <button onclick=submitAndRestart() type="button" class="btn btn-warning float-right btnSubmit">Add & Start</button>
                            <button onclick=submitForm() type="button" class="btn btn-success float-right btnSubmit mr-3">Add</button>
                        <?php } ?>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form id="formStream">
                    <input type="hidden" name="token"
                           value="<?=\ACES2\Armor\Armor::createToken('iptv.channel');?>" />
                    <input style='display:none' type="file" name="upload_logo" />
                    <?php if($EditID) { ?>
                        <input type="hidden" name="action" value="update_channel" />
                        <input type="hidden" name="stream_id" value="<?=$EditID;?>" />
                    <?php } else { ?>
                        <input type="hidden" name="action" value="add_channel" />
                    <?php } ?>

                    <div class="row">

                            <?php if(!isset($_GET['sources'])) {?>
                            <!-- CARD INFO -->
                            <div class="col-lg-5">
                                <div class="card card-default">
                                    <div class="card-header">
                                        <h3>Channel Info</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 col-xl-8">
                                                <div class="form-group">
                                                    <label>Stream/Channel Name</label>
                                                    <input class="form-control" name="name"  />
                                                </div>
                                            </div>

                                            <div class="col-md-6 col-xl-4">
                                                <div class="form-group">
                                                    <label> Transcoding Options </label>
                                                    <select name="build_codecs" class="form-control select2 ">
                                                        <option value='h264:ac3'> h264 CPU </option>
                                                        <option value='h264_nvenc:ac3'> h264 GPU - Requires NVIDIA video
                                                            card with cuda support on server. </option>
                                                        <option value='symlink'> Symlink </option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Category</label>
                                                    <select class="form-control select2" name="category_id" >
                                                        <?php while( $opt=$r_cats->fetch_assoc() ) { ?>
                                                            <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Stream Server</label>
                                                    <select id="server_id" class="form-control select2" name="server_id" >
                                                        <?php while( $opt=$r_server->fetch_assoc() ) { ?>
                                                            <option value="<?=$opt['id'];?>"><?=$opt['name'];?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Web Address</label>
                                                    <div class="input-group">
                                                        <input autocomplete="OFF" name="logo" type="text" class="form-control"
                                                               placeholder="Enter a Web address logo.">
                                                        <span class="input-group-btn">
                                                            <button onclick="$('#formStream input[type=file]').trigger('click');"
                                                                    class="btn btn-info btn-flat upload-logo" type="button">CLICK TO UPLOAD</button></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6 mt-5">

                                                <div class="form-group ">
                                                    <div class="custom-control form-group">
                                                        <label> <input type="checkbox" name="enable" class="boostrap-switch " > Is Enabled</label>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- CARD SOURCES -->
                            <div class="col-lg-7">
                                <div class="card card-default">
                                    <div class="card-header">
                                        <h3>Bouquets</h3>
                                    </div>
                                    <div class="card-body">
                                        <h4 style="font-weight:bold;">Bouquets <span style='font-size:16px'>
                                                <a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",true);'>
                                                    Check</a>/<a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",false);'>
                                                    Un Check All.</a></span></h4>

                                        <input type="hidden" name='bouquets[]' value='' />
                                        <?php  while($opt = $r_bouquets->fetch_assoc() ) { ?>

                                            <label style='margin-left:10px; margin-bottom:10px'>
                                                <input name="bouquets[]" value="<?=$opt['id'];?>"  type="checkbox"> <?=$opt['name']; ?>
                                            </label>

                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>

                            <!-- CARD BOUQUETS -->
                            <div class="col-12">

                                <div class="card card-default">
                                    <div class="card-header">
                                        <h3>Sources</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row ">
                                            <div class="col-sm-4 col-lg-2 form-group">
                                                <select onchange="changeType(this.value)" class="form-control">
                                                    <option value="vod">Vods</option>
                                                    <option value="folder">Folder</option>
                                                    <option value='web'>Web</option>
                                                </select></div>
                                            <div class="col-xs-12 col-sm-8 col-lg-9 vodRow "><input autocomplete="off" list='list-files' id='search-vod' onkeyup="refreshVodList(this)" class="form-control" placeholder="Type to search vods" /></div>
                                            <div style='display:none;' class="col-xs-12 col-sm-8 col-lg-9 webRow "><input autocomplete="off"  id='search-web'  class="form-control" placeholder="Enter a link and press enter." /></div>
                                            <div style='display:none;' class="folderRow col-xs-12 col-sm-8 col-lg-10">
                                                <div class='row'>
                                                    <div class="col-xs-12 col-sm-7 col-md-9 col-xl-10"><select class="select2" autocomplete="off"  id='search-folder' onchange="getContent(this,this.value,'directories'); $(this).select2('open');"></select></div>
                                                    <div class=' col-sm-5 col-md-3 col-xl-2'><button onclick='addFolder()' type='button' style='width:100%' class='btn btn-info'>Add</button></div>
                                                </div>
                                            </div>
                                        </div>
                                        <br />
                                        <div class="url-sources clearfix">
                                            <div class="row">
                                                <div style="width:100%; overflow: auto;"><ul  class="todo-list ui-sortable">
                                                        <?php if($EditID)
                                                            while($s=$r_sources->fetch_assoc()) {

                                                                if($s['status'] == -1 ) $status = "<b class=' text-red'>[FAILED]</b>";
                                                                else if($s['status'] == 0 ) $status = "<b class=' text-yellow'>[UN BUILD]</b>";
                                                                else if($s['status'] == 1 ) $status = "<b class=' text-yellow'>[BUILDING]</b>";
                                                                else if($s['status'] == 2 ) $status = "<b class=' text-green'>[BUILD]</b>";
                                                                else if($s['status'] == 3 ) $status = "<b class=' text-yellow'>[MOVING]</b>";

                                                                $c='';
                                                                $name  = "#{$s['file_id']} ";
                                                                if($s['type'] == 1) {
                                                                    $name .= "[WEB] {$s['val']} $status";
                                                                    $s['file_id'] = $s['val'];
                                                                } else if($s['type'] == 2) { $c = 'liFile';
                                                                    $name .= "[FILE] {$s['val']} $status";
                                                                    $s['file_id'] = $s['val'];

                                                                } else if($s['title']) {
                                                                    $name .= "[SERIES] {$s['name']} S{$s['season_number']} E{$s['episode_number']} $status";
                                                                } else
                                                                    $name .= "[MOVIE] {$s['name']} $status";

                                                                ?> <li class="<?=$c;?>"> <input type="hidden" name="files_type[]" value="<?=$s['type'];?>" /> <input type="hidden" name="files[]" value="<?=$s['file_id'];?>" /><span class="handle ui-sortable-handle"><i class="fa fa-ellipsis-v"></i></span><span><span class="text"><?=$name;?></span><div class="tools"><a  onclick="$(this).parent().parent().parent().remove()"><i class="fa fa-remove"></i></a></div></li> <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <div class="col-12 pb-3">
                                <?php if($EditID) { ?>
                                    <button onclick=submitAndRestart() type="button" class="btn btn-warning float-right btnSubmit">Save & Restart</button>
                                    <button onclick=submitForm() type="button" class="btn btn-success float-right btnSubmit mr-3">Save</button>
                                <?php } else { ?>
                                    <button onclick=submitAndRestart() type="button" class="btn btn-warning float-right btnSubmit">Add & Start</button>
                                    <button onclick=submitForm() type="button" class="btn btn-success float-right btnSubmit mr-3">Add</button>
                                <?php } ?>
                            </div>

                    </div>



                </form>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include '../../../footer.php'; ?>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- JQuery UI -->
<script src="/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- bs-custom-file-input -->
<script src="/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>

<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    var listTimer;
    function refreshVodList(input_obj) {

        if (listTimer) clearTimeout(listTimer);

        listTimer = setTimeout(function(){

            var value = input_obj.value;
            var dataList = document.getElementById('list-files');

            $(dataList).empty();

            $.ajax({
                url: '../../ajax/streams/gChannelVodList.php',
                type: 'post',
                dataType: 'json',
                data: { 'search': value },
                //beforeSend: function() { input_obj.style.borderColor='red';  },
                success: function(data) {

                    data.data.forEach(function(entry) {

                        option = document.createElement('option');
                        option.value = entry.name;
                        option.innerHTML = entry.name;
                        option.setAttribute('vodid',entry.id);
                        dataList.appendChild(option);
                        //input_obj.display = "block";

                    });
                    input_obj.focus();
                }

            });


        }, 500);

        return false;
    }

    function removeSource(id) {
        $("#formModalCreateChannel").prepend("<input type=hidden name='remove_source[]' value='"+id+"' />")
    }

    function addFolder() {

        var path = $("#search-folder").val();
        var server_id = $("#server_id").val();


        if(path == '') return false;

        $.ajax({
            async:false,
            url: '../../ajax/pServerContent.php',
            type: 'post',
            dataType: 'json',
            data: { 'get_server_content' : server_id, path:path, type:'vods', get_as_array:1 },
            success: function(resp) {

                for(i=0;resp['content'][i];i++) {

                    vod = resp['content'][i];

                    $(".ui-sortable").append('<li class="liFile"> <input type="hidden" name="files_type[]" value="2" /> ' +
                        '<input type="hidden" name="files[]" value="'+vod+'" /><span class="handle ui-sortable-handle"><i class="fa fa-ellipsis-v"></i></span>' +
                        '<span><span class="text">'+vod+' <b class="text-yellow">[UN BUILED]</b> </span><div class="tools"><a  onclick="$(this).parent().parent().parent().remove()"><i class="fa fa-remove"></i></a></div></li>');

                }

            }

        });

        $("#search-folder").select2('close');


    }

    function updateServer() {

        if($(".liFile").length > 0 ) {
            if(!confirm('Changing server will cause local files to be removed.')) return false
        }

        $(".liFile").remove();
        getContent($('#search-folder'),'/home','directories');


    }

    function getContent(obj,path='/home/aces/',type='directories'){

        formats  = [".mp4", ".mp3", ".avi", ".mkv", '.flv', '.mp2', '.wav', '.ts', '.wav', 'srt' ];


        if(formats.some(path.includes.bind(path))) {
            return false;
        }

        server_id = $("#server_id").val();
        if(!server_id) server_id = 1;

        $.ajax({
            async:false,
            url: '../../ajax/pServerContent.php',
            type: 'post',
            dataType: 'json',
            data: { 'get_server_content' : server_id, path:path, type:type },
            success: function(resp) {
                $(obj).select2("destroy");
                $(obj).html(resp.content);
                $(obj).select2({ width: '100%'});
            }
        });
    }

    function changeType(v) {

        $(".folderRow").hide();
        $(".vodRow").hide();
        $(".webRow").hide();
        $("#search-web").val('');
        $("#search-vod").val('');
        $("#search-folder").val('');

        if(v == 'folder') { getContent($("#search-folder"),'/home','directories'); $(".folderRow").show(); }
        else if(v == 'web') $(".webRow").show();
        else $(".vodRow").show();

    }

    function submitForm() {

        $(".btnSubmit").prop('disabled', true);

        $.ajax({
            url: '../../ajax/streams/pFormStream.php',
            type: 'post',
            dataType: 'json',
            data: new FormData($('#formStream')[0]),
            contentType: false,
            processData:false,
            success: function (resp) {

                toastr.success("Stream Added");
                setTimeout( function() { window.location.href ="../../streams.php"}  , 2000 );


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

    }


    $("#search-vod").on('input', function(e){
        var $input = $(this),
            val = $input.val();
        var vod_id;
        list = $input.attr('list'),
            match = $('#'+list + ' option').filter(function() {
                if($(this).val() === val) {
                    vod_id = $(this).attr('vodid');
                    return val;
                }
            });

        if(match.length > 0) {
            $(".ui-sortable").append('<li class=""> <input type="hidden" name="files_type[]" value="0" /> <input type="hidden" name="files[]" value="'+vod_id+'" /><span class="handle ui-sortable-handle"><i class="fa fa-ellipsis-v"></i></span><span><span class="text">'+val+' <b class="text-yellow">[UN BUILED]</b> </span><div class="tools"><a  onclick="$(this).parent().parent().parent().remove()"><i class="fa fa-remove"></i></a></div></li>');
            $input.val('');
        }
    });

    $(document).ready(function(){



        <?php if($EditID) { ?>

            $("input[name='name']").val('<?=$EditStream->name?>');
            $("select[name='server_id']").val('<?=$EditStream->server_id?>');
            $("select[name='category_id']").val('<?=$EditStream->category_id?>');
            $("input[name='tvg_id']").val('<?=$EditStream->tvg_id?>');

            <?= $EditStream->enabled ? '$("input[name=\'enable\']").prop("checked",true);'  : ''?>

            <?php foreach($EditStream->getBouquets() as $id ) { ?>
            <?= '$("input[name=\'bouquets[]\'][value='.$id.']").prop("checked",true);'; ?>
            <?php } ?>


        <?php } else {?>
            $("input[name='enable']").prop('checked',true);
        <?php } ?>

        $(".ui-sortable").sortable();
        $(".select2").select2();
        $(".boostrap-switch").bootstrapSwitch();

    });




</script>
</body>
</html>