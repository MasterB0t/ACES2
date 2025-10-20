<?php
$ADMIN = new \ACES2\ADMIN ();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
} else if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

$DB = new \ACES2\DB;
$Stream =  new \ACES2\IPTV\Stream($_GET['channel_id']);
$StreamServer = new \ACES2\IPTV\SERVER($Stream->server_id);

$SERVERS=[];
$r_servers = $DB->query("SELECT id,name FROm iptv_servers WHERE id != $Stream->server_id ");
while($row = $r_servers->fetch_assoc()) $SERVERS[] = $row;

?>

    <form id="formManageLB"></form>

    <div class="modal-header">
        <h4 class="modal-title">Manage Load balances for <b><?=$Stream->name;?></b></h4>
    </div>
    <div class="modal-body">

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>


<script>


    function getLoadBalaceForm(channel_id) {

        var htm = "";

        $.ajax({
            type: 'POST',
            url: 'ajax/streams/pActions.php',
            data: { 'action': 'get_load_balances', 'channel_id': channel_id },
            dataType: 'json',
            success: function(data) {

                htm = "<div class='row'>";
                htm += "<div class='col-5'><div class='form-group'><label> Source Server</label><div class='lbs-sources'></div>";
                htm += "<select class='form-control lb-source' form='formManageLB' name='source_server'>";
                <?php if($Stream->type != $Stream::CHANNEL_TYPE_247 ) {
                    echo 'htm += "<option value=0>Stream Source</option>"; ';
                } ?>

                <?php echo "htm += '<option value=$StreamServer->id>$StreamServer->name</option>';"; ?>

                htm += "</select></div></div>";

                htm += "<div class='col-5'><div class='form-group'><label> Destination Server</label><div class='lbs-destinations'></div>";

                htm+=  "<select class='form-control lb-destination' form='formManageLB' name='destination_server'>";
                <?php foreach($SERVERS as $s) { echo "htm += '<option value={$s['id']}>{$s['name']}</option>';"; } ?>
                htm += "</select></div></div>";

                htm += "<div class='col-2'>";
                htm += "<div class='form-group'><label>Actions</label><div class='lbs-actions'></div>";

                htm += "<button onClick='addLB("+channel_id+")' class='btn btn-success form-control'>Add</button></div></div>";
                htm += "</div>";

                $(".cmodal").find(".modal-body").html(htm);

                for(i=0;i<data.data.length;i++) {

                    lb  = data.data[i];

                    //ADDING SOURCE OPTIONS
                    $("select.lb-source").append("<option value="+lb.destination+">"+lb.destination_name+"</option>");

                    //REMOVING DESTINATION OPTIONS
                    $("select.lb-destination").find("option[value="+lb.destination+"]").remove();

                    $(".lbs-sources").append("<input type='text' class='form-control inputLbSource lbs-{$d}' readonly value='"+lb.source_name+"' />");
                    $(".lbs-destinations").append("<input type='text' class='form-control inputLbSource lbs-{$d}' readonly  value='"+lb.destination_name+"' />");
                    $(".lbs-actions").append("<button onClick=\"removeLB("+channel_id+","+lb.destination+"); $(this).remove(); \" class='btn btn-danger form-control'>Remove</button>");

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
                            alert(response.error);
                        else
                            alert("System Error");

                }
            }


        });

        return htm;

    }

    function addLB(chan_id)  {

        if($(".lb-source").val() == $(".lb-destination").val() ) { alert("Source and destination servers can't be the same."); return false; }

        $.ajax({
            type: 'POST',
            url: 'ajax/streams/pActions.php',
            data: { 'action':'add_load_balance', 'channel_id': chan_id, 'source': $(".lb-source").val(), 'destination_server': $(".lb-destination").val()  } ,
            dataType: 'json',
            success: function(resp) {

                getLoadBalaceForm(chan_id);

            },  error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        var response = xhr.responseJSON;
                        if (typeof response != "undefined" && typeof response.error != "undefined")
                           alert(response.error);
                        else
                            alert("System Error");

                }

            }
        });

    }

    function removeLB(chan_id,dest) {

        $.ajax({
            async:false,
            type: 'POST',
            url: 'ajax/streams/pActions.php',
            data: { action : 'remove_load_balance', channel_id : chan_id, 'remove_destination': dest },
            dataType: 'json',
            success: function(resp) {

                getLoadBalaceForm(chan_id);

            }, error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        var response = xhr.responseJSON;
                        if (typeof response != "undefined" && typeof response.error != "undefined")
                            alert(response.error);
                        else
                            alert("System Error");

                }
            }

        });

    }

    <?php if($Stream->id) { ?>
        getLoadBalaceForm(<?=$Stream->id;?>);
    <?php } ?>


</script>