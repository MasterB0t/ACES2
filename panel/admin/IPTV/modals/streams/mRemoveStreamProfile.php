<?php

use ACES2\IPTV\StreamProfile;

$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

$StreamProfile = new StreamProfile((int)$_GET['id']);

?>
<form id="formRemoveStreamProfile">
    <input type="hidden" name="id" value="<?=$StreamProfile->id?>" />
    <input type="hidden" name="action" value="remove_stream_profile" />
    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.stream_profile');?>" />
    <div class="modal-header">
        <h4 class="text-center">Remove Category</h4>
    </div>

    <div class="modal-body">
        <h5>Are you sure you want to remove '<i><?=$StreamProfile->name;?></i>' stream profile?</h5>
    </div>

    <div class="modal-footer">
        <button type="button"  class="btn btn-default pull-right" data-dismiss="modal">Close</button>
        <button type="submit"  class="btn btn-danger">Remove</button>
    </div>
</form>
<script>
    $("#formRemoveStreamProfile").submit(function(e){
        e.preventDefault();

        $.ajax({
            url: 'ajax/streams/pStreamProfile.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Stream profile have been removed.");
                $(".cmodal").modal('hide');
                reloadTable();
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