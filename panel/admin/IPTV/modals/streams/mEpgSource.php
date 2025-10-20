<?php

use ACES2\IPTV\EpgSource;

$enabled = 'checked';
if($EditID = (int)$_GET['id']){
    $EpgSource = new EpgSource($EditID);
    $enabled = $EpgSource->enabled ? 'checked' : '';
}

?>
<form id="formEpgSource">
    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.epg_source');?>" />
    <?php if($EditID) { ?>
        <input type="hidden" name="id" value="<?=$EditID?>" />
        <input type="hidden" name="action" value="update_epg_source" />
    <?php } else { ?>
        <input type="hidden" name="action" value="add_epg_source" />
    <?php } ?>

    <div class="modal-header">
        <h4>Epg Source</h4>
    </div>

    <div class="modal-body">
        <div class="form-group">
            <label>Epg Name</label>
            <input required name="name" class="form-control" />
        </div>

        <div class="form-group">
            <label>Epg URL</label>
            <input required name="url" class="form-control" />
        </div>



        <div class="form-group form-group-bs">
            <input type="hidden" name="enabled" value=""/>
            <label>Enabled</label>
            <input <?=$enabled; ?>
                    data-on-text="Yes"
                    data-off-text="No"
                    type="checkbox" name="enabled" class="bootstrap-switch" />
        </div>

    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-success btnSubmit" >Submit</button>
    </div>


</form>
<script>
    $("#formEpgSource").submit(function(e){

        $("#formEpgSource button[type='submit']").prop('disabled', true);


        e.preventDefault();
        $.ajax({
            url: 'ajax/streams/pEpg.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                $(".cmodal").modal('hide');
                setTimeout(reloadTable, 1200);
            }, error: function (xhr) {
                $("#formEpgSource button[type='submit']").prop('disabled', false);
                switch (xhr.status) {
                    case 401:
                    case 403:

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

        return false;
    });

    $(document).ready(function() {

        <?php if($EditID) { ?>
            $("input[name='name']").val('<?=$EpgSource->name?>');
            $("input[name='url']").val('<?=$EpgSource->url?>');
            <?php if($EpgSource->enabled) { ?>
                $("input[name='enabled']").prop('checked', true);
            <?php } ?>
        <?php } ?>

        $(".bootstrap-switch").bootstrapSwitch();
    });

</script>
