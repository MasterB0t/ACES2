<?php

$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}


?>
<form id="formModal">
    <input type="hidden" name="ids" value="<?=$_GET['ids']?>"/>
    <?php if(isset($_GET['remove_videos']))
        echo "<input type='hidden' name='action' value='remove_videos' />";
    else if(!empty($_GET['remove_episodes'])) {
        echo "<input type='hidden' name='action' value='remove_episodes' />";
    } ?>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Are you sure you want to remove selected videos?</h4>
        </div>

    </div>
    <div class="modal-body">
        <div class=checkbox><label>
                <input id="chkRemoveSource" type="checkbox" form="formModalMassRemove" name="remove_source_file"
                       value="1"/>
                Remove source file. </label>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Remove</button>
    </div>

</form>

<script>

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(reloadTable, 1200);
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