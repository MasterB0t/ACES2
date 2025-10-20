<?php


$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission("")) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}
?>
<form id="formModalMaintenance">
    <input type="hidden" name="action" value="<?=$_REQUEST['action']?>" />
    <?php if(isset($_GET['older_than']))
        echo "<input type='hidden' name='older_than' value='{$_GET['older_than']}' />";
    ?>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Confirm</h4>
        </div>
    </div>

    <div class="modal-body">
        <h5>This action can't be undone. Are you sure you want to perform this action?</h5>
        <div class="pt-3 form-group">
            <label>Enter Your Password</label>
            <input required type="password" name="password" autocomplete="off" class="form-control" />
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Submit</button>
    </div>

</form>

<script>

    $("#formModalMaintenance").submit(function (e) {
        e.preventDefault();
        $("#formModalMaintenance button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pMaintenance.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                $(".cmodal").modal('hide');
            }, error: function (xhr) {
                $("#formModalMaintenance button[type='submit']").prop("disabled", false);
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