<?php

$EditID = 0;

if (!$AdminID=adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

$Admin = new \ACES2\Admin($AdminID);

if (!$Admin->hasPermission("")) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

if($EditID=(int)$_REQUEST['id']){
    $Firewall = new ACES2\Firewall($EditID);
}

?>
<form id="formModal">
    <?php if($EditID) { ?>
        <input type="hidden" name="rule_id" value="<?=$EditID?>" />
        <input type="hidden" name="action" value="update_rule"  />
    <?php } else { ?>
        <input type="hidden" name="action" value="add_rule" />
    <?php } ?>
    <div class="modal-header">
        <div class="modal-title">
            <?= $EditID != 0 ? '<h4 class="text-center">Edit </h4>'
                : '<h4 class="text-center">Add </h4>' ?>
        </div>
    </div>

    <div class="modal-body">

        <div class="row">
            <div class="form-group col-md-4">
                <label>Chain</label>
                <select class="form-control select2" name="chain" >
                    <option value="input" >INPUT</option>
                    <option value="output" >OUTPUT</option>
                </select>
            </div>

            <div class="form-group col-md-6">
                <label> IP-Address </label>
                <input class="form-control" name="ip_address" data-inputmask="'alias': 'ip'" data-mask=""
                    value="<?=$Firewall->ip_address?>"/>
            </div>

            <div class="form-group col-md-2">
                <label>Rule</label>
                <select name="rule" class="form-control select2">
                    <option value="REJECT"> Reject </option>
                    <option value="DROP"> Drop </option>
                    <option value="ACCEPT"> Accept </option>
                </select>
            </div>

        </div>

        <div class="row">
            <div class="form-group col-md-5">
                <label>Source Port</label>
                <input class="form-control" name="sport" />
            </div>
            <div class="form-group col-md-5">
                <label>Destination Port</label>
                <input class="form-control" name="dport" />
            </div>
            <div class="form-group col-md-2">
                <label>Protocol</label>
                <select name="protocol" class="form-control select2">
                    <option value="">Auto</option>
                    <option value="tcp">TCP</option>
                    <option value="udp">UDP</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Other Options</label>
            <textarea class="form-control" name="options"></textarea>
        </div>

        <div class="form-group">
            <label>Comments</label>
            <textarea class="form-control" name="comments"></textarea>
        </div>


    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add</button>
    </div>

</form>

<script>

    $(document).ready(function(){

        <?php if($EditID) { ?>

            $("#formModal select[name='rule']").val('<?=$Firewall->rule?>');


            $("#formModal input[name='dport']").val('<?=$Firewall->dport ? $Firewall->dport :  '' ?>');
            $("#formModal input[name='sport']").val('<?=$Firewall->sport ? $Firewall->sport :  '' ?>');
            $("#formModal input[name='protocol']").val('<?=$Firewall->protocol?>');
            $("#formModal textarea[name='options']").val('<?=$Firewall->options?>');
            $("#formModal textarea[name='comments']").val('<?=$Firewall->comments?>');



        <?php } ?>

    });

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pFirewall.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                TABLES.ajax.reload();
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