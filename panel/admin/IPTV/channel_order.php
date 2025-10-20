<?php
$ADMIN = new \ACES2\ADMIN ();
if (!adminIsLogged()) {
    Redirect("/admin/login.php");
    die;
} else if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    redirect("/admin/profile.php");
}

$PageTitle = "Channel Order";

$DB = new \ACES2\DB();

$CHANNELS = array();
$r_chans=$DB->query("SELECT s.id,s.name,c.name as category,c.id as category_id, s.number  FROM iptv_channels s
    LEFT JOIN iptv_stream_categories c ON c.id = s.category_id ORDER BY s.ordering  ");

$TOTAL_CHANS = $r_chans->num_rows;

$CATS = array();
$r_cats=$DB->query("SELECT c.name,c.id FROM iptv_stream_categories c 
    RIGHT JOIN iptv_channels s   ON c.id = s.category_id GROUP BY c.id ");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?> - <?=$PageTitle?> </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Jquery UI -->
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

        ul.todo-list {  width:100%; overflow-y:auto; }
        ul.todo-list .selected { border-left:solid 5px #367fa9 !important; }
        ul.todo-list li input { height:27px; }
        
        .todo-list {  padding-left:25px;  }

        .buttons { margin-top:20px;  }
        .cat-button { margin:5px 5px; }

        .div-content {
            display: flex;
            flex-flow: column;
            height: 80vh;
        }

        .div-content .fill {
            flex: 1 1 auto;
            overflow-y:auto;
        }

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
                        <h1><?=$PageTitle;?></h1>
                        <ol class="breadcrumb float-sm-left">
                            <li class="breadcrumb-item active"><a href="streams.php">Streams</a></li>
                            <li class="breadcrumb-item active"><?=$PageTitle;?></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button form="formChannelOrder" type="submit" class="btn btn-sm btn-primary float-right btnSubmit">Save Order</button>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div id="ProgressBox" style="display: none;" class="info-box bg-info">
                            <span class="info-box-icon"><i class="fa fa-refresh"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">Channel Order In Progress</span>
                                <span class="info-box-number"></span>

                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                    </div>
                </div>

                <form id="formChannelOrder">

                    <input type="hidden" name="action" value="order_channels" />
                    <input type="hidden" name="token"
                           value="<?=\ACES2\Armor\Armor::createToken('iptv.channel_order')?>" />


                    <div class="row">

                        <div class="col-lg-6 col-xl-4">
                            <div class="row div-content">

                                <h4>With selected streams :</h4>
                                <div class=" buttons">

                                    <button class="btn btn-default" type="button" onclick="sortAZ()">Sort a-z </button>
                                    <button class="btn btn-default " type="button" onclick="moveDown()">Move Down</button>
                                    <button class="btn btn-default " type="button" onclick="moveUp()">Move Up</button>

                                </div>


                                <h4 class="pt-5"> Select/Deselect channels by category.</h4>
                                <div class="buttons fill">

                                    <?php while($cat=$r_cats->fetch_assoc()) {

                                        echo "<button class='btn btn-success cat-button' type='button'
                                            onclick='selectCat({$cat['id']})'>{$cat['name']}</button>";
                                    } ?>

                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-xl-8">
                            <h4>Stream Order</h4>
                            <div class="div-content">
                                <div class="fill">
                                    <ul class="todo-list ui-sortable connectedSortable">

                                        <?php
                                        $i=0;$n=0;
                                        while($c=$r_chans->fetch_assoc()) { $n++; ?>

                                            <li class="ui-state-default cat<?=$c['category_id'];?>" >
                                                <input type="hidden" name="channels[]" value='<?=$c['id'];?>' />
                                                <span class='handle ui-sortable-handle'><i class='fa fa-ellipsis-v'></i></span>
                                                <span class="text">
                                            <input style="width:150px; display:inline; margin-right: 5px;"
                                                   class="form-control" type="text" name="number[]"
                                                   value="<?=$c['number'];?>" /> <?="{$c['name']} [{$c['category']}] ";?>

                                            </span>
                                            </li>

                                        <?php $i++; } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>


                </form>
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
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- JQuery UI -->
<!--<script src="/plugins/jquery-ui/jquery-ui.min.js"></script>-->
<script src="/plugins/jquery-ui-1.14.1/jquery-ui.min.js"></script>
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

    function selectCat(id) {

        if( $("li.selected.cat"+id).length ) {
            $(".cat"+id).removeClass('selected');
        } else {
            //$(".ui-sortable li").removeClass('selected');
            $(".cat"+id).addClass('selected');
        }

    }

    function moveDown() {

        if($(".ui-sortable li.selected").length < 1)
            return;

        var sortableList = $('.ui-sortable');
        var listitems = $('li.selected', sortableList);

        inx =  $(".ui-sortable li.selected:last").index() + 1;
        last_e = $(".ui-sortable li").eq( inx )

        var htm = '';
        $.each(listitems, function(idx, itm) {
            htm += $(itm).prop('outerHTML')
            $(itm).remove();
        });

        $(last_e).after(htm);

        // $(".ui-sortable").animate({
        //     scrollTop: $($(".ui-sortable li.selected:last")).offset().top
        // }, 2000);

        $(".ui-sortable li.selected:first").get(0).scrollIntoView({behavior: 'smooth'});

    }

    function moveUp() {

        if($(".ui-sortable li.selected").length < 1)
            return;

        var sortableList = $('.ui-sortable');
        var listitems = $('li.selected', sortableList);

        inx =  $(".ui-sortable li.selected:first").index() - 1;
        last_e = $(".ui-sortable li").eq( inx )

        var htm = '';
        $.each(listitems, function(idx, itm) {
            htm += $(itm).prop('outerHTML')
            $(itm).remove();
        });

        $(last_e).before(htm);

        $(".ui-sortable li.selected:last").get(0).scrollIntoView({behavior: 'smooth', 'block': 'end'});

    }

    function sortAZ(alph) {

        var sortableList = $('.ui-sortable');
        var listitems = $('li.selected', sortableList);

        listitems.sort(function (a, b) {
            if(alph == 'z') return ($(a).text().toUpperCase() > $(b).text().toUpperCase())  ? 1 : -1;
            else return ($(a).text().toUpperCase() < $(b).text().toUpperCase())  ? 1 : -1;

        });
        //sortableList.append(listitems);

        var ul = $('li.selected:first').parent('.ui-sortable:eq(0)').index();
        var i= $('li.selected:first').index();


        $.each(listitems, function(idx, itm) {
            $("li.selected:first ").before(itm);
            i++;
            //if(i>14) {ul++; i = 0; }
        });

    }

    var isRunning = false;
    function getProgress() {

        $.ajax({
            url: 'ajax/streams/pChannelOrder.php',
            type: 'post',
            dataType: 'json',
            data: {'action': 'get_progress' },
            success: function (resp) {
                data = resp.data;

                if(data.is_running == true ) {
                    isRunning = true;
                    $("#ProgressBox").show();
                    $("#formChannelOrder button[type='submit']").prop('disabled', true);
                    $("#ProgressBox").find(".progress-bar").css({'width': data.progress+'%'});

                } else if(isRunning) {
                    $("#ProgressBox").hide();
                    $("#formChannelOrder button[type='submit']").prop('disabled', false);
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

    $("#formChannelOrder").submit(function(e) {
        e.preventDefault();
        $("#formChannelOrder button[type='submit']").prop('disabled', true);

        $.ajax({
            url: 'ajax/streams/pChannelOrder.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

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

        getProgress();
        setInterval(getProgress, 5000);

        $(document).on('click','li', function(){

            $(this).toggleClass("selected");

        });


        <?php  if($r_chans->num_rows < 1001 ){ ?>

        $(".ui-sortable").sortable({connectWith: ".connectedSortable", revert:0, delay: 300,

            //items: "li",

            helper: function (e, item) {
                //Basically, if you grab an unhighlighted item to drag, it will deselect (unhighlight) everything else
                if (!item.hasClass('selected')) {
                    item.addClass('selected').siblings().removeClass('selected');
                }

                //////////////////////////////////////////////////////////////////////
                //HERE'S HOW TO PASS THE SELECTED ITEMS TO THE `stop()` FUNCTION:

                //Clone the selected items into an array
                var elements = item.parent().children('.selected').clone();

                //Add a property to `item` called 'multidrag` that contains the
                //  selected items, then remove the selected items from the source list
                item.data('multidrag', elements).siblings('.selected').remove();

                //Now the selected items exist in memory, attached to the `item`,
                //  so we can access them later when we get to the `stop()` callback

                //Create the helper
                var helper = $('<li/>');
                return helper.append(elements);
            },
            stop: function (e, ui) {
                //Now we access those items that we stored in `item`s data!
                var elements = ui.item.data('multidrag');

                //`elements` now contains the originally selected items from the source list (the dragged items)!!

                //Finally I insert the selected items after the `item`, then remove the `item`, since
                //  item is a duplicate of one of the selected items.
                ui.item.after(elements).remove();
            }

        });

        <?php } ?>

        $(".select2").select2();

    });
</script>