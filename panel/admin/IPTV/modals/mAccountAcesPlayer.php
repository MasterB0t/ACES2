<?php

?>
<form id="formModal">
    <input type="hidden" name="action" value="connect_aces_player"/>
    <input type="hidden" name="account_id" value="<?=$_GET['account_id'];?>" />
    <div class="modal-header">
        <div class="modal-title">
            <h4>Connect Account With Aces Player APP</h4>
        </div>
    </div>

    <div class="modal-body">

        <h5>Enter the access code to login with this account into Aces Player APP.</h5>
        <div class="form-group">
            <input type="text" name="access_code" class="form-control" autocomplete="off"
                   onkeyup="this.value = this.value.toUpperCase();"
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
            url: 'ajax/pAccountAcesPlayer.php',
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