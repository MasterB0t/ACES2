<?php


$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL))
    Redirect("/admin/profile.php");

$PageTitle = "Add Episode";
$Episode = new \ACES2\IPTV\Episode();
$SeriesID = (int)$_GET['series_id'];

if($EditID = @(int)$_GET['id']) {
    $Episode = new \ACES2\IPTV\Episode($EditID);
    $SeriesID = $Episode->series_id;
    $PageTitle = "Edit Episode '$Episode->title' ";
    $Vod = new \ACES2\IPTV\Video($Episode->series_id);
}

$back_link = (int)$_GET['series_id'] ? '../episodes.php?series_id='.$SeriesID : '../episodes.php';

$db = new \ACES2\DB();
$r=$db->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
$TMDB_KEY = $r->fetch_assoc()['value'];

$r_servers = $db->query("SELECT id,name FROM iptv_servers  ");
$r_ondemand = $db->query("SELECT id,name FROM iptv_ondemand ORDER BY name");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.themoviedb.org/3/configuration/languages?api_key=".$TMDB_KEY);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
$language = json_decode(curl_exec($ch), 1);
curl_close($ch);

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
                            <li class="breadcrumb-item active"><a href="<?=$back_link;?>">Episodes</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formEpisode" type="submit" class="btn btn-primary btn-sm float-right btnSubmit">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="<?=$back_link;?>"
                           type="button" class="btn btn-default btn-sm float-right mr-3">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formEpisode">
                <input type="hidden" name="tmdb_id" value="<?=$Episode->tmdb_id?>" />
                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_episode" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="action" value="add_episode" />
                <?php } ?>
                <div class="row">
                    <div class="col-12 col-md-6">

                        <!-- CARD INFO -->
                        <div class="card">

                            <div class="card-body">
                                <div class="form-group">
                                    <label>Episode Title</label>
                                    <div class="input-group">
                                        <input name="title" class="form-control" AUTOCOMPLETE="OFF"
                                               value="<?=$Episode->title;?>" />
                                        <div class="input-group-append">
                                            <button onclick="getEpisodeInfo()" type="button" class="btn btn-success btn-flat">
                                                Get Info
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Episode Number</label>
                                            <input type="number" name="episode_number"
                                                   value="<?=$Episode->episode_number;?>"
                                                   class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Season Number</label>
                                            <input type="number" name="season_number"
                                                   value="<?=$Episode->season_number;?>"
                                                   class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <labe>Series</labe>
                                    <select required class="form-control select2" name="series_id">
                                        <option value="0">Select Series</option>
                                        <?php
                                        while($s = $r_ondemand->fetch_assoc()) {
                                            echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label>Release Date</label>
                                            <input name="release_date" type="date" class="form-control" value="<?=$Episode->release_date?>" />
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label>Rate</label>
                                            <input name="rate" class="form-control" value="<?=$Episode->rate?>" />
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <div class="form-group">
                                            <label>Runtime Minutes</label>
                                            <input type="number" name="runtime_minute" class="form-control"
                                                   value="<?=(int)$Episode->runtime_seconds/60?>"/>
                                        </div>

                                    </div>

                                </div>


                                <div class="form-group">
                                    <label>Episode Cover</label>
                                    <input name="cover" class="form-control" placeholder="Enter a web address logo" />
                                </div>

                                <div class="form-group">
                                    <label>About</label>
                                    <textarea name="about" rows=7 class="form-control"><?=$Episode->about;?></textarea>
                                </div>

                            </div>



                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <!-- CARD FILE -->
                        <div class="card">
                            <div class="card-header">
                                <h5>File</h5>
                            </div>
                            <div class="card-body">

                                <?php if($EditID) { ?>
                                    <div class="form-group">
                                        <select onchange="editVideoFile(this.value)" name="edit_video_file" class="form-control" >
                                            <option selected value="1"> EDIT VIDEO FILE </option>
                                            <option value="0"> DO NOT EDIT VIDEO FILE </option>
                                        </select>
                                    </div>
                                <?php } ?>

                                <div class="form-group">
                                    <label>Select Stream Server</label>
                                    <select onchange="getContent($('#browser'),'/home','vods')" id="server_id"
                                            name="server_id" class="form-control select2">

                                        <?php
                                        while($s = $r_servers->fetch_assoc()) {
                                            echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                                        }
                                        ?>

                                    </select>
                                </div>

                                <div class="form-group" >
                                    <label> Transcoding Option </label>
                                    <select name="transcoding" class="form-control">
                                        <option value="copy"> COPY Do not transocode file. </option>
                                        <option value="h264:aac"> H264 AAC </option>
                                        <option selected title='Create a symbolic link instead of copy or processing local files or redirect if is a web source.'
                                                value="redirect"> Symlink / Redirect </option>
                                        <option title=''
                                                value="stream"> Stream </option>
                                    </select>
                                </div>

                                <div class='form-group'>
                                    <label>Container </label>
                                    <select name='container' class='form-control'>
                                        <option value='mp4'> MP4 </option>
                                        <option value='mkv'> MKV </option>
                                        <option value='avi'> AVI </option>
                                    </select>
                                </div>

                                <div class="form-group ">
                                    <label> File Location </label>
                                    <select class='form-control fileLocation' onchange='fileLocation(this.value)' >
                                        <option value='local'> SERVER LOCAL FILE </option>
                                        <option value='web'> Web Address </option>
                                    </select>
                                </div>

                                <div class="form-group localFileGroup ">
                                    <label>FILE</label>
                                    <select id="browser" onchange="getContent(this,this.value,'vods'); $(this).select2('open');"
                                            name="file"  class="form-control select2">
                                        <option value="/"> / </option>
                                    </select>
                                </div>

                                <div style='display:none;' class="form-group webFileGroup">
                                    <label> Web File </label>
                                    <input disabled type='text' name='file' class='form-control webfile' />
                                </div>

                                <div class="pt-3 row row-add-subtitle">
                                    <button onclick="addSubtitle()" type="button"
                                            class="btn btn-flat btn-sm btn-success "> ADD SUBTITLE </button>
                                </div>
                                <div style="padding-bottom:20px;" class="subtitle-row pt-2 pb-2"></div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="row clearfix">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm float-right btnSubmit">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="<?=$back_link;?>"
                           type="button" class="btn btn-default btn-sm float-right mr-3">Go Back</a>
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

    var TMDB_API_KEY = '<?=$TMDB_KEY;?>';

    function getContent(obj,path='/home',type='vods'){

        formats  = [".mp4", ".mp3", ".avi", ".mkv", '.flv', '.mp2', '.wav', '.ts', '.wav', 'srt' ];

        if(formats.some(path.includes.bind(path))) {
            return false;
        }

        server_id = $("#server_id").val();
        if(!server_id) server_id = 1;

        $.ajax({
            async:false,
            url: '../ajax/pServerContent.php',
            type: 'post',
            dataType: 'json',
            data: { 'get_server_content' : server_id, path:path, type:type },
            success: function(resp) {
                $(obj).html(resp.content);
                $(obj).select2({ width: '100%' });
            }
        });
    }

    function getEpisodeInfo() {

        var season_number = $("#formEpisode input[name='season_number']").val();
        var episode_number = $("#formEpisode input[name='episode_number']").val();

        var tmdb_id = '<?=$Vod->tmdb_id;?>';

        if( TMDB_API_KEY == '' ) {
            toastr.error("Please set the api key v3 from The Movie Database first."); return false; }

        if(!episode_number || !season_number)
            toastr.error("Enter episode and season number.");


        url = 'https://api.themoviedb.org/3/tv/'+tmdb_id+'/season/'+season_number+'/episode/'+episode_number+
            '?api_key='+TMDB_API_KEY+'&language=<?=$Vod->tmdb_lang;?>';

        $.ajax({
            url: url,
            dataType: 'json',
            error: function(response) {
                if (response['status'] == 404) {
                    toastr.error("Could not retrieve episode. Probably the episode #" + episode_number + " " +
                        " do not exist in this season #"+season_number);
                }
            }, success: function(response) {

                $("#formEpisode input[name='title']").val(response.name);
                $("#formEpisode textarea[name='about']").val(response.overview);
                $("#formEpisode input[name='release_date']").val(response.air_date)
                $("#formEpisode input[name='tmdb_id']").val(response.id);
                $("#formEpisode input[name='runtime_minute']").val(response.runtime);
                $("#formEpisode input[name='rate']").val(response.vote_average);
                if(response.still_path)
                    $("#formEpisode input[name='cover']")
                        .val("http://image.tmdb.org/t/p/original/"+response.still_path);


                //$("#formEpisode input[name='get_tmdb_info']").val(1);
            }
        });


    }

    function fileLocation(val) {

        if(val=='local') {  $(".webFileGroup").hide(); $(".localFileGroup").show(); $("input[name='web_file']").val('');}
        else {  $(".localFileGroup").hide(); $(".webFileGroup").show();  }

    }

    function editVideoFile(v) {
        if( v == 1  ) {

            f = decodeURIComponent($("#browser").val());
            d = f.substring(0, f.lastIndexOf("/"));
            getContent($("#browser"),d);

            $("input[name='video_web_file']").prop('disabled',false);
            $("select[name='container'], #browser, #server_id," +
                " select[name='transcoding'], select[name='server'], .fileLocation").prop('disabled',false);

        } else  {
            $("input[name='video_web_file']").prop('disabled',true);
            $("select[name='container'], #browser, #server_id," +
                " select[name='transcoding'], select[name='server'],  .fileLocation").prop('disabled',true);
        }
    }

    $("#formEpisode").submit(function(e) {

        $(".btnSubmit").prop('disabled',true);

        e.preventDefault();
        $.ajax({
            url: '../ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="<?=$back_link;?>" }, 1000)
            }, error: function (xhr) {
                $(".btnSubmit").prop('disabled',false);
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

    $(document).ready(function(){

        $("select[name='series_id']").val('<?=$SeriesID;?>');
        
        <?php if($EditID) { ?>

            $("#server_id").val('<?=$Episode->server_id;?>');
            $("input[name='cover']").prop('placeholder',"Leave it blank for no change.");
            $("select[name='edit_video_file']").val(0);
            $("Select[name='container']").val('<?=$Episode->file_container?>');
            <?php if($Episode->transcoding == 'symlink') { ?>
                 $("select[name='transcoding']").val('redirect');
            <?php }?>

            editVideoFile(0);

            $("select[name='series_id']").prop('disabled',true);

        <?php }  ?>

        $(".select2").select2();
        $(".boostrap-switch").bootstrapSwitch();
        getContent($("#browser"))


    });


</script>
</body>
</html>