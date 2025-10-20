<?php


if (!$UserID=userIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

$EditID = (int)$_REQUEST['account_id'];
$Account = new \ACES2\IPTV\Account($EditID);

$User = new \ACES2\IPTV\User($UserID);
//$Resellers = $User->getResellers();
//$Resellers[] = $User->id;
//
//if(!in_array($Account->owner_id, $Resellers))
//    setAjaxError("Account not found.", 403);

$db = new \ACES2\DB;

$Packages = [];
$OverridePackage = $User->getOverridePackage();
//MAKE SURE PACKAGE STILL EXIST.
foreach($OverridePackage as $pack_id => $v ) {
    $r=$db->query("SELECT id,name FROM iptv_bouquet_packages WHERE id = '$pack_id' ");
    if($row=$r->fetch_assoc()) {
        $row['official_credits'] = $OverridePackage[$pack_id]['official_credits'];
        $Packages[] = $row;
    }
}


?>
<form id="formModal">
    <input type="hidden" name="action" value="update_account_package"/>
    <input type="hidden" name="account_id" value="<?=$EditID;?>"/>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Update Account Package</h4>
        </div>
    </div>

    <div class="modal-body">
        <div class="form-group">
            <label>Select Package</label>
            <select name="package_id" required class="select2 form-control">
                <option value="">Select One</option>
                <?php foreach($Packages as $package) { ?>
                    <option value="<?=$package['id']?>"><?=$package['name']?></option>
                <?php } ?>
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
                setTimeout(function() {
                    reloadTable();
                    GetUserCredits()
                }, 800);
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