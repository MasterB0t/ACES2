<?php

$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new ACES2\DB;
$r_category = $db->query("SELECT * FROM iptv_stream_categories ORDER BY name ");
$r_servers = $db->query("SELECT id,name FROM iptv_servers ");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");


?>

<form id="formModal" enctype='multipart/form-data'>
    <input type="hidden" name="action" value=""/>
    <input type="hidden" name="token" value="<?= \ACES2\Armor\Armor::createToken('iptv.'); ?>"/>
    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">M3u Import </h4>
        </div>
    </div>

    <div class="modal-body">

        <ul class="nav nav-tabs nav-fill" id="custom-tabs-three-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active"
                   id="custom-tabs-1-tab" data-toggle="pill"
                   href="#custom-tabs-1-content" role="tab"
                   aria-controls="custom-tabs-1-home"
                   aria-selected="true">Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   id="custom-tabs-2-tab" data-toggle="pill"
                   href="#custom-tabs-2-content" role="tab"
                   aria-controls="custom-tabs-2-profile"
                   aria-selected="false">Bouquets</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="custom-tabs-three-tabContent">
            <div class="tab-pane fade show active" id="custom-tabs-1-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">

                <div class="form-group">
                    <label>Select Type</label>
                    <select name="type" aria-hidden="true"class="form-control select2">
                        <option value="movies"> Movies </option>
                        <option value="series"> Series </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Stream Server</label>
                    <select name="server_id" aria-hidden="true"class="form-control select2">

                        <?php
                        while($s = $r_servers->fetch_assoc()) {
                            echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                        }
                        ?>

                    </select>
                </div>

                <div class="form-group">
                    <label> Add Categories </label>
                    <select onchange="addCategory(this)" name="sub_category" class="form-control select2">
                        <option value="">Select Categories</option>
                        <option value="0">Category From Playlist</option>
                        <option value="-1">Category From TMDB</option>
                        <?php while($cat = $r_category->fetch_assoc()) {
                            echo "<option value=\"{$cat['id']}\">{$cat['name']}</option>";
                        } ?>
                    </select>
                    <div class="list-categories"></div>
                </div>

                <div class="form-group" >
                    <label> Transcoding Option </label>
                    <select name="transcoding" class="form-control">
                        <option value="copy"> COPY Do not transocode file. </option>
                        <option value="h264:aac"> H264 AAC </option>
                        <option title='Create a symbolic link instead of copy or proccesing local files or redirect if is a web source.' value="symlink"> Symlink / Redirect </option>
                    </select>
                </div>

                <div class="form-group" title="">
                    <label> Max Downloads </label>
                    <input class="form-control" name="max_download" type="number" value="1">
                </div>

                <div class="checkbox">
                    <label><input type="checkbox" name="force_import" value="1" title="Will add even if movie/series is already added." /> Force Import <b>[WARNING]</b></label>
                </div>

                <div class="form-group">
                    <label> Select m3u playlist file. </label>
                    <input id="inputFileM3u" name="m3u_file" type="file">
                </div>

            </div>

            <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">

                <div class="checkbox packageGroup">

                    <h4 style="margin-top:30px; font-weight:bold;">Bouquets <span style='font-size:16px'><a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/<a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

                    <input  name="bouquets[]" value="" type="hidden">

                    <?php

                    while ($row = $r_bouquets->fetch_assoc()) { ?>

                        <label style='margin-left:10px; margin-bottom:10px'> <input name="bouquets[]" value="<?=$row['id'];?>" type="checkbox"> <?=$row['name']; ?>
                        </label>

                    <?php } ?>

                </div>

            </div>


        </div>


    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    function addCategory(obj) {

        txt = $("select[name='sub_category']>option:selected").text();
        val = $("select[name='sub_category']>option:selected").val();


        if ($("input.massCategories[value='" + val + "']").length == 0)
            $(".list-categories").append("<span class='badge badge-success'><input class='massCategories' " +
                "type='hidden' name='categories[]' value='" + val + "'/>" + txt + "<a href='#!' onclick='removeCat(this)'>X</a></span>");

        $("select[name='sub_category']").val(0);
        $("select[name='sub_category']").select2();


    }

    function removeCat(obj) {
        $(obj).parent('span').fadeOut('slow', function () {
            $(this).remove();
        })
    }

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pVideoM3uUpload.php',
            type: 'post',
            dataType: 'json',
            data: new FormData(this),
            processData:false,
            contentType: false,
            success: function (resp) {
                toastr.success("Success");
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
        $(".select2").select2();
    })

</script>