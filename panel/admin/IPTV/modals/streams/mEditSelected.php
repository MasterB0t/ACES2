<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged()) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

$DB = new \ACES2\DB();

$r_cats=$DB->query("SELECT id,name FROM iptv_stream_categories ");

$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");

$r_stream_profile = $DB->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0 ");

$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets ");

$ids = explode(",",$_GET['ids']);

?>

<div class="modal-header">
    <h4 class="modal-title align-center">Mass Edit Streams/Channels</h4>
</div>
<div class="modal-body">
    <form id="formChannelMassEdit" method="post">
        <input type="hidden" name="action" value="mass_selected_edit" >
        <?php foreach($ids as $id ) {
            echo "<input type='hidden' name='ids[]' value='$id'>";
        }?>
        <div title="Set a server for all channel selected. Channels will be restarted if this is changed." class="form-group">
            <label> Stream Server</label>
            <select class="form-control select2"  name="stream_server" onchange="if(!confirm('Changing main server will remove all load balances for channels. You will need to reset it.'))
            { $(this).val('') return false; }">
                <option value="">Do not change stream Server</option>
                <?php while($s = $r_servers->fetch_assoc()) { echo "<option value={$s['id']}>{$s['name']} </option> " ;} ?>
            </select>
        </div>
        <div title="Set a categories to all channels selected." class="form-group">
            <label>Category</label>
            <select class="form-control select2 "  name="category"  aria-hidden="true">
                <option value="">Do not change category</option>
                <?php while($cat = $r_cats->fetch_assoc()) { echo "<option value={$cat['id']}>{$cat['name']} </option> " ;} ?>
            </select>
        </div>
        <div title="Select the stream profile for channels selected. Channels will be restarted if this is changed." class="form-group">
            <label>Stream Profile</label>
            <select class="form-control"  name="stream_profile">
                <option value="">Do not change stream profile.</option>
                <?php while($p = $r_stream_profile->fetch_assoc()) { echo "<option value={$p['id']}>{$p['name']} </option> " ;} ?>
            </select>
        </div>
        <div title="If channels are disabled will be hidden for users." class="form-group">
            <label>Channel Status</label>
            <select class="form-control"  name="channel_status">
                <option value="">Do not change channel status.</option>
                <option value="1"> Enable selected channels </option>
                <option value="0"> Disable selected channels </option>
            </select></div>

        <div title="" class="form-group">
            <label>Stream Channel</label>
            <select class="form-control"  name="stream_channel">
                <option value="">Do not change stream channel.</option>
                <option value="1"> Stream selected channels </option>
                <option value="0"> Do not stream selected channel redirect to source instead.</option>
            </select></div>

        <div title="If ondemand is set channel will be streamed only when there are client watching it." class="form-group">
            <label>Set or disable ondemand</label><select class="form-control"  name="channel_ondemand">
                <option value="">Do not change ondemand.</option><option value="1"> Set as ondemand the selected channels. </option>
                <option value="0"> Always stream the selected channels. </option>
            </select></div>

        <div title="" class="form-group"><label>Set channels to bouquets</label>
            <select onchange="if(this.value == 1) { $('.div-mass-bouquets').show(); $('input.mass-bouquets').prop('checked',false);} else $('.div-mass-bouquets').hide();  " class="form-control" name="channel_bouquets">
                <option value="">Do not change bouquets on selected channels.</option>
                <option value="1"> Set the bouquets below to selected channels </option>
            </select></div>


        <div class="form-group">
            <label>Set/Unset Auto Rename</label>
            <select class="form-control" name="sync_stream_name">
                <option value="">Keep current value</option>
                <option value="1">Enable Sync Name</option>
                <option value="0">Disable Sync Name</option>
            </select>
        </div>

        <div class="div-mass-bouquets" style="display:none;">
            <h4 style="margin-top:30px; font-weight:bold;">Bouquets <span style="font-size:16px">
                <a href="#!" onclick=" $('.mass-bouquets').prop('checked',true) ">Check</a>/
                <a href="#!" onclick="$('input.mass-bouquets').prop('checked',false)">Un Check All.</a>
            </span></h4>
            <?php while($b = $r_bouquets->fetch_assoc()) { ?>
                <label style="margin-left:10px; margin-bottom:10px">
                    <input class="mass-bouquets" name="bouquets[]" value="<?=$b['id'];?>" type="checkbox"><?=$b['name'];?></label>
            <?php } ?>
        </div>

    </form>
</div>


</form>



<div class="modal-footer">
    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
    <button onclick='submitModal()' type="button" class="btn btn-primary btnSubmitModal" >Submit</button>
</div>


<script>


    function submitModal() {

        $(".btnSubmitModal").prop('disabled',true);

        $.ajax({

            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: $("#formChannelMassEdit").serialize(),
            success: function (data) {
                toastr.success('Channels will be updated on background.');
                $(".cmodal").modal('hide')
            },error: function (xhr) {

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

    }

    $(document).ready(function(){

        $(".select2").select2();

    });

</script>