<?php

$Admin = new \ACES2\Admin();

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$ids = explode(',',$_GET['ids']);

$db = new \ACES2\DB;
$r_servers = $db->query("SELECT id,name FROM iptv_servers ");
$Servers = $r_servers->fetch_all(MYSQLI_ASSOC);

$selected_main = 0;
$LoadBalances = [];

if(count($ids) == 1 ) {
    $Stream = new \ACES2\IPTV\Stream((int)$ids[0]);

    $LBS = [];
    foreach($Stream->load_balances as $lb) {

        try {
            $SrvDest = new \ACES2\IPTV\Server($lb['destination']);
            $destination = array('id' => $SrvDest->id , 'name' => $SrvDest->name);
            if($lb['source'] == 0 ) {
                $source = array('id' => 0 , 'name' => 'Stream Source');
            } else {
                $SrvSource = new \ACES2\IPTV\Server($lb['source']);
                $source = array('id' => $SrvSource->id , 'name' => $SrvSource->name);
            }

            $LBS[] = array('source' => $source , 'destination' => $destination );
        } catch(\Exception $e) {
            $ignore = 1;
        }
    }

    $MainServer = new \ACES2\IPTV\Server($Stream->server_id);
    $selected_main = $Stream->server_id;
}

$d = 1;
?>
<form id="formModal">
    <input type="hidden" name="stream_ids" value="<?=$_GET['ids']?>" />
    <input type="hidden" name="action" value="stream_set_load_balance"/>
    <div class="modal-header">
        <div class="modal-title">
            <h5>Manage Load Balance</h5>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>Main Server</label>
            <select name="main_server" form="formModal" class="form-control select2">
                <option value="">Select One</option>
                <?php foreach ($Servers as $server) {
                    $selected = $selected_main == $server['id'] ? 'selected' : '';
                    ?>
                    <option <?=$selected?> value="<?= $server['id'] ?>"><?= $server['name'] ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group-bs">
            <label for="swtNoClient">No Client on Main Server</label>
            <input id="swtNoClient" type="checkbox" class="bs-switch" name="no_client_main_server"
               data-on-text="Yes"
               data-off-text="No Clients"
            />
        </div>

        <div class="divLbs pt-3">
            <?php if(count($ids) == 1 ) {
                foreach($LBS as $lb) { ?>
                    <div class='row pb-1'>
                        <input type='hidden' name='lb_source[]' form='formModal' value='<?=$lb['source']['id']?>' />
                        <input name='lb_destination[]' type='hidden' form='formModal'  value='<?=$lb['destination']['id']?>' />
                        <div class=col-5>
                            <input type='text' class='form-control' readonly value='<?=$lb['source']['name']?>' />
                        </div>
                        <div class="col-5">
                            <input type='text' class='form-control' readonly value='<?=$lb['destination']['name']?>' />
                        </div>
                        <div class="col-2">
                            <button type='button' onClick="removeLb(this,'<?=$lb['destination']['id']?>', '<?=$lb['destination']['name']?>')"
                                class='btn btn-danger form-control'>Remove</button>
                        </div>
                    </div>
                    <?php
                }
            } ?>
        </div>

        <div class="row">
            <div class="form-group col-5">
                <label for="selectSource">Source Server</label>
                <select id="selectSource" class="form-control" form="formNull" name='source_server'>
                    <?php if(count($ids) == 1 ) {
                        ?>
                        <option value="<?=$MainServer->id?>"><?=$MainServer->name?></option>
                        <?php foreach($LBS as $lb ) {
                            ?>
                                <option value="<?=$lb['destination']['id']?>"><?=$lb['destination']['name']?></option>
                    <?php } }?>
                </select>
            </div>
            <div class="form-group col-5">
                <label for="selectDestination">Destination Server</label>
                <select id="selectDestination" class="form-control" form="formNull" name="destination_server">
                </select>
            </div>

            <div class="form-group col-2">
                <label>Actions</label>
                <button type=button onclick='addLb()' class='btn btn-success form-control'>Add</button>
            </div>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    var Servers = JSON.parse('<?=json_encode($Servers)?>');


    function resetLbs() {

        var opt = document.createElement('option');
        opt.value = 0;
        opt.innerHTML = 'Stream Source';
        document.getElementById('selectSource').appendChild(opt);

        $("#selectDestination").html('');

        for(let i = 0; i < Servers.length; i++ ) {

            server = Servers[i];

            var opt = document.createElement('option');
            opt.value = server.id;
            opt.innerHTML = server.name;
            document.getElementById('selectDestination').appendChild(opt);
        }

        return ;

    }

    function removeLb(element,destVal,destTxt) {
        $(element).parent().parent().remove();

        var opt = document.createElement('option');
        opt.value = destVal;
        opt.innerHTML = destTxt;
        document.getElementById('selectDestination').appendChild(opt);

        selectSource = document.getElementById('selectSource')
        for (let i = 0; i < selectSource.options.length; i++) {
            if (selectSource.options[i].value == destVal ) {
                selectSource.remove(i);
                break;
            }
        }

    }


    function addLb() {

        selectSource = document.getElementById('selectSource');
        selectDestination = document.getElementById('selectDestination');

        sourceVal = $("#selectSource").val();
        destinationVal = $("#selectDestination").val()
        sourceTxt = $('#selectSource').find(":selected").text()
        destinationTxt = $('#selectDestination').find(":selected").text()

        if($("select[name='main_server']").val() === '' ) {
            toastr.error("Select main server.")
            return
        }

        if(destinationVal == null )
            return ;

        $(".divLbs").append(
            "<div class='row pb-1'>" +
                "<input type='hidden' name='lb_source[]' form='formModal' value='"+sourceVal+"' />" +
                "<input name='lb_destination[]' type='hidden' form='formModal'  value='"+destinationVal+"' />" +
                "<div class=col-5>" +
                    "<input type='text' class='form-control' readonly value='"+sourceTxt+"' />" +
                "</div>" +
                "<div class=col-5>" +
                    "<input type='text' class='form-control' readonly value='"+destinationTxt+"' />" +
                "</div>" +
                "<div class='col-2'>" +
                    "<button type='button' onClick=\"removeLb(this,"+destinationVal+", '"+destinationTxt+"' )\" " +
                        "class='btn btn-danger form-control'>Remove</button>" +
                "</div>" +
            "</div>"
        );

        var opt = document.createElement('option');
        opt.value = destinationVal;
        opt.innerHTML = destinationTxt;
        selectSource.appendChild(opt)

        for (let i = 0; i < selectDestination.options.length; i++) {
            if (selectDestination.options[i].value === destinationVal ) {
                selectDestination.remove(i);
                break;
            }
        }

    }

    $("#formModal").submit(function (e) {

        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            processData: false,
            contentType: false,
            data: new FormData( document.getElementById('formModal') ),
            success: function (resp) {
                toastr.success("Success");
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

    $(document).ready(function(){

        $(".bs-switch").bootstrapSwitch();
        resetLbs();

        $("select[name='main_server']").change(function(){

            resetLbs();
            $('.divLbs').html('');

            selectSource = document.getElementById('selectSource')
            selectDestination = document.getElementById('selectDestination')

            for(var i = selectSource.options.length - 1; i >= 0; i--) {
                selectSource.remove(i);
            }

            var opt = document.createElement('option');
            opt.value = 0;
            opt.innerHTML = 'Stream Source';
            selectSource.appendChild(opt);

            var opt = document.createElement('option');
            opt.value = this.value;
            opt.innerHTML = $(this).find(":selected").text();
            selectSource.appendChild(opt);

            for (let i = 0; i < selectDestination.options.length; i++) {
                if (selectDestination.options[i].value === $(this).val() ) {
                    selectDestination.remove(i);
                    break;
                }
            }

        });

        <?php if(count($ids) == 1 ) {
            foreach($LBS as $lb ) { ?>
                $("#selectDestination option[value=<?=$lb['destination']['id']?>]").remove();
        <?php } ?>
                $("#selectDestination option[value=<?=$MainServer->id?>]").remove();
        <?php }?>



    });

</script>