<?php
$EpgSource = new \ACES2\IPTV\EpgSource((int)$_GET['id']);
?>

<form id="formRemoveEpgSource">
    <input type="hidden" name="id" value="<?=$EpgSource->id?>" />
    <input type="hidden" name="action" value="remove_epg_source" />
    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.epg_source');?>" />
    <div class="modal-header">
        <h4 class="text-center">Remove Epg Source</h4>
    </div>

    <div class="modal-body">
        <h5>Are you sure you want to remove '<i><?=$EpgSource->name;?></i>' epg source?</h5>
    </div>

    <div class="modal-footer">
        <button type="button"  class="btn btn-default pull-right" data-dismiss="modal">Close</button>
        <button type="submit"  class="btn btn-danger">Remove</button>
    </div>
</form>
<script>
    $("#formRemoveEpgSource").submit(function(e){
        e.preventDefault();

        $.ajax({
            url: 'ajax/streams/pEpg.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Epg Source have been removed.");
                $(".cmodal").modal('hide');
                reloadTable();
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