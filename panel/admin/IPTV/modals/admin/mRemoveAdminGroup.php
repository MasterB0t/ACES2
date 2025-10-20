<?php

$Admin = new \ACES2\Admin();

$EditID = (int)$_GET['id'];
$Group = new \ACES2\AdminGroup($EditID);

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission("")) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

?>
<form id="formModal">
    <input type="hidden" name="action" value="remove_admin_group"/>
    <input type="hidden" name="id" value="<?=$EditID?>" />
    <input type="hidden" name="token" value="<?= \ACES2\Armor\Armor::createToken('iptv.admin'); ?>"/>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Remove Admin Group</h4>
        </div>
    </div>


    <div class="modal-body">
        <p>Are you sure you want to remove <i><?=$Group->name?></i> admin group?</p>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Remove</button>
    </div>

</form>

<script>

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/admin/pAdmin.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Removed");
                setTimeout(reloadTable, 1500);
                $(".cmodal").modal('hide');
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