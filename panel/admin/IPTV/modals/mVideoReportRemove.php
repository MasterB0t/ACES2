<?php

if (!$AdminID=adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

$ids = $_REQUEST['ids'];

$Admin = new \ACES2\Admin($AdminID);

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}
?>
<form id="formModal">
    <input type="hidden" name="action" value="remove_video_report"/>
    <input type="hidden" name="ids" value="<?=$_REQUEST['ids']?>"/>
    <div class="modal-header">
        <div class="modal-title">
            <h5>Remove Reports</h5>
        </div>
    </div>


    <div class="modal-body">
        <div class="form-group-bs">
            <label>Remove VOD from panel as well</label>
            <input type="checkbox" class="bootstrap-switch" name="remove_vod"
                   data-on-text="Yes"
                   data-off-text="No"
            />
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
                setTimeout(reloadTable, 800);
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
        $(".bootstrap-switch").bootstrapSwitch();
    })

</script>