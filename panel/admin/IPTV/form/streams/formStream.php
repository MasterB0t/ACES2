<?php
    $ADMIN = new \ACES2\Admin();
    if(!$ADMIN->is_logged)
        Redirect("/admin/login.php");

    $db = new \ACES2\DB();
    $r_server = $db->query(" SELECT id,name FROM iptv_servers ");

    $r_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");

    $r_stream_profiles = $db->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0 ");

    $r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

    $PageTitle = "Add Stream";

    $OnlySource = '';
    if($EditID = (int)$_GET['stream_id'] ) {
        $EditStream = new \ACES2\IPTV\Stream($EditID);
        $PageTitle = "Edit Stream <i>$EditStream->name</i>";
        $Sources = $EditStream->getStreamSources();
        $StreamProfile = new \ACES2\IPTV\StreamProfile($EditStream->stream_profile_id);
        $OnlySource = isset($_GET['sources']) ? 'd-none' : '';
    }

    $r_settings = $db->query("SELECT `value` FROM settings WHERE name = 'iptv.rtmp_auth_key'");
    $RTMP_KEY = $r_settings->fetch_assoc()['value'];



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?> | <?=$PageTitle ?> </title>

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
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css ">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>
        ul.stream-info { list-style: none; padding-top:8px; width:100%;  }
        ul.stream-info li { display:inline; float:left; text-align: center; width:25%; }
        ul.stream-info li.text-danger { display:inline; float:left; text-align: center; width:100%; font-weight: bold; }

        ul.stream-info li i { display:block;  }
        ul.stream-info li span {  }
        ul.stream-info li h5 { width:100%; }
    </style>
