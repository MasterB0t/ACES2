<?php

if (!$UserID=userIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

$USER = new \ACES2\IPTV\Reseller2($UserID);

$resellers = $USER->getResellers();
$sql = implode(",", $resellers);

$db = new \ACES2\DB();
$r=$db->query("SELECT id,name FROM users WHERE id in ($sql) ");
$Resellers = $r->fetch_all(MYSQLI_ASSOC);

?>
<form id="formModal">
    <input type="hidden" name="action" value="remove_resellers"/>
    <input type="hidden" name="reseller_ids" value="<?=$_GET['ids']?>'" />

    <div class="modal-header">
        <div class="modal-title">
            <h4>Remove Resellers</h4>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>Move Resellers to </label>
            <select name="move_reseller_to_user" class="form-control select2" >
                <option value="0">(YOU)</option>
                <?php foreach($Resellers as $o) { ?>
                    <option value="<?= $o['id'] ?>"><?= $o['name'] ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Move Account to </label>
            <select name="move_reseller_to_user" class="form-control select2" >
                <option value="0">(YOU)</option>
                <?php foreach($Resellers as $r) { ?>
                    <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                <?php } ?>
            </select>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Remove</button>
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

    $(document).ready(function() {
        $(".select2").select2();
    })

</script>