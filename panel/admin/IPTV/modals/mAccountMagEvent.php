<?php

$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}
?>
<form id="formModal">
    <input type="hidden" name="action" value="mag_event"/>
    <input type="hidden" name="account_id" value="<?=$_GET['id'];?>" />
    <div class="modal-header">
        <div class="modal-title">
            <h4 class="modal-title">Mag Event</h4>
        </div>
    </div>

    <div class="modal-body">
        <div class="form-group ">
            <label>Event</label>
            <select  name="event" class="form-control select2" >
                <option value="send_msg">Send Message</option>
                <option value="reload_portal">Reload Portal </option>
                <option value="reboot">Reboot Device </option>
                <option value="cut_off">Close Portal </option>
                <option value="reset_stb_lock">Reset STB Lock </option>
            </select>
        </div>

        <div class="form-group divMessage">
            <label>Message</label>
            <textarea  name="message" class="form-control"></textarea>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    $(document).on("change","#formModal select[name='event']",function(){

        if($("select[name='event']").val() == 'send_msg') {
            $(".divMessage").show();
        } else {
            $(".divMessage").hide();
        }

    })

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pAccount.php',
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
