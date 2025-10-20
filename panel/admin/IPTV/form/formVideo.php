<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("/admin/login.php");

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL))
    Redirect("/admin/profile.php");

$runtime_minutes = "";
$TYPE = $_GET['type'] == 'series' ? 'series' : 'movies';
$PageTitle = "Add $TYPE";
$Video = new \ACES2\IPTV\Video();

$Video->tmdb_lang = \ACES2\IPTV\Settings::get( \ACES2\IPTV\Settings::TMDB_LANGUAGE );

if($EditID = @(int)$_GET['id']) {
    $Video = new \ACES2\IPTV\Video($EditID);
    $TYPE = $Video->is_series ? 'series' : 'movies';
    $runtime_minutes = (int)$Video->runtime_seconds / 60;
    $PageTitle = "Edit '$Video->name'";
}

$db = new \ACES2\DB;
$r_cats= $db->query("SELECT id,name FROM iptv_stream_categories ");
$r_servers = $db->query("SELECT id,name FROM iptv_servers  ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets  ");

$r=$db->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
$TMDB_KEY = $r->fetch_assoc()['value'];

include DOC_ROOT . "/includes/languages.php";

$laguanges = [];
//if($TMDB_KEY) {
//    $ch = curl_init();
//    curl_setopt($ch, CURLOPT_URL, "https://api.themoviedb.org/3/configuration/languages?api_key=".$TMDB_KEY);
//    curl_setopt($ch, CURLOPT_HEADER, 0);
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
//    $language = json_decode(curl_exec($ch), 1);
//    curl_close($ch);
//}


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
                            <li class="breadcrumb-item active"><a href="../videos.php">All Videos</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formVideo" type="submit" class="btn btn-success float-right">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../videos.php"
                           type="button" class="btn btn-default float-right mr-3">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="formVideo">
                <input type="hidden" name='tmdb_id' value='<?=$Video->tmdb_id;?>'/>

                <?php if($EditID) { ?>
                    <input type="hidden" name="action" value="update_video" />
                    <input type="hidden" name="id" value="<?=$EditID;?>" />
                <?php } else {  ?>
                    <input type="hidden" name="type"  value="<?=$TYPE;?>" />
                    <input type="hidden" name="action" value="add_video" />
                <?php } ?>
                <div class="row">
                    <div class="col-xl-6 ">
                        <!-- CARD INFO -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Information</h5>
                            </div>

                            <div class="card-body">

                                <div class="form-group">
                                    <label>Name</label>
                                    <div class="input-group">
                                        <input name="name" class="form-control" list="listName" AUTOCOMPLETE="OFF"
                                               value="<?=$Video->name;?>" />
                                        <datalist id="listName"></datalist>
                                        <div class="input-group-append">
                                            <select name="tmdb_lang" onchange="setLangTMDB(this.value)" class="form-control select2">
                                                <?php foreach($__LANGUAGES as $k => $l) { ?>
                                                    <option value="<?=$k?>"><?=$l?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="input-group-append">
                                            <button onclick="searchMovieTMDB()" type="button" class="btn btn-success btn-flat">
                                                Get Info
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label>Categories</label>
                                    <select id="selectCategory" onchange="addCategory(this)" class="form-control select2" >
                                        <option value="">Add Categories</option>
                                        <?php while($o = $r_cats->fetch_assoc()) { ?>
                                            <option value="<?=$o['id'];?>"><?=$o['name'];?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="pt-3 pb-3 list-categories">
                                        <?php if($EditID) {
                                            foreach($Video->getCategories() as $cat ) { ?>
                                                <span class='badge badge-success p-2 m-2 text-md '>
                                                    <input type='hidden' name='categories[]' value='<?=$cat['id']?>'/><?=$cat['name']?>
                                                    <a class='ml-2 text-danger' href='#' onclick='removeCat(this)'>X</a></span>
                                        <?php }} ?>
                                    </div>
                                </div>

                                <!-- GENRES -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Genre 1</label>
                                            <input name="genre1" class="form-control" value="<?=$Video->genre1;?>" />
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Genre 2</label>
                                            <input name="genre2" class="form-control" value="<?=$Video->genre2;?>" />
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Genre 3</label>
                                            <input name="genre3" class="form-control" value="<?=$Video->genre3;?>" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Release Date</label>
                                            <input class="form-control" type="date" placeholder="DD/MM/YYYY"
                                                   name="release_date" value="<?=$Video->release_date;?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Runtime Minutes</label>
                                            <input class="form-control" type="text" name="runtime_minutes"
                                                   value="<?=$runtime_minutes?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Rating</label>
                                            <input class="form-control" type="text"  name="rating"
                                                   value="<?=$Video->rating;?>" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group age-rating-movies">
                                    <label>Age Rating</label>
                                    <select name="age_rate" class="form-control ">
                                        <option value="0">Not Rated.</option>
                                        <option value="U">U/G General Audiences.</option>
                                        <option value="PG">PG - Parental Guidance</option>
                                        <option value="12"> 12A/12 - Suitable for 12 years and over</option>
                                        <option value="PG-13"> PG-13 - Parents Strongly Cautioned.</option>
                                        <option value="R"> R - Restricted</option>
                                        <option value="NC-17"> NC-17 - No One 17 and Under Admitted</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label> Director </label>
                                    <input type="text" name="director" class="form-control"
                                           value="<?=$Video->director?>" />
                                </div>

                                <div class="form-group">
                                    <label>Trailer Link</label> <input
                                            name="youtube_trailer" type="text" class="form-control"
                                            value="<?=$Video->youtube_trailer;?>" />
                                </div>

                                <div class="form-group">
                                    <label> Cast </label>
                                    <input type="text" name="cast"  class="form-control" value="<?=$Video->cast;?>" />
                                </div>

                                <div class="form-group">
                                    <label>Movie Poster</label>
                                    <input class="form-control" name="logo" placeholder="Enter a Web address logo."/>
                                </div>

                                <div class="form-group">
                                    <label>Background Poster</label>
                                    <input class="form-control" name="back_logo" placeholder="Enter a Web address logo." />
                                </div>

                                <div class="form-group">
                                    <label>Description (optional)</label> <textarea
                                            name="about"  class="form-control"
                                            placeholder><?=$Video->about;?></textarea>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="col-xl-6 ">
                        <!-- FILE CARD -->
                        <?php if($TYPE != 'series') { ?>
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
                                    <select name="transcoding" class="form-control select2">
                                        <option value="copy"> COPY Do not transcode file. </option>
                                        <option value="h264:aac"> H264 AAC </option>
                                        <option selected title='Create a symbolic link instead of copy or processing local files or redirect if is a web source.'
                                                value="redirect"> Symlink / Redirect </option>
                                        <option title=''
                                                value="stream"> Stream </option>
                                    </select>
                                </div>

                                <div class='form-group'>
                                    <label>Container </label>
                                    <select name='container select2' class='form-control'>
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
                        <?php } ?>

                        <!-- BOUQUET CARD -->
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

                <div class="row pb-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm float-right">
                            <?=$EditID ? 'Save' : 'Add';?>
                        </button>

                        <a href="../videos.php"
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

    var tmdb_api_key = '<?=$TMDB_KEY;?>';
    var dataList = document.getElementById('listName');
    var TYPE = "<?=$TYPE?>";
    var TMDB_LANG = "<?=$Video->tmdb_lang?>";
    var TMDB_ID = <?=$Video->tmdb_id?>;
    
    function toggleAllBouquets(s) {

        if(s == true ) {
            $("input[name=bouquets\\[\\]]").prop("checked",true);
        } else
            $("input[name=bouquets\\[\\]]").prop("checked",false);

    }

    function setLangTMDB(l) {
        $(".button-language").html(l);
        $("input[name='tmdb_lang']").val(l);
        TMDB_LANG = l;
        if(TMDB_ID) {
            fetchInfoTMDB();
        }

    }

    function getContent(obj,path='/home',type='vods'){

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
            }, error: function() {
                $(obj).html('');
                $(obj).select2();
            }
        });
    }

    function searchMovieTMDB() {

        if( tmdb_api_key == '' ) { alert("Please set the api key v3 from The Movie Database first."); return false; }

        var input_obj = $("#formVideo input[name='name']");

        query = input_obj.val();

        if(query == "") { alert('Please enter a name first') ; return false; }

        //url = 'http://api.themoviedb.org/3/search/movie?api_key=' + tmdb_api_key + '&query=' +query+ '&language=en-US';
        if(TYPE == 'series')
            url = 'https://api.themoviedb.org/3/search/tv?api_key=';
        else
            url = 'https://api.themoviedb.org/3/search/movie?api_key='


        $(dataList).empty();

        $.ajax({
            url: url + tmdb_api_key + '&query=' +query+ '&language='+TMDB_LANG,
            dataType: 'json',

            success: function (response) {
                for (var i = 0; i < response.results.length; i++) {

                    if(TYPE == 'series') {
                        date = response.results[i].first_air_date.split('-');
                        title = response.results[i].name;
                    } else {
                        date = response.results[i].release_date.split('-');
                        title = response.results[i].title;
                    }

                    year = date[0];

                    option = document.createElement('option');
                    option.value = response.results[i].id;
                    option.innerHTML = title+" ("+year+")";
                    dataList.appendChild(option);

                }
            }, error: function (xhr) {
                toastr.error("Fail to fetch info from TMDB.");
            }
        });

        $(input_obj).blur();
        input_obj.focus();

    }

    function fetchInfoTMDB() {

        //FETCHING
        if(TYPE == 'series')
            var url = 'https://api.themoviedb.org/3/tv/' ;
        else
            var url = 'https://api.themoviedb.org/3/movie/';

        $.ajax({
            url: url + TMDB_ID + '?api_key=' + tmdb_api_key + '&language=' + TMDB_LANG,
            dataType: 'json',
            error: function () {
                toastr.error("Fail to fetch info from TMDB.");
            },
            success(response) {

                if (TYPE == 'series') {

                    name = response.name;
                    release_date = response.first_air_date;
                    age_rate_url = "https://api.themoviedb.org/3/tv/" + TMDB_ID + "/content_ratings?api_key=" + tmdb_api_key;

                } else {
                    name = response.title;
                    release_date = response.release_date;
                    age_rate_url = "https://api.themoviedb.org/3/movie/" + TMDB_ID + "/release_dates?api_key=" + tmdb_api_key
                }

                age_rate = '';
                $.get(age_rate_url, 'json', function (data) {
                    for (i = 0; i < data.results.length; i++) {
                        item = data.results[i];
                        if (item['iso_3166_1'] == 'US') {
                            if (TYPE == 'series')
                                age_rate = item['rating'];
                            else
                                age_rate = item['release_dates'][0]['certification'];
                            break;
                        }

                    }

                    $("select[name='age_rate']").val(age_rate);

                });


                $("#formVideo input[name='name']").val(name);
                date = release_date.split('-');

                //$("#formVideo input[name='year']").val(date[0]);
                $("#formVideo input[name='release_date']").val(release_date);
                $("#formVideo input[name='rating']").val(response.vote_average);

                $("#formVideo textarea[name='about']").val(response.overview);


                $("#formVideo input[name='genre1']").val('');
                $("#formVideo input[name='genre2']").val('');
                $("#formVideo input[name='genre3']").val('');

                for (var i = 0; i < 3; i++) {
                    if (response.genres[i])
                        $("#formVideo input[name='genre" + (i + 1) + "']").val(response.genres[i].name);
                }

                $("#formVideo input[name='runtime_minutes']").val(response.runtime);

                if (response.poster_path)
                    $("#formVideo input[name='logo']").val('http://image.tmdb.org/t/p/w500/' + response.poster_path);
                if (response.backdrop_path)
                    $("#formVideo input[name='back_logo']").val('http://image.tmdb.org/t/p/w500/' + response.backdrop_path);


                $("#formVideo input[name='tmdb_id']").val(TMDB_ID);

            }
        });

        $.ajax({
            url: url + TMDB_ID +"/videos?api_key="+tmdb_api_key + '&language='+TMDB_LANG,
            dataType: 'json',

            error: function () {
                toastr.error("Fail to fetch info from TMDB.");
            }, success: function (response) {
                if (response.results[0].site == 'YouTube')
                    $("#formVideo input[name='youtube_trailer']").val("https://www.youtube.com/watch?v=" + response.results[0].key);

            }
        });

        $.ajax({
            url: url + TMDB_ID + "/credits?api_key=" + tmdb_api_key + '&language=' + TMDB_LANG,
            dataType: 'json',
            error: function () {
                toastr.error("Fail to fetch info from TMDB.");
            },
            success: function (response) {

                cast = '';
                director = '';
                for (i = 0; i < response['cast'].length; i++) {
                    if (i != 0) cast += ", ";
                    cast += response['cast'][i]['name'];
                }

                for (i = 0; i < response['crew'].length; i++) {
                    if (response['crew'][i]['job'] == 'Director') {
                        director = response['crew'][i]['name'];
                        break
                    }
                }

                $("input[name='cast']").val(cast);
                $("input[name='director']").val(director);

            }
        });
    }

    function addCategory() {

        txt = $("#selectCategory>option:selected").text();
        val = $("#selectCategory>option:selected").val();

        if( $("input[value='"+val+"'][name='categories[]']").length == 0 )
            $(".list-categories").append("<span class='badge badge-success p-2 m-2 text-md '>" +
                "<input type='hidden' name='categories[]' value='"+val+"'/>"+txt+"<a class='ml-2 text-danger' href='#!' onclick='removeCat(this)'>X</a></span>");

        $("#selectCategory").val(0);
        $("#selectCategory").select2();

    }

    function removeCat(obj) {
        $(obj).parent('span').fadeOut('slow', function() { $(this).remove(); })
    }

    function fileLocation(val) {

        if(val=='local') {
            $(".webFileGroup").hide();
            $(".webfile").prop('disabled', true);
            $(".localFileGroup").show(); $("input[name='web_file']").val('');

        }  else {
            $(".localFileGroup").hide(); $(".webFileGroup").show();
            $(".webfile").prop('disabled', false);
        }

    }

    function editVideoFile(v) {

        if( v == 1  ) {

            f = decodeURIComponent($("#browser").val());
            d = f.substring(0, f.lastIndexOf("/"));
            getContent($("#browser"),d);

            $("input[name='video_web_file']").prop('disabled',false);
            $("select[name='container'], #browser, select[name='transcoding'], select[name='server_id'], .fileLocation").prop('disabled',false);

        } else  {
            $("input[name='video_web_file']").prop('disabled',true);

            $("select[name='container'], #browser, select[name='transcoding'], select[name='server_id'], .fileLocation").prop('disabled',true);
        }
    }

    function addSubtitle() {

        var htm = '<div class="row">';

        htm += '<div class="col-2"><div class="form-group"><label>Language</label>' +
            '<input class="form-control" name="subtitle_langs[]"/></div></div> ';

        htm += '<div class="col-9"><div class="form-group"><label>Subtitle File</label>' +
            '<select class="form-control sub-select2" name="subtitle_files[]" onchange="getContent(this,this.value,\'subs\' )" >' +
            '<option value="" >Select One [Files on /home/aces/aces_vods/ will be show here.]</option></select></div></div>';

        htm += '<div class="col-1"><div class="form-group"><label>Remove</label>' +
            '<button type="button" class="btn btn-sm btn-danger form-control" onclick="$(this).parent().parent().parent().remove();">' +
            '<i class="fa fa-remove"></i></button></div></div>';

        htm += '</div>';
        $('.subtitle-row ').append(htm);


        $(".sub-select2").last().select2();

        getContent($("select.sub-select2").last());

    }

    $("#formVideo select[name='transcoding']").change(function(){
        if( $(this).val() == 'symlink' ) { $(".subtitle-row").html(''); $('.row-add-subtitle').hide(); }
        else $('.row-add-subtitle').show();
    });

    //TMDB SEARCH
    $("#formVideo input[name='name']").on('input', function () {

        var id = this.value;

        if($('#listName option').filter(function(){
            return this.value === id;
        }).length) {
            TMDB_ID = id;
            fetchInfoTMDB();

        }
    });

    $("#formVideo").submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '../ajax/pVideos.php',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(function() { window.location.href="../videos.php" }, 1200)
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

    $(document).ready(function(){

        <?php if($EditID) { ?>

            $("#formVideo input[name='logo'], #formVideo input[name='back_logo']")
                .prop('placeholder', "Leave it blank for no change.");

            TMDB_LANG = '<?=$Video->tmdb_lang;?>';
            $(".button-language").html('<?=$Video->tmdb_lang;?>');

            $("select[name=age_rate]").val('<?=$Video->age_rate?>');
            $("select[name='container']").val('<?=$Video->file_container;?>');

            $("select[name='edit_video_file']").val(0);
            $("select[name='edit_video_file']").change();

            <?php if($Video->file_transcoding == 'symlink') { ?>
                $("select[name='transcoding']").val('redirect');
            <?php } else { ?>
                $("select[name='transcoding']").val('<?=$Video->file_transcoding;?>');
            <?php } ?>

            <?php foreach($Video->getBouquets() as $b ) { ?>
                $("input[name='bouquets[]'][value='<?=$b['id']?>']").prop('checked',true);
            <?php } ?>

        <?php }  ?>

        getContent($("#browser"));

        if(TYPE == 'series')
            $(".age-rating-movies").remove();
        else
            $(".age-rating-series").remove();

        $("#formVideo select[name='tmdb_lang']").val('<?=$Video->tmdb_lang?>');

        $(".select2").select2({theme: 'bootstrap4' });
        $(".boostrap-switch").bootstrapSwitch();


    });


</script>
</body>
</html>