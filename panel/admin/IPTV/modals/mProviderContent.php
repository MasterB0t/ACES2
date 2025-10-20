<?php

$Admin =  new \ACES2\Admin();

$EditID = 0;

if(!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if(!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$DB = new \ACES2\DB;
$r_cats = $DB->query("SELECT * FROM `iptv_stream_categories` ORDER BY name  ");
$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");
$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets ");
$do_not_download_images = true;
?>
<form id="formModal">
    <input type="hidden" name="action" value="add_provider_content" />
    <input type="hidden" name="ids" value="<?=$_GET['ids']?>" />

    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">Add Content From Provider</h4>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>VOD Info</label>
            <select name="get_info_from" class="select2 form-control" >
                <option value="provider">Provider</option>
                <option value="tmdb">TMDB</option>
            </select>
        </div>

        <div class="form-group">
            <label>Max Parallel Downloads</label>
            <input class="form-control" type="number" name="parallel_downloads" value="10" />
        </div>

        <div class="form-group">
            <label>Select Stream Server</label>
            <select name="server_id" aria-hidden="true" class="form-control select2">

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
                <option value="-2">[Category From Portal]</option>
                <option value="-1">[Add Genres as Categories]</option>
                <?php while($cat = $r_cats->fetch_assoc()) {
                    echo "<option value=\"{$cat['id']}\">{$cat['name']}</option>";
                } ?>
            </select>
            <div class="list-categories"></div>
        </div>

        <div class="form-group" >
            <label> Transcoding Option </label>
            <select name="transcoding" class="form-control">
                <option value="copy"> Copy Do not transocode file. </option>
                <option value="h264:aac"> H264 AAC </option>
                <option title='Create a symbolic link instead of copy or processing local files or redirect if is a web source.'
                        value="redirect"> Redirect </option>
                <option value="stream">Stream</option>
            </select>
        </div>

        <div class="pb-3 form-group-bs">
            <label>Do not download images. Use Image from source host or TMDB server.</label>
            <input type="checkbox" name="do_not_download_images" class="bootstrap-switch"
                <?php if($do_not_download_images) echo 'checked'; ?>
            />
        </div>

        <div class="checkbox packageGroup">

            <h4 style="margin-top:30px; font-weight:bold;">Bouquets
                <span style='font-size:16px'><a href="#!" onclick='ToggleBouquets(true);'>Check</a>
                    /<a href="#!" onclick='ToggleBouquets(false);'>Un Check All.</a></span></h4>

            <input  name="bouquets[]" value="" type="hidden">

            <?php

            while ($row = $r_bouquets->fetch_assoc()) { ?>

                <label style='margin-left:10px; margin-bottom:10px'>
                    <input name="bouquets[]" value="<?=$row['id'];?>" type="checkbox"> <?=$row['name']; ?>
                </label>

            <?php } ?>

        </div>



    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit"  class="btn btn-primary">Submit</button>
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

    $(document).ready(function() {
        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();
    });

    $("#formModal").submit(function(e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pProviderContent.php',
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

</script>