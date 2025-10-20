<?php
$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

if (!is_file('/home/aces/guide/guide.xml')) {
    header("Error-Msg: Build Epg first.");
    setAjaxError("Build Epg first.");
    die;
}

$DB = new \ACES2\DB();
$Stream = new \ACES2\IPTV\Stream($_GET['stream_id']);
$r_servers = $DB->query("SELECT id,name FROM iptv_servers");

$r_rc = $DB->query("SELECT * FROM iptv_recording WHERE chan_id = $Stream->id AND status = 3 ");

$r_epg = $DB->query("SELECT start_time,end_time,title,description FROM iptv_epg 
                                             WHERE chan_id = '$Stream->id' AND NOW() < end_date GROUP BY start_date");

?>
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title align-center">Catchup <?=$Stream->name;?></h4>
    </div>
    <div class="modal-body">

        <form id='formCatchup'>
            <input type="hidden" name="action" value="set_catchup"/>
            <input type="hidden" name="channel_id" value="<?= $Stream->id; ?>"/>

            <div class="form-group"><label> Store Recording On Server </label>
                <select class="form-control select2" name="catchup_server">
                    <?php while ($s = $r_servers->fetch_assoc()) {
                        echo "<option value='{$s['id']}'>{$s['name']}</option>";
                    } ?>
                </select></div>

            <div class="form-group"><label>Keep recordings for</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="catchup_expire_days" value="<?=$Stream->catchup_exp_days;?>">
                    <div class="input-group-addon">DAYS</div>
                </div>
            </div>

            <div class="form-group"><label><input
                            onclick="enableCatchup(this)" name="enable_catchup" type="checkbox"> Save all programme</label></div>

            <div style="overflow:auto;height:300px;">
                <ul class="todo-list">
                    <?php

                    while($record = $r_rc->fetch_assoc()) {

                        ?>

                        <li class="bg-success">
                            <span class="text"><?= "{$record['title']} - {$record['start_time']}"; ?></span>
                            <div class='tools'><i style='color:white;' onclick='removeCatchUp($(this).parent().parent(),<?=$record['id'];?>)' class='fa fa-trash'></i></div>
                        </li>

                    <?php }

                    while ($e = $r_epg->fetch_assoc()) {
                        $date = date("D d, H:i", ($e['start_time']));
                        $rr=$DB->query("SELECT * FROM iptv_recording 
                            WHERE chan_id = $Stream->id AND start_time = FROM_UNIXTIME({$e['start_time']}) AND status != 4 ");
                        $s = '';$lc = '';$c = ''; $checked_disabled = ' disabled ';

                        if($record = $rr->fetch_assoc()) {

                            $tools = "<i style='color:white;'
                                   onclick='removeCatchUp($(this).parent().parent(),{$record['id']})'
                                   class='fa fa-trash'></i>";

                            $c = 'checked';
                            if ($record['status'] == 0 || $record['status'] == 1) {
                                $s = '<b>[SCHEDULE]</b>';
                                $lc = 'bg-warning';

                            } else if ($record['status'] == 2) {
                                $s = '<b>[RECORDING]</b>';
                                $lc = 'bg-danger';

                            } else if ($record['status'] == 3) {
                                $s = '<b>[ARCHIVED]</b>';
                                $lc = 'bg-danger';
                            } else if($record['status'] == 4) {
                                $c = '';
                                $checked_disabled = '';
                            }

                        } else {
                            $tools = '';
                            $checked_disabled = '';
                        }
                        ?>
                        <li class="<?=$lc;?>">
                            <input <?=$checked_disabled?> class="i-record" type="checkbox" <?=$c;?> name="record[]" value="<?=$e['start_time'];?>">
                            <input class="start_time" type="hidden" name="record_start[]"
                                   value="<?=$e['start_time'];?>">
                            <input type="hidden" name="record_end[]" value="<?= $e['start_time']; ?>">
                            <span class="text "><?= "{$e['title']} - {$date}"; ?></span>
                            <div  class='tools'><?=$tools;?></div>
                        </li>
                    <?php } ?>
            </div>

        </form>



    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        <button onclick='submitModal()' type="button" class="btn btn-primary btnSubmitModal">Submit</button>
    </div>

</div>
<script>

    function removeCatchUp(obj,id) {

        $("#formCatchup").append("<input type='hidden' name='remove_catchup[]' value='" + id + "'/> ");
        $(obj).remove();
    }

    function enableCatchup() {

        obj = $("input[name='enable_catchup']");

        if($(obj).is(':checked') ){

            $(".i-record").attr('checked',false);
            $('.i-record').each(function () { this.checked = !this.checked; });
            $(".i-record").attr('disabled',true);


        } else {
            $(".i-record").attr('disabled',false);
            $('.i-record').each(function () { this.checked = !this.checked; });
        }
    }



    function submitModal() {

        $(".btnSubmitModal").prop('disabled', true);

        $.ajax({

            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: $("#formCatchup").serialize(),
            success: function (data) {
                if (data) {
                    toastr.success('Complete');
                }
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

    }

    $(document).ready(function () {

        <?php
        if($Stream->catchup_server) echo "$(\"select[name='catchup_server']\").val('$Stream->catchup_server');";
            if($Stream->catchup) echo "$(\"input[name='enable_catchup']\").prop('checked',true);";

        ?>
        //enableCatchup();
        $(".select2").select2();

    });

</script>