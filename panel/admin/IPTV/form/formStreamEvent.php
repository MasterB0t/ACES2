<?php
if(!$AdminID=adminIsLogged()){
    Redirect("/admin/login.php");
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    Redirect("/admin/profile.php");
}


$Providers = [];
$db = new \ACES2\DB;


$TYPE_247 = \ACES2\IPTV\Stream::CHANNEL_TYPE_247;
$r_streams = $db->query("SELECT id,name FROM iptv_channels WHERE type != $TYPE_247  ");

$r_stream_profiles = $db->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0");
$r_stream_cats = $db->query("SELECT id,name FROM iptv_stream_categories ");
$r_server = $db->query("SELECT id,name FROM iptv_servers ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

$SelectedStreams = [];
$Providers = [];
$Event = new \ACES2\IPTV\StreamEvent(null);

if($EditID = (int)$_GET['id']) {

    $Event = new \ACES2\IPTV\StreamEvent($EditID);

    $sql_ids = count($Event->providers) > 0 ? implode(",",$Event->providers) : "''";
    $r1=$db->query("SELECT id,host, true as active FROM iptv_xc_videos_imp WHERE id IN ($sql_ids) ORDER BY FIELD(id,$sql_ids)");
    $r2=$db->query("SELECT id,host, false as active FROM iptv_xc_videos_imp WHERE id NOT IN ($sql_ids)");
    $Providers = array_merge($r1->fetch_all(MYSQLI_ASSOC), $r2->fetch_all(MYSQLI_ASSOC));
    $r_stream_selected = $db->query("SELECT id FROM iptv_channels WHERE event_id = '$EditID' ");
    while($row=$r_stream_selected->fetch_assoc()) {
        $SelectedStreams[] = $row['id'];
    }
} else {
    $r_providers=$db->query("SELECT id,host,false as active FROM iptv_xc_videos_imp ");
    $Providers = $r_providers->fetch_all(MYSQLI_ASSOC);
}

function recursiveFiles($path) {

    $extension[] = 'ttf';
    $extension[] = 'otf';

    $Files = [];

    if(is_dir($path)) {
        $scan = glob($path.'/*');
        foreach ($scan as $file) {
            if (is_dir($file) && $path) {
                $Files = array_merge ($Files, recursiveFiles($file));
            } else {
                foreach ($extension as $ext) {
                    if(str_contains($file, $ext))
                        $Files[] = $file;
                }
            }
        }
    }

    return $Files;
}

$d = 1;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME; ?>| Stream Events </title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- DataTables2 -->
    <link rel="stylesheet" href="/plugins/DataTables2/datatables.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
</head>
<style>
    .select2-container--default .select2-results > .select2-results__options {
        max-height: 300px; /* Adjust this value as needed */
        overflow-y: auto; /* Adds a scrollbar if content exceeds max-height */
    }
    .colorpicker { padding : .20rem .20rem;}
</style>

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
                        <h1>Stream Events</h1>
                        <ol class="breadcrumb pt-2">
                            <li class="breadcrumb-item "><a href="../stream_events.php">All Events</a></li>
                            <li class="breadcrumb-item active">Stream Events</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <button type="submit" form="form" class="btn btn-primary btn-sm float-right ml-3">Save</button>
                        <a href="../stream_events.php" class="btn btn-default btn-sm float-right">Go Back</a>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <form id="form">
                    <div class="row">

                        <input type="hidden" name="action" value="<?=$EditID ? 'update' : 'add';?>"/>
                        <?=$EditID ? "<input type='hidden' name='event_id' value='{$EditID}' />" : '';?>

                        <!-- MAIN INFO -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <div class="form-group">
                                        <label>Select Event Type</label>
                                        <select <?=$EditID ? 'readonly' : 'required' ?> name="event_type" class="form-control select2">
                                            <option value="">Select One</option>
                                            <option value="MLB">MLB</option>
                                            <option value="NFL">NFL</option>
                                            <option value="NBA">NBA</option>
                                        </select>
                                    </div>

                                    <div class="form-group-bs col-md-4">
                                        <label for="inpActive">Enabled</label>
                                        <input id=inpActive type="checkbox" class="bs-switch" name="active"
                                               <?=$Event->active ? 'checked' : ''; ?>
                                               data-on-text="Yes"
                                               data-off-text="No"
                                        />
                                    </div>

                                    <div class="form-group-bs col-md-4">
                                        <label for="chkHideStreams">Hide streams when there is no event</label>
                                        <input id=chkHideStreams type="checkbox" class="bs-switch" name="hide_streams_on_no_event"
                                               data-on-text="Yes"
                                               data-off-text="No"
                                               <?= $Event->hide_streams_on_no_event == true ? 'checked' : '';?>
                                        />
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12">
                            <!-- PROVIDERS -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Providers</h5>
                                </div>
                                <div class="card-body">

                                    <ul class="provider-list" >

                                        <?php foreach($Providers as $p) {
                                            $c = $p['active'] == 1 ? 'checked' : '';
                                            ?>
                                            <li>
                                                <!-- drag handle -->
                                                <span title="Hold click to drag for ordering" class="handle">
                                                  <i class="fas fa-lg fa-ellipsis-v"></i>
                                                  <i class="fas fa-lg fa-ellipsis-v"></i>
                                                </span>

                                                <div class="icheck-primary d-inline ml-2">
                                                    <input type="checkbox" value="<?=$p['id']?>"
                                                           name="providers[]"
                                                           <?=$c;?>
                                                           id="todoCheck<?=$p['id']?>">
                                                    <label for="todoCheck<?=$p['id']?>"></label>
                                                </div>

                                                <!--  text -->
                                                <span class="text"><?=$p['host']?></span>
                                            </li>

                                        <?php } ?>

                                    </ul>

                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <!-- STREAM -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Streams</h5>
                                </div>

                                <div class="card-body">

                                    <?php if($EditID) { ?>

                                        <div class="form-group ">
                                            <label>Select streams for the events.</label>
                                            <select style="height:600px;" name="streams[]" class="selectStreams form-control" multiple >
                                                <?php while($o=$r_streams->fetch_assoc()) {
                                                    $c = in_array($o['id'], $SelectedStreams) ? 'selected' : '';
                                                    ?>
                                                    <option <?=$c?>  value="<?=$o['id']?>"><?=$o['name']?></option>
                                                <?php } ?>
                                            </select>
                                        </div>


                                    <?php } else { ?>

                                        <h6>Add Streams to event.</h6>
                                        <div class="form-group">
                                            <div class="form-group ">
                                                <label>How many stream will be added to this event.</label>
                                                <input required type="number" class="form-control" name="add_streams" />
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-group ">
                                                <label>Logo Url</label>
                                                <input type="url" class="form-control" name="logo_url" />
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Select Category from the streams</label>
                                            <select required name="category" class="form-control select2">
                                                <option value="">Select One</option>
                                                <?php while($o = $r_stream_cats->fetch_assoc()) { ?>
                                                    <option value="<?=$o['id']?>"><?=$o['name'];?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Select Server from the streams</label>
                                            <select required name="server" class="form-control select2">
                                                <option value="">Select One</option>
                                                <?php while($o = $r_server->fetch_assoc()) { ?>
                                                    <option value="<?=$o['id']?>"><?=$o['name'];?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Select Stream Profile</label>
                                            <select required name="stream_profile" class="form-control select2">
                                                <option value="">Select One</option>
                                                <?php while($o = $r_stream_profiles->fetch_assoc()) { ?>
                                                    <option value="<?=$o['id']?>"><?=$o['name'];?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group-bs">
                                            <label for="chkOndemand">Set On-demand</label>
                                            <input id="chkOndemand" type="checkbox" class="bs-switch" name="ondemand"
                                               data-on-text="Yes"
                                               data-off-text="No"
                                            />
                                        </div>

                                        <div class="form-group-bs pt-2">
                                            <label for="chkStream">Stream</label>
                                            <input id="chkStream" type="checkbox" checked class="bs-switch" name="stream"
                                               data-on-text="Yes"
                                               data-off-text="No"
                                            />
                                        </div>


                                        <h4 style="font-weight:bold;"> Select Bouquets <span style='font-size:16px'>
                                            <a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",true);'>
                                                Check</a>/<a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",false);'>
                                                Un Check All.</a></span>
                                        </h4>

                                        <input type="hidden" name='bouquets[]' value='' />
                                        <?php  while($opt = $r_bouquets->fetch_assoc() ) { ?>

                                            <label style='margin-left:10px; margin-bottom:10px'>
                                                <input name="bouquets[]" value="<?=$opt['id'];?>"  type="checkbox"> <?=$opt['name']; ?>
                                            </label>

                                        <?php } ?>

                                    <?php } ?>

                                </div>
                            </div>
                        </div>

                        <!-- STREAM OPT -->
                        <div class="col-12">
                            <div class="card card-default">
                                <div class="card-header">
                                    <h5>Pre Stream Options</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group-bs col-md-6">
                                            <label for="preStream">Enable Pre/Post Stream</label>
                                            <input id="preStream" type="checkbox" class="bs-switch" name="pre_stream"
                                                   data-on-text="Yes"
                                                   data-off-text="No"
                                                   <?=$Event->getPreStream() ? 'checked' : ''; ?>
                                            />
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="form-group col-md-12 ">
                                            <label>Image URL</label>
                                            <input  class="form-control stream-opt" name="stream_image"
                                                    value="<?=$Event->stream_image?>" />
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Select Font </label>
                                            <select name="stream_font" class="form-control select2 stream-opt">
                                                <option value="">Select One</option>
                                                <?php foreach(recursiveFiles(ACES_ROOT. "/fonts") as $f) {
                                                    $exp = explode('/', $f);
                                                    $name = $exp[count($exp) - 1 ];?>
                                                    <option value="<?=$f;?>"><?=$name;?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-3">
                                            <label>Font Size</label>
                                            <input name="stream_font_size" class="form-control stream-opt"
                                                   value="<?=$Event->stream_font_size?>" type="number"/>
                                        </div>

                                        <!-- Color Picker -->
                                        <div class="form-group form-group-sm">
                                            <label>Font Color</label>
                                            <div class="input-group colorpicker input-group-sm">
                                                <input type="text" name="stream_font_color" class="form-control "
                                                       value="<?=$Event->stream_font_color?>" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="fas fa-square"></i></span>
                                                </div>
                                            </div>
                                            <!-- /.input group -->
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm float-right mb-3 ml-3 bntSubmit">Save</button>
                            <a href="../stream_events.php" class="btn btn-sm btn-default float-right mb-3">Go Back</a>
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
<!-- jQuery UI 1.11.4 -->
<script src="/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.js"></script>
<!-- DataTables2  & Plugins -->
<script src="/plugins/DataTables2/datatables.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- bootstrap color picker -->
<script src="/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/table.js"></script>
<script src="/dist/js/admin.js"></script>
<script>

    $(".stream-opt").attr('disabled', true);

    $("td.tdActive").click(function() {
        e = $(this).find(".bootstrap-switch");
        e.prop('checked', !e.prop('checked') ).trigger('change');
    });

    $(".provider-list li .text").click(function(e){
        e = $(this).parent().find("input");
        e.prop('checked', !e.prop('checked') ).trigger('change');
    });


    $("input[name='pre_stream']").on('switchChange.bootstrapSwitch', function (event, state) {
        if(state == true) {
            $(".stream-opt").attr('disabled', false);
            colorPicker.enable();
        }
        else
            $(".stream-opt").attr('disabled', true);
    });

    $("#form").submit(function(e) {

        e.preventDefault();
        $("button[type='submit']").prop('disabled', true);

        $.ajax({
            url: '../ajax/pStreamEvent.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {

                toastr.success("Saved")
                setTimeout( function() { window.location.href="../stream_events.php"; } , 800  );

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

        $('.colorpicker').colorpicker();

        $('.colorpicker').on('colorpickerChange', function(event) {
            $('.colorpicker .fa-square').css('color', event.color.toString());
        })

        $('.provider-list').sortable({
            placeholder: 'sort-highlight',
            handle: '.handle',
            forcePlaceholderSize: true,
            zIndex: 999999
        })

        <?php if($EditID) {

            if($Event->getPreStream()) { ?>

                $('.stream-opt').attr('disabled', false);

            <?php }

            ?>
            $("select[name='event_type']").val('<?=$Event->type?>');
            $("select[name='stream_font']").val('<?=$Event->stream_font?>');
            $(".colorpicker").trigger('change')
            $(".select2").select2();

        <?php } ?>

        $(".selectStreams").select2({
            closeOnSelect: false
        })

    });

</script>
</body>
</html>