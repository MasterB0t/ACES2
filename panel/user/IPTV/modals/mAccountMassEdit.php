<?php

if(!$UserID=userIsLogged())
    setAjaxError("",401);

$User = new \ACES2\IPTV\User($UserID);
$Resellers = $User->getResellers();
$db = new \ACES2\DB;

$resellers = [];
if(count($Resellers)>0) {
    $sql = implode(",",$Resellers);
    $r=$db->query("SELECT id,username,name FROM users WHERE id in ( $sql ) ");
    $resellers = $r->fetch_all(MYSQLI_ASSOC);
}


?><form id="formModal">
    <input type="hidden" name="action" value="mass_account_edit"/>
    <input type="hidden" name="ids" value="<?=$_GET['ids'];?>" />
    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">Mass Account Edit</h4>
        </div>
    </div>

    <div class="modal-body">
        <div class="form-group">
            <label>Account Status</label>
            <select name="status" class="form-control select2">
                <option value="">Do not change status</option>
                <option value="1">Disabled Selected Accounts</option>
                <option value="2">Enable Selected Accounts</option>
            </select>
        </div>

        <div class="form-group">
            <label>Change Owner</label>
            <select name="owner" class="form-control select2">
                <option value="">Keep current owner</option>
                <option value="<?=$User->id?>">(YOU)</option>
                <?php foreach($resellers as $o) { ?>
                    <option value="<?=$o['id']?>"><?="{$o['name']} - {$o['username']}"?></option>
                <?php } ?>
            </select>

        </div>

        <div class="form-group">
            <label>Show Adult Content</label>
            <select name="adult_content" class="form-control select2">
                <option value="">Keep current value</option>
                <option value="1">Show adult content</option>
                <option value="2">Hide adult content</option>
            </select>
        </div>

        <div class="form-group">
            <label>Adult Require PIN</label>
            <select name="adult_with_pin" class="form-control select2">
                <option value="">Keep current value</option>
                <option value="1">Enable PIN for adult content</option>
                <option value="2">Disable PIN for adult content</option>
            </select>

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
            url: 'ajax/pAccount.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(TABLE_RELOAD(), 800);
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
        $(".select2").select2();
    })

</script>