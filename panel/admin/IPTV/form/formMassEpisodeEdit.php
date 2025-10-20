<?php

    if(!$AdminID=adminIsLogged()){
        Redirect("/admin/login.php");
        exit;
    }

    $ADMIN = new \ACES2\Admin($AdminID);

    if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
        Redirect("/admin/profile.php");
    }

    $db = new \ACES2\DB;

    //VALIDATE FOR SQL INJECTION
    $ids = explode(",", $_REQUEST['ids']);
    foreach($ids as $id) {
        $IDs[] = (int)$id;
    }

    if(!count($IDs))
        Redirect("../episodes.php");

    $sql_id = implode(",", $IDs);
    $r_episodes = $db->query("SELECT e.id,e.title,e.about,e.rate,e.release_date,e.rate,
            e.number as episode_number,s.number as season_number, v.tmdb_id,v.tmdb_lang 
        FROM iptv_series_season_episodes e 
        RIGHT JOIN iptv_series_seasons s on e.season_id = s.id
        RIGHT JOIN iptv_ondemand v ON v.id = s.series_id 
        WHERE e.id IN ($sql_id)");


    $TMDB_API = \ACES2\Settings::get(\ACES2\IPTV\Settings::TMDB_API_KEY);
    $DefaultLang = \ACES2\Settings::get(\ACES2\IPTV\Settings::TMDB_LANGUAGE);

    $do=0;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITENAME; ?>| Mass Episode Edit </title>
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

<body class="hold-transition sidebar-mini text-sm layout-footer-fixed">
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
                        <h1>Mass Episode Edit</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item "><a href="../episodes.php">All Episodes</a></li>
                            <li class="breadcrumb-item active">Mass Episode Edit</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button type="submit" form="form" class="btn btn-primary btn-sm float-right ml-3">Save</button>
                        <a href="../episodes.php" class="btn btn-default btn-sm float-right">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <form id="form">
                    <input type="hidden" name="action" value="mass_edit_episodes" />


                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Episodes</h5
                                </div>

                                <div class="card-body">

                                    <?php while($episode = $r_episodes->fetch_assoc()) {

                                        $id = $episode['id'];
                                        $lang = !empty($episode['language']) ? $episode['language'] : $DefaultLang;

                                        ?>

                                            <!-- EPISODE ROW -->
                                            <div class="div pb-5">

                                                <div class="row">
                                                    <div class="col-lg-6 col-xl-4">
                                                        <div class="form-group">
                                                            <label>Episode Title</label>
                                                            <div class="input-group">
                                                                <input name="title[<?=$id?>]" class="form-control" AUTOCOMPLETE="OFF"
                                                                       value="<?=$episode['title']?>" />
                                                                <div class="input-group-append">
                                                                    <button onclick="getEpisodeInfo('<?=$episode['tmdb_id']?>','<?=$id;?>','<?=$episode['episode_number'];?>','<?=$episode['season_number'];?>','<?=$lang?>')"
                                                                            type="button" class="btn btn-success btn-flat">
                                                                        Get Info
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 col-lg-2">
                                                        <div class="form-group">
                                                            <label>Episode #</label>
                                                            <input type="number" name="episode_number[<?=$id?>]"
                                                                   value="<?=$episode['episode_number'];?>"
                                                                   class="form-control" />
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 col-lg-2">
                                                        <div class="form-group">
                                                            <label>Season #</label>
                                                            <input type="number" name="season_number[<?=$id?>]"
                                                                   value="<?=$episode['season_number'];?>"
                                                                   class="form-control" />
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 col-lg-2">

                                                        <div class="form-group">
                                                            <label>Rate</label>
                                                            <input name="rate[<?=$id?>]" class="form-control"
                                                                   value="<?=$episode['rate']?>" />

                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 col-lg-2">
                                                        <div class="form-group">
                                                            <label>Release Date</label>
                                                            <input name="release_date[<?=$id?>]" type="date" class="form-control"
                                                                   value="<?=$episode['release_date']?>" />
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <div class="form-group col-12">
                                                        <label>Episode Cover</label>
                                                        <input name="cover[<?=$id?>]" class="form-control"
                                                               placeholder="Enter a web address logo" />
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="form-group col-12">
                                                        <label>About</label>
                                                        <textarea name="about[<?=$id?>]"
                                                                  rows=4
                                                                  class="form-control"><?=$episode['about'];?></textarea>
                                                    </div>
                                                </div>



                                            </div>



                                    <?php } ?>

                                </div>
                            </div>

                        </div>

                        <div class="col-12">
                            <button type="submit" form="form" class="btn btn-primary btn-sm float-right ml-3">Save</button>
                            <a href="../episodes.php" class="btn btn-default btn-sm float-right">Go Back</a>
                        </div>
                    </div>

                </form>

            </div>

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
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    var TMDB_API_KEY = '<?=$TMDB_API;?>';

    function getEpisodeInfo(tmdb_id,id,episode_number,season_number,lang) {

        if( TMDB_API_KEY == '' ) {
            toastr.error("Please set the api key v3 from The Movie Database first."); return false; }

        if(!episode_number || !season_number)
            toastr.error("Enter episode and season number.");


        url = 'https://api.themoviedb.org/3/tv/'+tmdb_id+'/season/'+season_number+'/episode/'+episode_number+
            '?api_key='+TMDB_API_KEY+'&language='+lang;

        $.ajax({
            url: url,
            dataType: 'json',
            error: function(response) {
                if (response['status'] == 404) {
                    toastr.error("Could not retrieve episode. Probably the episode #" + episode_number + " " +
                        " do not exist in this season #"+season_number);
                }
            }, success: function(response) {

                $("#form input[name='title["+id+"]']").val(response.name);
                $("#form textarea[name='about["+id+"]']").val(response.overview);
                $("#form input[name='release_date["+id+"]']").val(response.air_date)
                $("#form input[name='runtime_minute["+id+"]']").val(response.runtime);
                $("#form input[name='rate["+id+"]']").val(response.vote_average);
                if(response.still_path)
                    $("#form input[name='cover["+id+"]']")
                        .val("http://image.tmdb.org/t/p/original/"+response.still_path);

            }
        });


    }

    $("#form").submit(function (e) {

        e.preventDefault();

        $("button[type='submit']").prop('disabled', true);

        $.ajax({
            url: '../ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                toastr.success("Saved")
                setTimeout(function () {
                    window.location.href = "../episodes.php";
                }, 800);

            }, error: function (xhr) {

                $("button[type='submit']").prop('disabled', false);

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

    });

</script>
</body>
</html>