</head>

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
                            <button onclick=submitAndRestart() type="button" class="btn btn-sm btn-warning float-right btnSubmit">Save & Restart</button>
                            <button onclick=submitForm() type="button" class="btn btn-sm btn-success float-right btnSubmit mr-3">Save</button>
                        <?php } else { ?>
                            <button onclick=submitAndRestart() type="button" class="btn btn-sm btn-warning float-right btnSubmit">Add & Start</button>
                            <button onclick=submitForm() type="button" class="btn btn-sm btn-success float-right btnSubmit mr-3">Add</button>
                        <?php } ?>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form id="formStream">
                    <input style='display:none' type="file" name="upload_logo" />
                    <input type="hidden" name="start" value="0" />
                    <?php if($EditID) { ?>
                        <input type="hidden" name="action" value="update_stream" />
                        <input type="hidden" name="stream_id" value="<?=$EditID;?>" />
                    <?php } else { ?>
                        <input type="hidden" name="action" value="add_stream" />
                    <?php } ?>
                    
                <div class="row">


                    <!-- CARD INFO -->
                    <div class="col-lg-7 <?=$OnlySource;?>">
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Channel Info</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Stream/Channel Name</label>
                                            <input class="form-control" name="name"  />
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>EPG ID</label>
                                            <input list="tvglist" onkeyup="refreshTvgList(this)" AUTOCOMPLETE="OFF" class="form-control" name="tvg_id"  />
                                            <datalist id="tvglist"></datalist>
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
                                            <select onchange='updateSourceType()' class="form-control select2" name="server_id" >
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
                                                <input autocomplete="OFF" name="logo" type="text" class="form-control" placeholder="Enter a Web address logo.">
                                                <span class="input-group-btn"><button onclick="$('#formStream input[type=file]').trigger('click');"
                                                                                      class="btn btn-info btn-flat upload-logo" type="button">CLICK TO UPLOAD</button></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-5">
                                        <div class="form-group ">
                                            <div class=" form-group form-group-bs ">
                                                <label for="chkOndemand"> Ondemand</label>
                                                <input id="chkOndemand" type="checkbox" name="ondemand" class="bootstrap-switch" >
                                            </div>
                                        </div>

                                        <div >
                                            <div class="form-group form-group-bs">
                                                <label for="chkEnable"> Is Enabled</label>
                                                <input id="chkEnable" type="checkbox" name="enable" class="bootstrap-switch " >
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <div class=" form-group form-group-bs">
                                                <label for="chkStream"> Stream It</label>
                                                <input id="chkStream" type="checkbox" name="stream" class="bootstrap-switch" >
                                            </div>
                                        </div>

                                        <div class="form-group ">
                                            <div class=" form-group form-group-bs">
                                                <label for="chkUpdateName">Sync Stream Name With Provider.</label>
                                                <input id="chkUpdateName" type="checkbox" name="auto_update" class="bootstrap-switch " >
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-5 <?=$OnlySource;?>">

                        <!-- CARD BOUQUETS -->
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Bouquets</h5>
                            </div>
                            <div class="card-body">
                                <h4 style=" font-weight:bold;">Bouquets <span style='font-size:16px'>
                                                <a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",true);'>Check</a>/<a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",false);'>Un Check All.</a></span></h4>

                                <input type="hidden" name='bouquets[]' value='' />
                                <?php  while($opt = $r_bouquets->fetch_assoc() ) { ?>

                                    <label style='margin-left:10px; margin-bottom:10px'>
                                        <input name="bouquets[]" value="<?=$opt['id'];?>"  type="checkbox"> <?=$opt['name']; ?>
                                    </label>

                                <?php } ?>
                            </div>
                        </div>


                    </div>

                    <div class="col-12 <?=$OnlySource;?>">
                        <!-- CARD STREAM PROFILE -->
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Stream Profile</h5>
                            </div>
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group ">
                                            <label for="selectStreamProfile" class="col-sm-2 col-form-label">Select Stream Profile</label>
                                            <select name="stream_profile_id" onChange="toggleStreamProfileOptions(this.value)" class="form-control select2" id="selectStreamProfile">
                                                <option value="0">[No Profile]</option>
                                                <?php while($opt=$r_stream_profiles->fetch_assoc()) { ?>
                                                    <option value="<?=$opt['id'];?>"><?=$opt['name']?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: none;" class="streamProfileForm" >

                                    <div class="row p-4">

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Video Codec</label>
                                                <select name="stream_profile[video_codec]" class="form-control select2" >
                                                    <option value="copy">copy</option>
                                                    <option value="h264">h264</option>
                                                    <option value="h264_nvenc">h264 GPU</option>
                                                    <option value="libx265">h265</option>
                                                    <option value="mpeg1video">MPEG-1</option>
                                                    <option value="mpeg2video">MPEG-2</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Video Bitrate (kbps)</label>
                                                <input name="stream_profile[video_bitrate_kbps]" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Audio Codec</label>
                                                <select name="stream_profile[audio_codec]" class="form-control select2" >
                                                    <option value="copy">copy</option>
                                                    <option value="aac">aac</option>
                                                    <option value="ac3">ac3</option>
                                                    <option value="mp2">mp2</option>
                                                    <option value="mp3">mp3</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Audio Bitrate (kbps)</label>
                                                <input name="stream_profile[audio_bitrate_kbps]" class="form-control" />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row encode-opt p-4">

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Screen Size</label>
                                                <input name="stream_profile[screen_size]" class="form-control"
                                                       placeholder="1920x1200  (Width)x(Height)"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Frame Rate</label>
                                                <input name="stream_profile[framerate]" class="form-control"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Threads</label>
                                                <input name="stream_profile[threads]" class="form-control"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Preset</label>
                                                <select name="stream_profile[preset]" class="form-control select2" >
                                                    <option value="ultrafast"> ultrafast </option>
                                                    <option value="superfast"> superfast </option>
                                                    <option value="veryfast"> veryfast </option>
                                                    <option selected value="faster"> faster </option>
                                                    <option value="fast"> fast </option>
                                                    <option value="medium"> medium </option>
                                                    <option value="slow"> slow </option>
                                                    <option value="slower"> slower </option>
                                                    <option value="veryslow"> veryslow </option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row p-4">

                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Restart stream every X hours</label>
                                                <input name="stream_profile[timeout]" class="form-control" />
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Http Proxy</label>
                                                <input name="stream_profile[proxy]" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>User Agent</label>
                                                <textarea name="stream_profile[user_agent]" class="form-control"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group" >
                                                <label>FFMPEG Extra Post Arguments (optional)</label>
                                                <textarea name="stream_profile[post_args]" class="form-control"></textarea>
                                            </div>
                                        </div>

                                    </div>

                                    <h4 style="font-weight:bold"> Extra Options </h4>
                                    <div class="row clearfix p-4">

                                        <div class="col-sm-6 pr-3">
                                            <div class="form-group form-group-bs">
                                                <label for="chkGenPts"> Generate PTS </label>
                                                <input id="chkGenPts" type="checkbox" class="bootstrap-switch" name="gen_pts" />
                                            </div>
                                            <div class="form-group form-group-bs">
                                                <label for="chkNativeFrames">  Read as Native Frames </label>
                                                <input id="chkNativeFrames" class="bootstrap-switch" type="checkbox" name="native_frames" />
                                            </div>
                                            <div class="form-group form-group-bs" >
                                                <label for="chkStreamAll"> Stream all streams from source. </label>
                                                <input id="chkStreamAll" class="bootstrap-switch" type="checkbox" name="stream_all" />
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group form-group-bs" >
                                                <label for="chkNoAudio"> Ignore source if it has no audio streams. </label>
                                                <input id="chkNoAudio" type="checkbox" name="skip_no_audio" class="bootstrap-switch" />
                                            </div>
                                            <div class="form-group form-group-bs">
                                                <label for="chkNoVideo"> Ignore source if it has no video streams. </label>
                                                <input id="chkNoVideo" type="checkbox" name="skip_no_video" class="bootstrap-switch" />
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                <div class="row">
                    <!-- CARD SOURCES -->
                    <div class="col-12">
                        <div class="card card-default">
                            <div class="card-header">
                                <h5>Sources</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="selectTypeSource" class="col-sm-2 col-form-label">Source Type</label>
                                    <div class="col-sm-10">
                                        <select onchange="updateSourceType();" class="form-control" name="source_type" id="selectTypeSource">
                                            <option value="0">Http/Https </option>
                                            <option value="1">Rtpm Push</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="http_sources">

                                    <div class="row rtmpPushInfo" style="display: none;" >
                                        <h4 style="text-align: center"></h4>
                                    </div>

                                    <table class="table table-hover tableHttpSources">
                                        <thead>
                                        <tr>
                                            <th>Enabled</th>
                                            <th>Backup</th>
                                            <th>Name</th>
                                            <th>Source Url</th>
                                            <th>
                                                <button type="button" class="btn btn-success" onclick="addHttpSource()">Add More</button>
                                                <button id="btnAnalyzeSources" type="button" class="btn btn-warning" onclick="analyzeSources()">Analyze Sources</button>

                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <?php if($EditID) {
                                            foreach ($Sources as $source) {
                                                $source_id = $source['id'];
                                                $enabled = $source['is_enabled'] ? 'checked' : '';
                                                $backup = $source['is_backup'] ? 'checked' : '';
                                                $name = $source['name'];
                                                $url = $source['url'];
                                                ?>


                                                <tr>
                                                    <input type="hidden" name="source_id[]" value="<?=$source_id;?>" />
                                                    <td><div class="custom-control form-group">
                                                            <input type="hidden" name="source_enable[]" value="<?=$source['is_enabled'];?>" />
                                                            <input <?=$enabled;?>  type="checkbox"  onchange="toggleSourceEnable(this);"
                                                                                   class="bootstrap-switch" ></div></td>

                                                    <td><div class="custom-control form-group">
                                                            <input type="hidden" name="source_backup[]" value="<?=$source['is_backup'];?>" />
                                                            <input <?=$backup;?> type="checkbox" onchange="toggleSourceBackup(this);"
                                                                                 class="bootstrap-switch" ></div></td>

                                                    <td><div class="form-group">
                                                            <input value="<?=$name;?>" type="text" name="source_name[]" class="form-control" />
                                                        </div></td>

                                                    <td><div class="form-group">
                                                            <input value="<?=$url;?>" type="text" name="source_url[]" class="form-control inpSource" />
                                                        </div></td>

                                                    <td>
                                                        <button onclick="moveSourceUp($(this).parent().parent())" class="btn btn-flat btn-sm btn-info" type=button>
                                                            <i class="fa fa-angle-double-up"></i></button>
                                                        <button onclick="moveSourceDown($(this).parent().parent())" class="btn btn-flat btn-sm btn-warning" type=button>
                                                            <i class="fa fa-angle-double-down"></i></button>
                                                        <button title="Remove" class="btn btn-flat btn-sm btn-danger" type="button" onclick="$(this).parent().parent().remove(); removeSource(<?=$source_id;?>)">
                                                            <i class="fa fa-trash"></i></button>
                                                        <a onclick="showModalSources($(this).parent().parent().find('.inpSource'))" title="Provider Sources" class="btn btn-flat btn-sm btn-info" type="button">
                                                            <i class="fa fa-plus"></i></a>
                                                        <a title="Play in player" class="btn btn-flat btn-sm btn-info" type="button" href="vlc://<?=$source['url']?>">
                                                            <i class="fa fa-tv"></i></a>

                                                    </td>

                                                </tr>


                                            <?php }} ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row pb-3">
                    <div class="col-12">
                        <?php if($EditID) { ?>
                            <button onclick=submitAndRestart() type="button" class="btn btn-sm btn-warning float-right btnSubmit">Save & Restart</button>
                            <button onclick=submitForm() type="button" class="btn btn-sm btn-success float-right btnSubmit mr-3">Save</button>
                        <?php } else { ?>
                            <button onclick=submitAndRestart() type="button" class="btn btn-sm btn-warning float-right btnSubmit">Add & Start</button>
                            <button onclick=submitForm() type="button" class="btn btn-sm btn-success float-right btnSubmit mr-3">Add</button>
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
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
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
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script src="/dist/js/Modal2.js"></script>
<script>

    var RTMP_KEY = '<?=$RTMP_KEY;?>';

    function moveSourceUp(row) {

        // Get the previous element in the DOM
        var previous = row.prev();

        // Check to see if it is a tr
        if (previous.is("tr")) {
            // Move row above previous
            row.detach();
            previous.before(row);

            // draw the user's attention to it
            row.fadeOut();
            row.fadeIn();
        }
        // else - already at the top

    }

    function moveSourceDown(row) {

        // Get the previous element in the DOM
        var previous = row.next();

        // Check to see if it is a row
        if (previous.is("tr")) {
            // Move row above previous
            row.detach();
            previous.after(row);

            // draw the user's attention to it
            row.fadeOut();
            row.fadeIn();
        }

    }

    function toggleSourceEnable(obj) {

        if($(obj).prop('checked')) $(obj).parent().parent().parent().find('input[type="hidden"]').val(1);
        else  $(obj).parent().parent().parent().find('input[type="hidden"]').val(0);
    }

    function toggleSourceBackup(obj) {

        if($(obj).prop('checked')) $(obj).parent().parent().parent().find('input[type="hidden"]').val(1);
        else  $(obj).parent().parent().parent().find('input[type="hidden"]').val(0);
    }

    function removeSource(id) {
        $("#formStream").prepend("<input type=hidden name='remove_source[]' value='"+id+"' />")
    }

    function updateSourceType() {

        v = Number($("#selectTypeSource").val());

        if(v) {
            server_id = $("select[name='server_id']").val()

            $.post('../../ajax/streams/pActions.php',{'action': 'get_server_info', 'server_id' : server_id }, function(data) {

                server = data.data;

                $(".rtmpPushInfo").show();
                $(".tableHttpSources").hide();
                $(".rtmpPushInfo h4 ").html("Use this address on the encoder to push the stream: <br/><br/><b>rtmp://"+server.address+":1935/aces_rtmp/<?=$EditStream->id?>?key="+RTMP_KEY+"</b>");

            },'json');

        } else {
            $(".rtmpPushInfo").hide();
            $(".tableHttpSources").show();
        }

    }

    var analyzeLock = 0;
    var intAnalyze = false;
    function analyzeSources() {

        if(analyzeLock > 0 )
            return;

        analyzeLock = $(".inpSource").length;
        $('#btnAnalyzeSources').html("<i class='fa fa-gear fa-spin mr-1'></i> Analyzing");
        $('#btnAnalyzeSources').prop('disabled', true);

        intAnalyze = setInterval(function() {
            console.log("Analyze Lock " + analyzeLock);
            if(analyzeLock < 1 ) {
                $('#btnAnalyzeSources').html("Analyze Sources");
                $('#btnAnalyzeSources').prop('disabled', false);
                clearInterval(intAnalyze);
            }

        }, 1000);

        $(".inpSource").each(function() {

            var stream_url = $(this).val();
            var element = $(this).parent();

            if( stream_url != '' ) {

                $.ajax({
                    url: '../../ajax/streams/pActions.php',
                    type: 'post',
                    dataType: 'json',
                    timeout: 15000,
                    data: {action: 'analyze_stream_source', 'stream_url' : stream_url },
                    success: function (resp) {

                        analyzeLock--;

                        info = resp.data;

                        if($(element).find('.stream-info').length)
                            $(element).find('.stream-info').remove();

                        $(element).append("<ul class='stream-info'> " +
                            "<li><i class='fa fa-image'></i> <span> "+ info.video.resolution + "</span></li>" +
                            "<li><i class='fa fa-video'></i> <span> "+ info.video.codec + "</span></li>" +
                            "<li><i class='fa fa-volume-high'></i> <span> "+ info.audio.codec + "</span></li>" +
                            "<li><i class='fa fa-film'></i> <span> "+ info.video.fps + "</span></li>" +
                        "</ul>");

                    },
                    error : function (xhr) {
                        analyzeLock--;

                        if($(element).find('.stream-info').length)
                            $(element).find('.stream-info').remove();

                        $(element).append("<ul class='stream-info'>" +
                            "<li class='text-danger'><span class='text-danger'>Stream Down</span></li>");


                    }
                });

            }

        });
    }

    var inpSource = null;
    function addProviderSource(url) {

        $(inpSource).val(url);
        Modal2.hide();
    }

    function showModalSources(element) {
        inpSource = element;
        Modal2.get({url: '../../modals/mProviderStreamSources.php', size : 'full'});
    }

    function addHttpSource() {

        htm = '<tr><td><div class="custom-control form-group">';
            htm += '<input checked type="checkbox" name="source_enable[]" class="bootstrap-switch" ></div></td>';

        htm += '<td><div class="custom-control form-group">';
            htm += '<input type="checkbox" name="source_backup[]" class="bootstrap-switch" ></div></td>';

        htm += '<td><div class="form-group">';
            htm += '<input type="url" name="source_name[]" class="form-control" />';
            htm += '</div></td>';

        htm += '<td><div class="form-group">';
            htm += '<input type="url"  name="source_url[]" class="form-control inpSource" />';
            htm += '</div></td>';

        htm += '<td><button onclick="moveSourceUp($(this).parent().parent())" class="btn btn-flat btn-sm btn-info" type=button><i class="fa fa-angle-double-up"></i></button>' +
                '<button onclick="moveSourceDown($(this).parent().parent())" class="btn btn-flat btn-sm btn-warning" type=button><i class="fa fa-angle-double-down"></i></button>' +
                '<button title="Remove" class="btn btn-flat btn-sm btn-danger" type="button" onclick="$(this).parent().parent().remove();"><i class="fa fa-trash"></i></button>' +
                '<button title="" class="btn btn-flat btn-sm btn-info" type="button" onclick="showModalSources($(this).parent().parent().find(\'.inpSource\'))"><i class="fa fa-plus"></i></button>' +
            '</td>';

        htm += '</tr>';


        $(".tableHttpSources tbody").append(htm);

        $("input[name='source_enable[]']").last().bootstrapSwitch();
        $("input[name='source_backup[]']").last().bootstrapSwitch();

    }

    function toggleStreamProfileOptions(val) {
        if(val == 0 )
            $(".streamProfileForm").fadeIn();
        else
            $(".streamProfileForm").fadeOut();
    }

    var lockRefesh;
    function refreshTvgList(input_obj) {

        if (lockRefesh) clearTimeout(lockRefesh);

        lockRefesh = setTimeout(function(){

            var value = input_obj.value;
            var dataList = document.getElementById('tvglist');

            $(dataList).empty();

            $.ajax({
                url: '../../ajax/streams/gTvgDataList.php',
                type: 'post',
                dataType: 'json',
                data: { 'get_tvg_datalist': value },
                success: function(data) {

                    for(var i=0; data.length > i; i++) {
                        arr = data[i];
                        option = document.createElement('option');
                        option.value = arr.tvg_id;
                        option.innerHTML = arr.channel_name + ", "+ arr.epg_name;
                        dataList.appendChild(option);
                        input_obj.display = "block";
                    }

                }

            });
            input_obj.focus();

        }, 500);

        return true;

    }

    function submitAndRestart() {
        $("#formStream ").find("input[name='start']").val(1);
        submitForm();
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

                toastr.success("<?=$EditID ? 'Stream Saved' : 'Stream Added'?>");
                setTimeout( function() { window.location.href ="../../streams.php"}  , 800 );


            }, error: function (xhr) {
                $("#formStream ").find("input[name='start']").val(0);
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


    $(document).ready(function(){

        //Modal2.get({url: '../../modals/mProviderStreamSources.php', size : 'full'});

        <?php if($EditID) { ?>

            $("input[name='name']").val('<?=$EditStream->name?>');
            $("select[name='server_id']").val('<?=$EditStream->server_id?>');
            $("select[name='category_id']").val('<?=$EditStream->category_id?>');
            $("input[name='tvg_id']").val('<?=$EditStream->tvg_id?>');
            $("select[name='stream_profile_id']").val('<?=$EditStream->stream_profile_id?>');
            $("select[name='source_type']").val('<?=$EditStream->source_type;?>');
            toggleStreamProfileOptions(<?=$EditStream->stream_profile_id?>);
            updateSourceType()


            <?= $EditStream->ondemand ? '$("input[name=\'ondemand\']").prop("checked",true);'  : ''?>
            <?= $EditStream->enabled ? '$("input[name=\'enable\']").prop("checked",true);'  : ''?>
            <?= $EditStream->stream ? '$("input[name=\'stream\']").prop("checked",true);'  : ''?>
            <?= $EditStream->auto_update_name ? '$("input[name=\'auto_update\']").prop("checked",true);'  : ''?>

            <?php foreach($EditStream->getBouquets() as $id ) { ?>
                <?= '$("input[name=\'bouquets[]\'][value='.$id.']").prop("checked",true);'; ?>
            <?php } ?>

            <?php if($StreamProfile->only_for_chan_id) { ?>

                $('.streamProfileForm').show();

                $("#formStream select[name='stream_profile_id']").val('0');

                $("#formStream select[name='stream_profile[stream_profile_id]']").val('');

                $("#formStream select[name='stream_profile[video_codec]']").val('<?=$StreamProfile->video_codec;?>');
                $("#formStream select[name='stream_profile[audio_codec]']").val('<?=$StreamProfile->audio_codec;?>');

                $("#formStream select[name='stream_profile[preset]']").val('<?=$StreamProfile->preset;?>');

                $("#formStream input[name='stream_profile[video_bitrate_kbps]']").val('<?=$StreamProfile->video_bitrate;?>');
                $("#formStream input[name='stream_profile[audio_bitrate_kbps]']").val('<?=$StreamProfile->audio_bitrate;?>');

                $("#formStream input[name='stream_profile[screen_size]']").val('<?=$StreamProfile->screen_size;?>');
                $("#formStream input[name='stream_profile[framerate]']").val('<?=$StreamProfile->framerate;?>');
                $("#formStream input[name='stream_profile[threads]']").val('<?=$StreamProfile->threads;?>');

                $("#formStream input[name='stream_profile[probesize]']").val('<?=$StreamProfile->probe_size;?>');
                $("#formStream input[name='stream_profile[timeout]']").val('<?=$StreamProfile->timeout;?>');
                $("#formStream input[name='stream_profile[screen_size]']").val('<?=$StreamProfile->screen_size;?>');
                $("#formStream textarea[name='stream_profile[user_agent]']").val('<?=$StreamProfile->user_agent;?>');
                $("#formStream textarea[name='stream_profile[post_args]']").val('<?=$StreamProfile->post_args;?>');
                $("#formStream input[name='stream_profile[proxy]']").val('<?=$StreamProfile->proxy;?>');

                <?= $StreamProfile->gen_pts ? "$(\"#formStream input[name='stream_profile[gen_pts]']\").prop('checked',true);" : ''; ?>
                <?= $StreamProfile->native_frames ? "$(\"#formStream input[name='stream_profile[native_frames]']\").prop('checked',true);" : ''; ?>
                <?= $StreamProfile->stream_all ? "$(\"#formStream input[name='stream_profile[stream_all]']\").prop('checked',true);" : ''; ?>
                <?= $StreamProfile->skip_no_audio ? "$(\"#formStream input[name='stream_profile[skip_no_audio]']\").prop('checked',true);" : ''; ?>
                <?= $StreamProfile->skip_no_video ? "$(\"#formStream input[name='stream_profile[skip_no_video]']\").prop('checked',true);" : ''; ?>

            <?php } else { ?>
                $("#formModalChannel select[name='stream_profile_id']").val('<?=$StreamProfile->id;?>');
            <?php } ?>

        <?php } else {  ?>

            $('select[name="stream_profile_id"] option:eq(1)').attr('selected', 'selected');
            $("input[name='enable']").prop('checked',true);
            $("input[name='stream']").prop('checked',true);


        <?php } ?>

        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

        <?php if(!$EditID){ ?>
            addHttpSource();
        <?php } ?>


    });




</script>
</body>
</html>