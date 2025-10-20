<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged())
    Redirect("../login.php");

$DB = new \ACES2\DB();

$r_streams=$DB->query("SELECT c.id,c.name FROM  iptv_stream_categories c
               RIGHT JOIN iptv_channels s on c.id = s.category_id
               GROUP BY c.id ORDER BY c.ordering ");


$r_movies = $DB->query("SELECT c.id,c.name FROM iptv_in_category inc 
	RIGHT JOIN iptv_stream_categories c on inc.category_id = c.id
    RIGHT JOIN iptv_ondemand o ON o.type = 'movies' AND inc.vod_id = o.id 
    WHERE c.id > 0
    GROUP BY c.id ORDER BY c.m_ordering
");

$r_series = $DB->query("SELECT c.id,c.name FROM iptv_in_category inc 
	RIGHT JOIN iptv_stream_categories c on inc.category_id = c.id
    RIGHT JOIN iptv_ondemand o ON o.type = 'series' AND inc.vod_id = o.id 
    WHERE c.id > 0
    GROUP BY c.id ORDER BY c.s_ordering 
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Categories Order </title>
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
    <style>
        ul.todo-list { height:700px; width:400px; }
        ul.todo-list .selected { border-left:solid 3px #b3b3b3; }
        .n {margin-right:5px;}
        .todo-list { float:left; padding-left:25px; padding-right:25px; min-heigth:900px; }
        .cont { margin-top:20px; max-height:650px; overflow-y:auto;   display:inline-block; }
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
                        <h1>Categories Order</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">Categories</li>
                            <li class="breadcrumb-item active">Category Order</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" role="form" id="formCategoryOrder">
                    <input type="hidden" name="action" value="category_order" />
                    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.category')?>" />
                    <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">

                                <ul class="nav nav-tabs nav-fill" id="custom-tabs-three-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active"
                                           id="custom-tabs-1-tab" data-toggle="pill"
                                           href="#custom-tabs-1-content" role="tab"
                                           aria-controls="custom-tabs-1-home"
                                           aria-selected="true">Channel Categories</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link"
                                           id="custom-tabs-2-tab" data-toggle="pill"
                                           href="#custom-tabs-2-content" role="tab"
                                           aria-controls="custom-tabs-2-profile"
                                           aria-selected="false">Movies Categories</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link"
                                           id="custom-tabs-3-tab" data-toggle="pill"
                                           href="#custom-tabs-3-content" role="tab"
                                           aria-controls="custom-tabs-3"
                                           aria-selected="false">Series Categories</a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3" id="custom-tabs-three-tabContent">
                                    <div class="tab-pane fade show active" id="custom-tabs-1-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                                        <div class="p-3" style=" width:100%; overflow: auto;">

                                            <div  class="row p-4">
                                                <button type="button" onclick="sort('a','.ui-sortable-streams')" class="btn btn-success mr-3">Order A-Z</button>
                                                <button type="button" onclick="sort('z','.ui-sortable-streams')" class="btn btn-success mr-3">Order Z-A</button>
                                            </div>

                                            <ul  class="todo-list ui-sortable-streams connectedSortableStreams">

                                                <?php while($c = $r_streams->fetch_assoc()) {

                                                if($i > 14 ) { ?></ul><ul class="todo-list ui-sortable-streams connectedSortableStreams"> <?php $i=0; } ?>

                                                <li class="liCat" > <input type="hidden" name="categories[]" value='<?=$c['id'];?>' />
                                                    <span class='handle ui-sortable-handle'><i class='fa fa-ellipsis-v'></i></span>
                                                    <span class="text"><?=$c['name'];?></span> </li>

                                                <?php $i++; } ?>


                                            </ul></div>
                                    </div>

                                    <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                                        <div class="p3" style=" width:100%; overflow: auto;">

                                            <div class="row p-4">
                                                <button type="button" onclick="sort('a','.ui-sortable-movies')" class="btn btn-success mr-3">Order A-Z</button>
                                                <button type="button" onclick="sort('z','.ui-sortable-movies')" class="btn btn-success mr-3">Order Z-A</button>
                                            </div>

                                            <ul  class="todo-list ui-sortable-movies connectedSortableMovies">

                                                <?php $i=0; while($c = $r_movies->fetch_assoc()) {

                                                if($i > 14 ) { ?></ul><ul class="todo-list ui-sortable-movies connectedSortableMovies"> <?php $i=0; } ?>

                                                    <li class="liCat" > <input type="hidden" name="movies_categories[]" value='<?=$c['id'];?>' />
                                                        <span class='handle ui-sortable-handle'><i class='fa fa-ellipsis-v'></i></span>
                                                        <span class="text"><?=$c['name'];?></span> </li>

                                                <?php $i++; } ?>


                                            </ul></div>
                                    </div>

                                    <div class="tab-pane fade show" id="custom-tabs-3-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                                        <div class="pt-3" style=" width:100%;  overflow: auto;">

                                            <div class="row p-4">
                                                <button type="button" onclick="sort('a','.ui-sortable-series')" class="btn btn-success mr-3">Order A-Z</button>
                                                <button type="button" onclick="sort('z','.ui-sortable-series')" class="btn btn-success mr-3">Order Z-A</button>
                                            </div>

                                            <ul  class="todo-list ui-sortable-series connectedSortableSeries">

                                                <?php $i=0; while($c = $r_series->fetch_assoc()) {

                                                if($i > 14 ) { ?></ul><ul class="todo-list ui-sortable-series connectedSortableSeries"> <?php $i=0; } ?>

                                                <li class="liCat" > <input type="hidden" name="series_categories[]" value='<?=$c['id'];?>' /><span class='handle ui-sortable-handle'><i class='fa fa-ellipsis-v'></i></span><span class="text"><?=$c['name'];?></span> </li>

                                                <?php $i++; } ?>


                                            </ul></div>
                                    </div>

                                </div>


                            </div>

                            <div class="card-footer">
                                <button class="btn btn-success float-right" type="submit" >Save</button>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
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
<!-- JQuery UI -->
<script src="/plugins/jquery-ui/jquery-ui.min.js"></script>
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

    function sort(alph,sortable) {

        var sortableList = $(sortable);
        var listitems = $('li', sortableList);

        listitems.sort(function (a, b) {
            if(alph == 'z') return ($(a).text().toUpperCase() < $(b).text().toUpperCase())  ? 1 : -1;
            else return ($(a).text().toUpperCase() > $(b).text().toUpperCase()) ? 1 : -1;

        });
        //sortableList.append(listitems);
        var ul = 0; var i=1;
        $.each(listitems, function(idx, itm) {
            $(sortable+":eq("+ul+") ").append(itm);
            i++;
            if(i>14) {ul++; i = 0; }

        });

        return false;

    }


    $(document).ready(function () {

        $(".ui-sortable-streams").sortable({connectWith: ".connectedSortableStreams",cursorAt: { left: 50, top: 45 }, delay: 300,

            helper: function(e, item) {
                if (!item.hasClass('selected')) item.addClass('selected');
                var elements = $('.selected').not('.ui-sortable-placeholder').clone();
                var helper = $('<ul/>');
                item.siblings('.selected').addClass('hidden');
                return helper.append(elements);
            },
            start: function(e, ui) {
                var elements = ui.item.siblings('.selected.hidden').not('.ui-sortable-placeholder');
                ui.item.data('items', elements);
                var len = ui.helper.children().length;
                var height = ui.item.height() + 5;
                ui.helper.height((len * height))
                ui.placeholder.height((len * height))
            },
            receive: function(e, ui) {
                ui.item.before(ui.item.data('items'));
            },
            stop: function(e, ui) {
                ui.item.siblings('.selected').removeClass('hidden');
                $('.selected').removeClass('selected');
            }

        });

        $(".ui-sortable-movies").sortable({connectWith: ".connectedSortableMovies",cursorAt: { left: 50, top: 45 }, delay: 300,

            helper: function(e, item) {
                if (!item.hasClass('selected')) item.addClass('selected');
                var elements = $('.selected').not('.ui-sortable-placeholder').clone();
                var helper = $('<ul/>');
                item.siblings('.selected').addClass('hidden');
                return helper.append(elements);
            },
            start: function(e, ui) {
                var elements = ui.item.siblings('.selected.hidden').not('.ui-sortable-placeholder');
                ui.item.data('items', elements);
                var len = ui.helper.children().length;
                var height = ui.item.height() + 5;
                ui.helper.height((len * height))
                ui.placeholder.height((len * height))
            },
            receive: function(e, ui) {
                ui.item.before(ui.item.data('items'));
            },
            stop: function(e, ui) {
                ui.item.siblings('.selected').removeClass('hidden');
                $('.selected').removeClass('selected');
            }

        });

        $(".ui-sortable-series").sortable({connectWith: ".connectedSortableSeries",cursorAt: { left: 50, top: 45 }, delay: 300,

            helper: function(e, item) {
                if (!item.hasClass('selected')) item.addClass('selected');
                var elements = $('.selected').not('.ui-sortable-placeholder').clone();
                var helper = $('<ul/>');
                item.siblings('.selected').addClass('hidden');
                return helper.append(elements);
            },
            start: function(e, ui) {
                var elements = ui.item.siblings('.selected.hidden').not('.ui-sortable-placeholder');
                ui.item.data('items', elements);
                var len = ui.helper.children().length;
                var height = ui.item.height() + 5;
                ui.helper.height((len * height))
                ui.placeholder.height((len * height))
            },
            receive: function(e, ui) {
                ui.item.before(ui.item.data('items'));
            },
            stop: function(e, ui) {
                ui.item.siblings('.selected').removeClass('hidden');
                $('.selected').removeClass('selected');
            }

        });

    });

    $("#formCategoryOrder").submit(function(e){

        e.preventDefault();
        $.ajax({
            url: 'ajax/pCategory.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Saved");
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
