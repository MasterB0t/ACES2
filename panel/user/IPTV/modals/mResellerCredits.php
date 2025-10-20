<?php

use ACES2\IPTV\Reseller2;

if (!$UserID=userIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}
$User = new Reseller2($UserID);
$Reseller = new Reseller2((int)$_REQUEST['reseller_id']);

?>
<form id="formModal">
    <input type="hidden" name="action" value="set_credits"/>
    <input type="hidden" name="reseller_id" value="<?=$Reseller->id?>"/>
    <div class="modal-header">
        <div class="modal-title">
             <h4>Set Reseller Credits</h4>
        </div>
    </div>

    <div class="modal-body">
        <div class="form-group">
            <label>Credits</label>
            <input type="number" class="form-control" name="credits" value="<?=$Reseller->getCredits();?>"/>
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
            url: 'ajax/pReseller.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                TABLE.ajax.reload();
                GetUserCredits();
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