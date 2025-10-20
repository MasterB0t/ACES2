<?php

$Admin = new \ACES2\Admin(null);

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$sql = '';
if(isset($_GET['ids'])) {
    $action = 'mass_remove_resellers';
    $PageTitle = "selected resellers.";
    $sql = "WHERE id not in ({$_GET['ids']})";

} else {
    $action = 'remove_reseller';
    $Reseller = new \ACES2\IPTV\Reseller((int)$_GET['id']);
    $PageTitle = "reseller '$Reseller->username'";
    $sql = "WHERE id != '$Reseller->id'";
}


$db = new \ACES2\DB;
$r=$db->query("SELECT id,username FROM users $sql order by username ");
$users = $r->fetch_all();


?>
<form id="formModal">
    <input type="hidden" name="action" value="<?=$action?>"/>
    <?php if(isset($_GET['ids'])) {?>
        <input type="hidden" name="ids" value="<?=$_GET['ids']?>" />
    <?php } else {  ?>
        <input type="hidden" name="id" value="<?=$_GET['id']?>" />
    <?php } ?>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Remove Reseller.</h4>
        </div>
    </div>


    <div class="modal-body">

        <h5 class="pb-3">Are you sure you want to remove this reseller <?=$PageTitle;?> ?</h5>

        <div class="form-group">
            <label>Set Account to reseller :</label>
            <select class="form-control select2" name="set_accounts_to">
                <option value="0">[Set To Admin]</option>
                <option value="remove">[REMOVE ACCOUNTS]</option>
                <?php foreach ($users as $i => $user) { ?>
                    <option value="<?=$user[0]?>"><?=$user[1]?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Set Sub Resellers to reseller :</label>
            <select class="form-control select2" name="set_resellers_to">
                <option value="0">[Set To Admin]</option>
                <option value="remove">[REMOVE RESELLERS]</option>
                <?php foreach ($users as $i => $user) { ?>
                    <option value="<?=$user[0]?>"><?=$user[1]?></option>
                <?php } ?>
            </select>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Remove</button>
    </div>

</form>

<script>

    $(document).ready(function() {
        $(".select2").select2();
    })

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/pReseller.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Reseller Removed");
                setTimeout(reloadTable, 1000);
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