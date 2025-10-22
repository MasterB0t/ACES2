<?php


if (!$AdminID= adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

$Admin = new \ACES2\Admin($AdminID);
if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new \ACES2\DB;
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

?>
<form id="formModal">
    <input type="hidden" name="action" value="mass_watch_edit"/>
    <input type="hidden" name="ids" value="<?=$_GET['ids']?>"/>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Mass Watch Edit</h4>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>Set/Unset Status</label>
            <select name="enabled" class="form-control select2">
                <option value="">Keep Current Value</option>
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>


        <div class="form-group">
            <label>Set Interval</label>
            <select name="set_interval" onchange="toggleInterval(this.value)" class="form-control select2">
                <option value="">Keep Current Value</option>
                <option value="1">Set Interval</option>
            </select>
        </div>
        <div class="form-group pl-2 pr-2 ">
            <label>Interval</label>
            <input id="inpInterval" disabled class="form-control" name="interval_mins" value="60"/>
        </div>

        <div class="form-group">
            <label>Set Bouquets</label>
            <div class="form-group">
                <label></label>
                <select name="set_bouquets" onchange="setBouquets(this.value)" class="form-control select2">
                    <option value="">Keep current bouquets</option>
                    <option value="1">Set Bouquets</option>
                </select>
            </div>

            <h5 style="font-weight:bold;">
                <span style='font-size:16px'>
                    <a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/<a href="#!"
                                 onclick='toggleAllBouquets(false);'>Un Check All.</a>
                </span>
            </h5>

            <input  name="bouquets[]" value="" type="hidden">

            <?php
                while ($row = $r_bouquets->fetch_assoc()) { ?>

                    <div  class="icheck-primary d-inline ml-2">
                        <input disabled type="checkbox" value="<?=$row['id']?>"
                               name="bouquets[]" id="chkBouquet<?=$row['id']?>">
                        <label for="chkBouquet<?=$row['id']?>"><?=$row['name']?></label>
                    </div>

            <?php } ?>

        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    function toggleInterval(val) {

        var status = val != "1";
        $("#inpInterval").prop('disabled', status);

    }

    function setBouquets(val) {
        var status = val != "1";
        $("input[name=bouquets\\[\\]]").prop("disabled",status);
    }

    function toggleAllBouquets(s) {

        val = s==true ;
        $("input[name=bouquets\\[\\]]").prop("checked",val);

    }

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pVideoWatch.php',
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