<?php

$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_ACCOUNTS)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$DB = new \ACES2\DB;

$r_users = $DB->query("SELECT id,name,email,username FROM users ORDER BY name ");
$r_pkgs = $DB->query("SELECT * FROM iptv_bouquet_packages ORDER BY name ");
$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets ORDER BY name ");
$ids = explode(",",$_REQUEST['ids']);
?>
<form id="formModal">
    <input type="hidden" name="action" value="mass_update_account"/>
    <input type="hidden" name="ids" value="<?=$_GET['ids']?>" />
    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">Mass Edit Account</h4>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>Account Status</label>
            <select name="status" class="form-control select2">
                <option value=""> Do not change status </option>
                <option value="1">Active</option>
                <option value="2">Disabled</option>
                <option value="3">Blocked</option>
            </select>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Account Adult Pin</label>
                    <select onchange="updateInputPin(this.value)" name="set_pin" class="form-control">
                        <option value="0">Do not change adult pin</option>
                        <option value="1">Set pin </option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label> Adult Pin </label>
                    <input maxlength="4" minlength="4" disabled class="form-control" name="pin" >
                </div>
            </div>
        </div>

        <div class="form-group"  >
            <label>Select Owner</label>
            <select name="owner_id" class="form-control select2">
                <option value="">Do not change owner.</option>
                <option value="0">Admins</option>
                <?php while($user = $r_users->fetch_assoc())
                    echo "<option value='{$user['id']}'> {$user['username']} {$user['email']} ";
                ?>
            </select>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Max connections</label>
                    <select onchange="updateInputConns(this.value)" name="set_limit_connections" class="form-control">
                        <option value="0">Do not change max connections</option>
                        <option value="1">Set max connection to </option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label> Max Connections  </label>
                    <input type="number" disabled class="form-control" name="limit_connections" >
                </div>
            </div>
        </div>

        <div class="form-group "  >
            <label>Bouquet Package</label>
            <select onChange="updatePackageBouquets(this.value);" name="bouquet_package" aria-hidden="true" class="form-control select2 ">
                <option value="">Do not change package or bouquets on this account.</option>
                <option value="0">No bouquet package. Select bouquets manually.</option>
                <?php while($row = $r_pkgs->fetch_assoc() ) { echo "<option value='{$row['id']}'>{$row['name']}</option>"; } ?>
            </select>
        </div>

        <div  class="checkbox packageGroup">

            <h4 style="margin-top:30px; font-weight:bold;">Bouquets
                <span style='font-size:16px'><a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/
                    <a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

            <input disabled name="bouquets[]" value="" type="hidden">

            <?php

            while ($row = $r_bouquets->fetch_assoc()) { ?>

                <label style='margin-left:10px; margin-bottom:10px'>
                    <input disabled name="bouquets[]" value="<?php echo $row['id'];?>"
                        type="checkbox"> <?php echo $row['name']; ?>
                </label>

            <?php } ?>

        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    function updatePackageBouquets(val) {

        $("#formModal input[name='bouquets[]']").prop('disabled',true);
        $("#formModal input[name='bouquets[]']").prop('checked',false);

        if(val == '') return false;

        if(val == 0 ) {
            $("#formModal input[name='bouquets[]']").prop('disabled',false);
            $("#formModal input[name='bouquets[]']").prop('checked',false);
            return true;
        }

        $.ajax({
            async:false,
            url: 'ajax/pPackage.php',
            type: 'post',
            dataType: 'json',
            data: { action : 'get_package', package_id : val },
            success: function(resp) {

                $.each(resp.data.bouquets, function(index, bouquet) {
                    id = bouquet['id'];
                    $("#formModal  input[name='bouquets[]'][value='"+id+"']").prop('disabled',true);

                    if(bouquet['enabled'] == 1 )
                        $("#formModal  input[name='bouquets[]'][value='"+id+"']").prop('checked',true);
                    else
                        $("#formModal  input[name='bouquets[]'][value='"+id+"']").prop('checked',false);

                });

            }

        });
    }

    function updateInputPin(v) {

        $("#formModal input[name='pin']").val('');
        $("#formModal input[name='pin']").prop('disabled', true);

        if(v == 1 ) {
            $("#formModal input[name='pin']").prop('disabled', false);
        }
    }

    function updateInputConns(v) {
        $("#formModal input[name='limit_connections']").val('0');
        $("#formModal input[name='limit_connections']").prop('disabled', true);

        if(v == 1 ) {
            $("#formModal input[name='limit_connections']").prop('disabled', false);
        }
    }

    $(document).ready(function() {
        $(".select2").select2();
    });

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
                setTimeout(reloadTable, 1000);
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