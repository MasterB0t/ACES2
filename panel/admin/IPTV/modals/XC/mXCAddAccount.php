<?php
//DEPRECATED
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
}

$ADMIN = new \ACES2\ADMIN ();
if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

$DB = new \ACES2\DB ();
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h4 class="modal-title">Add XtreamCode Account.</h4>
</div>


<div class="modal-body">
    <form id="formModalAddXCAccount" role="form"></form>
    <input type="hidden" form="formModalAddXCAccount" name="add_xc_account" value="1">
    <div class="row">
        <div class="col-xs-8"><label>Host</label><input class="form-control" type="text" form="formModalAddXCAccount" name="host"></div>
        <div class="col-xs-4"><label>Port</label><input class="form-control" type="text" form="formModalAddXCAccount" name="port"></div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-xs-6"><label>Username</label><input class="form-control" type="text" form="formModalAddXCAccount" name="username"></div>
        <div class="col-xs-6"><label>Password</label><input class="form-control" type="text" form="formModalAddXCAccount"  name="password"></div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    <button type="button" onclick=submitModal() form="formModalAddXCAccount" class="btn btn-primary  submitModalAddXCAccount">Add</button>
</div>

<script>

function submitModal() {

    $(".submitModalAddXCAccount").attr('disabled',true);

    $.ajax({
        url : 'ajax/videos/pXCAccounts.php',
        dataType: 'json',
        type : 'post',
        data : $("#formModalAddXCAccount").serialize(),
        success : function(resp){
            if(resp.status == 1 ) {
                toastr.success("Added..");
                $(".cmodal").modal('hide');
                <?php if($Watch) {  ?>
                    MODAL('modals/videos/mXCAccounts.php?watch=1');
                <?php } else {    ?>
                    MODAL('modals/videos/mXCAccounts.php?');
                <?php } ?>
            } else if(resp.error) {
                toastr.error(resp.error);
            } else toastr.error("System Error.");

            $(".submitModalAddXCAccount").attr('disabled',false);
        },
        error : function(x) {
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

    return false;

}

</script>