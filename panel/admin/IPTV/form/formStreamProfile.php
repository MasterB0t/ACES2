<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(""))
    Redirect("/admin/profile.php");

$PageTitle = "Add Stream Profile";
if($EditID = @(int)$_GET['id']) {
    $StreamProfile = new \ACES2\IPTV\StreamProfile($EditID);
    $PageTitle = "Edit Stream Profile";
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
                            <li class="breadcrumb-item active"><a href="../stream_profiles.php">Stream Profiles</a></li>
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

            <form id="formStreamProfile">
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_stream_profile" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_stream_profile" />
                <?php } ?>
                <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.stream_profile');?>" />
                <div class="row">
                    <div class="col-12 ">
                        <div class="card">

                            <div class="card-body">

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group ">
                                            <label>Stream Profile Name</label>
                                            <input name="name" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="streamProfileForm" >

                                    <div class="row ">

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Video Codec</label>
                                                <select name="video_codec" class="form-control select2" >
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
                                                <input name="video_bitrate_kbps" class="form-control encode-video" />
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-3">
                                            <div class="form-group">
                                                <label>Audio Codec</label>
                                                <select name="audio_codec" class="form-control select2" >
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
                                                <input name="audio_bitrate_kbps" class="form-control encode-audio" />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row  ">

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Screen Size</label>
                                                <input name="screen_size" class="form-control encode-video"
                                                       placeholder="1920x1200  (Width)x(Height)"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Frame Rate</label>
                                                <input name="framerate" class="form-control encode-video"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Threads</label>
                                                <input name="threads" class="form-control encode-video"  />
                                            </div>
                                        </div>

                                        <div class="col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Preset</label>
                                                <select name="preset" class="form-control select2 encode-video" >
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

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group ">
                                                <label>Probe Size</label>
                                                <input type='number' class="form-control" name="probe_size"
                                                    value="<?=$EditID ? $StreamProfile->probe_size : '' ; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group ">
                                                <label>Analyze Duration</label>
                                                <input type="number" class="form-control" name="analyze_duration"
                                                       value="<?=$EditID ? $StreamProfile->probe_size : '' ; ?>" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row ">

                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Restart stream every X hours</label>
                                                <input name="timeout" class="form-control" />
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Http Proxy</label>
                                                <input name="proxy" class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>User Agent</label>
                                                <textarea name="user_agent" class="form-control"></textarea>
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <div class="form-group" >
                                                <label>FFMPEG Extra Pre Arguments (optional)</label>
                                                <textarea name="pre_args" class="form-control"><?=$StreamProfile->pre_args?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group" >
                                                <label>FFMPEG Extra Post Arguments (optional)</label>
                                                <textarea name="post_args" class="form-control"><?=$StreamProfile->post_args?></textarea>
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

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-sm float-right">Save</button>
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

    $("select[name='video_codec").change(function() {
        if(this.value == 'copy')
            $('.encode-video').prop('disabled', true)
        else
            $('.encode-video').prop('disabled', false)


    })

    $("select[name='audio_codec").change(function() {

        if( this.value == 'copy')
            $('.encode-audio').prop('disabled', true)
        else
            $('.encode-audio').prop('disabled', false)
    });

    $(document).ready(function(){

        <?php if($EditID) { ?>
            $("#formStreamProfile input[name='name']").val('<?=$StreamProfile->name;?>');

            $("#formStreamProfile select[name='video_codec']").val('<?=$StreamProfile->video_codec;?>');
            $("#formStreamProfile select[name='audio_codec']").val('<?=$StreamProfile->audio_codec;?>');

            $("#formStreamProfile select[name='preset']").val('<?=$StreamProfile->preset;?>');

            $("#formStreamProfile input[name='video_bitrate_kbps']").val('<?=$StreamProfile->video_bitrate;?>');
            $("#formStreamProfile input[name='audio_bitrate_kbps']").val('<?=$StreamProfile->audio_bitrate;?>');

            $("#formStreamProfile input[name='screen_size']").val('<?=$StreamProfile->screen_size;?>');
            $("#formStreamProfile input[name='framerate']").val('<?=$StreamProfile->framerate;?>');
            $("#formStreamProfile input[name='threads']").val('<?=$StreamProfile->threads;?>');

            $("#formStreamProfile input[name='probesize']").val('<?=$StreamProfile->probe_size;?>');
            $("#formStreamProfile input[name='timeout']").val('<?=$StreamProfile->timeout;?>');

            $("#formStreamProfile textarea[name='user_agent']").val('<?=$StreamProfile->user_agent;?>');
            $("#formStreamProfile input[name='proxy']").val('<?=$StreamProfile->proxy;?>');

            <?= $StreamProfile->gen_pts ? "$(\"#formStreamProfile input[name='sgen_pts']\").prop('checked',true);" : ''; ?>
            <?= $StreamProfile->native_frames ? "$(\"#formStreamProfile input[name='native_frames']\").prop('checked',true);" : ''; ?>
            <?= $StreamProfile->stream_all ? "$(\"#formStreamProfile input[name='stream_all']\").prop('checked',true);" : ''; ?>
            <?= $StreamProfile->skip_no_audio ? "$(\"#formStreamProfile input[name='skip_no_audio']\").prop('checked',true);" : ''; ?>
            <?= $StreamProfile->skip_no_video ? "$(\"#formStreamProfile input[name='skip_no_video']\").prop('checked',true);" : ''; ?>

        <?php }  ?>

        $("#formStreamProfile select[name='video_codec']").change();
        $("#formStreamProfile select[name='audio_codec']").change();

        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();

    });

    $("#formStreamProfile").submit(function(e) {

        e.preventDefault();
        $.ajax({
            url: "../ajax/streams/pStreamProfile.php",
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../stream_profiles.php" }, 1200)
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