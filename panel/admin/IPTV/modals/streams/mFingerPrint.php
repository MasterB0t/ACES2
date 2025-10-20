<?php

$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

$channel_id = (int)$_GET['channel_id'];
$account_id = (int)$_GET['account_id'];

?>
<div class="modal-content">
    <div class="modal-header">
        <div class="modal-title"><h4>Print Message</h4></div>
    </div>
    <div class="modal-body">
        <form id="formModalChannelFingerPrint">
        </form>
        <input form="formModalChannelFingerPrint" type="hidden" name="token"
               value="<?=\ACES2\Armor\Armor::createToken('iptv.stream_fingerprint');?>" />
        <input form="formModalChannelFingerPrint" type="hidden" name="action" value="print_message">
        <?php if($channel_id == 0 ) { ?>
             <input form="formModalChannelFingerPrint" type="hidden"  name="account_id" value="<?=$account_id;?>">
        <?php } else { ?>
             <input form="formModalChannelFingerPrint" type="hidden"  name="channel_id" value="<?=$channel_id;?>">
        <?php } ?>
        <div class="form-group"><label>Print message or account id</label><select
                onchange="updateForm(this.value)" class="form-control" name="type" form="formModalChannelFingerPrint">
                <option value="id">Account ID</option>
                <option value="message">Custom Message</option>
            </select></div>
        <div class="form-group"><label>Position</label><select class="form-control" name="position" form="formModalChannelFingerPrint">
                <option value="center">Center</option>
                <option value="top_left">Top left</option>
                <option value="top_right">Top Right</option>
                <option value="bottom_left">Bottom left</option>
                <option value="bottom_right">Bottom Right</option>
            </select></div>
        <div class="row">
            <div class="form-group col-xs-12 col-sm-6"><label>Font</label><select class="form-control" name="font" form="formModalChannelFingerPrint">
                    <?php
                    $files=rglob('/home/aces/fonts/*');
                    foreach ($files as $file ) {
                        if(!is_dir($file)) {

                            $name = str_replace('/home/aces/fonts/', '', $file);
                            $pname = htmlspecialchars($name, ENT_QUOTES);
                            echo " htm += \"<option value='$pname'> $pname </option>\";  ";
                        }

                    }
                    ?>
                </select></div>
            <div class="form-group col-xs-12 col-sm-3"><label>Font Size</label><input type="number" class="form-control" name="font_size" value="36" form="formModalChannelFingerPrint"></div>
            <div class="form-group col-xs-12 col-sm-3"><label>Font Color</label><select class="form-control"name="font_color" form="formModalChannelFingerPrint">
                    <option value="white">White</option>
                    <option value="black">Black</option>
                    <option value="red">Red</option>
                    <option value="blue">Blue</option>
                </select></div>
        </div>
        <div class="form-group"><label>Message</label>
            <textarea form="formModalChannelFingerPrint" name="message" disabled="" id="textareFingerPrint"  class="form-control"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" id='btnSubmitModal' onclick="submitModalFingerPrint()" form="formModalChannelFingerPrint" class="btn btn-primary ">Send</button>
    </div>
</div>
<script>
    function updateForm(v) {
        if(v == 'id') { $("#textareFingerPrint").val(''); $("#textareFingerPrint").prop('disabled',true);}
        else {  $("#textareFingerPrint").prop('disabled',false); }
    }

    function submitModalFingerPrint() {

        $("#btnSubmitModal").prop("disabled",true);

        $.ajax({
            async:false,
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: $('#formModalChannelFingerPrint').serialize(),
            success: function(data) {

                $("#btnSubmitModal").prop("disabled",false);

                if(data.not_logged) { window.location.href='index.php'; }
                else if(data.error) { toastr.error(data.error); }
                else if(data.complete) { toastr.success('Message Sent.');$(".cmodal").modal('hide'); }

            }

        });

        return false;

    };

</script>