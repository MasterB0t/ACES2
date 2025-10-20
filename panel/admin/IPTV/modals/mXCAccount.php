<?php

$Admin = new \ACES2\Admin();

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$EditID = (int)$_GET['account_id'] ?: null;


$XCAccount = new \ACES2\IPTV\XCAPI\XCAccount($EditID);


?>
<form id="formModal">
    <?php if($EditID) { ?>
        <input type="hidden" name="action" value="update_account"/>
        <input type="hidden" name="id" value="<?=$EditID?>"/>
    <?php } else {  ?>
        <input type="hidden" name="action" value="add_account"/>
    <?php } ?>
    <div class="modal-header">
        <div class="modal-title">
            <?= $EditID != 0 ? '<h4 class="text-center">Update XC Account </h4>'
                : '<h4 class="text-center">Add XC Account</h4>' ?>
        </div>
    </div>

    <div class="modal-body">

        <div class="row">
            <div class="col-12 col-md-8">
                <div class="form-group">
                    <label>Host</label>
                    <input type="url" name="host" class="form-control"  value="<?=$XCAccount->url?>" />
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" class="form-control" name="port" value="<?=$XCAccount->port?>" />
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?=$XCAccount->username?>"/>
                </div>

            </div>

            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control" placeholder="" />
                </div>

            </div>
        </div>

        <div class="form-group-bs">
            <label for="chkAutoUpdate">Auto update content daily</label>
            <input id="chkAutoUpdate" type="checkbox" class="bootstrap-switch" name="auto_update_content"
                   data-on-text="Yes"
                   data-off-text="No"
            />
        </div>



    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

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

        <?php if($EditID) { ?>
            $("#formModal input[name='password']").prop('placeholder', "Leave it blank for no change.");

            $("#formModal input[name='auto_update_content']").prop('checked', <?=$XCAccount->auto_update_content; ?>);

        <?php } ?>

        $(".bootstrap-switch").bootstrapSwitch();

    })

</script>