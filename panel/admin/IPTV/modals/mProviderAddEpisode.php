<?php
$Admin = new \ACES2\Admin();

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new \ACES2\DB;
$r=$db->query("SELECT id,name FROM iptv_ondemand WHERE type='series' ORDER BY name");
$r_servers = $db->query("SELECT id,name FROM iptv_servers  ");
//$r_cats = $db->query("SELECT * FROM `iptv_stream_categories` ORDER BY name  ");
//$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

?>
<form id="formModal">
    <input type="hidden" name="action" value=""/>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Add Episode(s)</h4>
        </div>
    </div>

    <div class="modal-body">

        <input type="hidden" name="action" value="add_episodes" />
        <input type="hidden" name="episodes" value="<?=$_GET['ids'];?>" />

        <div class="form-group">
            <label>Series to add to :</label>
            <select required name="series_id" class="form-control select2" >
                <option value="" >Select One</option>
                <?php while($o=$r->fetch_assoc()) { ?>
                    <option value="<?=$o['id']?>" ><?=$o['name']?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Server</label>
            <select name="server_id" class="form-control select2">
                <?php while($o=$r_servers->fetch_assoc()) { ?>
                    <option value="<?=$o['id']?>" ><?=$o['name']?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Transcoding</label>
            <select name="transcoding" class="form-control select2">
                <option selected value="copy"> COPY Do not transcode file. </option>
                <option value="h264:aac"> H264 AAC </option>
                <option title="Create a symbolic link instead of copy or processing local files or redirect if is a web source." value="redirect"> Symlink / Redirect </option>
                <option title="" value="stream"> Stream </option>
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

        <div class='form-group'>
            <label>Get info from </label>
            <select name='get_info_from' class='form-control'>
                <option value='provider'> Provider </option>
                <option value='tmdb'> TMDB </option>
            </select>
        </div>

        <div class="form-group-bs">
            <label>Force Add</label>
            <input type="checkbox" class="bootstrap-switch" name="force_add"
                   data-on-text="Yes"
                   data-off-text="No"
            />
            <p>Mark this to add episodes even if episode already exists in series </p>
        </div>

        <div class="form-group-bs">
            <label>Download logos</label>
            <input type="checkbox" class="bootstrap-switch" name="download_logos"
                   data-on-text="Yes"
                   data-off-text="No"
            />

        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add</button>
    </div>

</form>

<script>

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pProviderContent.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Episodes will be added on background");
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

    $(document).ready(function(){
        $(".select2").select2();
        $(".bootstrap-switch").bootstrapSwitch();
    })

</script>