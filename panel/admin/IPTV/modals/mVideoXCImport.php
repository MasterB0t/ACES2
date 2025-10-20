<?php

$Admin = new \ACES2\Admin();

$EditID = 0;
$Watch = (bool)$_GET['Watch'];

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$XC = new \ACES2\IPTV\XCAPI\XCAccount($_GET['account_id']);
$free_cons =   $XC->getMaxConnections() - $XC->getActiveConnections();
$xc_categories = array_merge($XC->getVodCategories(), $XC->getSeriesCategories());

$DB = new \ACES2\DB();

$r_cats = $DB->query("SELECT id,name FROM iptv_stream_categories");
$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");
$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets");


$r2=$DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
$tmdb_api = $r2->fetch_assoc()['value'];
if($tmdb_api)
    $langs = json_decode(file_get_contents("https://api.themoviedb.org/3/configuration/languages?api_key=$tmdb_api"),1);

?>
<form id="formModal">
    <input type="hidden" name="action" value="import_vods" />
    <input type="hidden" name="id" value="<?=$XC->id?>" />
    <div class="modal-header">
        <div class="modal-title">
            <?= $Watch != 0 ? '<h4 class="text-center">Import From XC API </h4>'
                : '<h4 class="text-center"> Add XC Watch </h4>' ?>
        </div>
    </div>

    <div class="modal-body">
        <div class="row">
            <div class="col-md-4">
                <label>Parallel Downloads</label>
                <input class="form-control" type="text" 
                       name="parallel_downloads" value="<?=$free_cons;?>">
            </div>
            <!--            <div class="col-md-4"><label>Category</label><select class="form-control"  name="category">-->
            <!--                    <option value="0">Same as XtreamCodes Host </option>-->
            <!--                    --><?php //foreach($xc_categories as $i => $c ) { ?>
            <!--                        <option value="--><?//=$c['category_id'];?><!--">--><?//=$c['category_name'];?><!--</option>-->
            <!--                    --><?php //} ?>
            <!--                </select>-->
            <!--            </div>-->
            <input type="hidden"  name="from_last_import" value="0">
            <div class="col-md-8">
                <div class="form-group">
                    <label>Import only from Category</label>
                    <select class="form-control select2"  name="import_from_category">
                        <option value="0">Import from all categories </option>
                        <?php foreach($xc_categories as $i => $c ) { ?>
                            <option value="<?=$c['category_id'];?>"><?=$c['category_name'];?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label> Add Categories </label>
            <select onchange="addCategoryXC(this)" name="sub_category"  class="form-control select2">
                <option value="">Select Categories </option>
                <option value="-1">Add XtreamCodes Category </option>
                <?php while($row=$r_cats->fetch_assoc()) { ?>
                    <option value="<?=$row['id']?>"><?=$row['name'];?></option>
                <?php } ?>
            </select>
        </div>

        <div class="list-categories"></div>
        <div class="form-group">
            <label>Transcoding</label>
            <select class="form-control" name="transcoding">
                <option value="copy">Copy</option>
                <option value="h264:aac">H264 AAC</option>
                <option value="redirect">Redirect</option>
                <option value="stream">Stream</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox"  name="import_movies" checked="">
                        Import Movies</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="checkbox">
                    <label><input type="checkbox"  name="import_series" checked="">
                        Import Series</label></div>
            </div>

            <?php if(!$Watch) { ?>
                <div class="col-md-4">
                    <div class="checkbox" >
                        <label><input type="checkbox"  name="force_import"
                                      title="Will add even if movie/series is already added.">
                            Force Import All <b>[WARNING]</b></label>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="form-group">
            <labe>Get Vod Info From</labe>
            <select class="form-control" name="get_info_from"  >
                <option value="tmdb">The Movie Database</option>
                <option value="provider">Provider</option>
            </select>
        </div>

        <div class="form-group">
            <labe>TMDB Lang</labe>
            <select class="form-control select2" name="tmdb_lang"  >
                <?php foreach($langs as $l ) { echo "<option value='{$l['iso_639_1']}'>{$l['english_name']}</option>"; } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Select Server To import</label><select class="form-control select2"  name="server_id">
                <?php while($row=$r_servers->fetch_assoc()) { ?>
                    <option value="<?=$row['id']?>"><?=$row['name'];?></option>
                <?php } ?>
            </select></div>

        <div class="checkbox">
            <label><input type="checkbox"  name="do_not_download_images" 
                    <?php if($do_not_download_images) echo 'checked'; ?>
                /> Do not download images. Use Image from source host or TMDB server.</label>
        </div>

        <div class="checkbox packageGroup">
            <h4 style="margin-top:30px; font-weight:bold;">Bouquets
                <span style="font-size:16px">
                    <a href="#!" onclick="toggleAllBouquets(true);">Check</a>/<a href="#!" onclick="toggleAllBouquets(false);">Un Check All.</a>
                </span>
            </h4><input  name="bouquets[]" value="" type="hidden">
            <?php while($row=$r_bouquets->fetch_assoc()) { ?>
                <label style="margin-left:10px; margin-bottom:10px"> <input  name="bouquets[]" value="<?=$row['id']?>" type="checkbox"><?=$row['name']?></label>
            <?php } ?>
        </div>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php if($Watch) { ?>
            <input type="hidden"  name="add_xc_to_watch" value="<?=$XC->id?>">
            <button type="button" onclick="startImport()" class="btn btn-primary bnAddToWatch">Add To Watch</button>
        <?php } else {  ?>
            <input type="hidden"  name="start_xc_import" value="<?=$XC->id?>">
            <button class="btn btn-primary bnAddToWatch">Import Now </button>
        <?php } ?>
        </button>
    </div>

</form>

<script>

    function addCategoryXC(obj) {

        txt = $("select[name='sub_category']>option:selected").text();
        val = $("select[name='sub_category']>option:selected").val();


        if( $("input[value='"+val+"'].otherCats").length == 0 )
            $(".list-categories").append("<span class='badge badge-success'><input type='hidden' form='formModal' " +
                "class='otherCats' name='categories[]' value='"+val+"'/>"+txt+"<a href='#!' onclick='removeCatXC(this)'>X</a></span>");

        $("select[name='sub_category']").val(0);
        $("select[name='sub_category']").select2();


    }

    function removeCatXC(obj) {
        $(obj).parent('span').fadeOut('slow', function() { $(this).remove(); })
    }

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pXC.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                //setTimeout(reloadTable, 1000);
                $(".cmodal").modal('hide');
            }, error: function (xhr) {
                $("#formModal button[type='submit']").prop("disabled", false);
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

    $(document).ready(function() {
        $("select[name='tmdb_lang']").val('en');
        $(".select2").select2()
    })



</script>