<?php

$ADMIN = new \ACES2\ADMIN();
if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
}

?>
<div class="modal-header">
    <h4 class="text-center">DNS REPLACE</h4>
</div>
<div class="modal-body">
    <div class="modal-body">
        <form method="post" id="formModalDnsReplacement">
            <input form="formModalDnsReplacement" type="hidden" name="token"
                   value="<?=\ACES2\Armor\Armor::createToken('iptv.stream_dns_replace');?>" />
            <input type="hidden" name="action" value="dns_replacement"/>
        </form>
        <div class="form-group">
            <label class="text-left">Enter the old DNS</label>
            <input class="form-control" form="formModalDnsReplacement" autocomplete="off" name="old_dns" type="text">
        </div>
        <div class="form-group">
            <label class="text-left">Enter the new DNS </label>
            <input class="form-control" form="formModalDnsReplacement" autocomplete="off" name="new_dns" type="text">
        </div>

        <div class="checkbox"><label> <input type="checkbox" name="confirm" value="1"/> Are you sure? </label></div>

    </div>
</div>
<div class="modal-footer">
    <button type="submit" form="formModalDnsReplacement" class="btn btn-primary pull-right ">UPDATE</button>
</div>
<script>
    $("#formModalDnsReplacement").submit(function () {

        if ($("input[name='new_dns']").val() == '' || $("input[name='old_dns']").val() == '') {
            toastr.error("Both dns are required.");
            return false
        }

        if (!$("input[name='confirm']").is(':checked')) {
            toastr.error("Check the confirmation before submit.");
            return false;
        }

        $("#formModalDnsReplacement button[type='submit']").prop("disabled", true);


        $.ajax({
            url: 'ajax/streams/pActions.php',
            type: 'post',
            dataType: 'json',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function (data) {

                if (data.not_logged) {
                    window.location.href = 'index.php';
                } else if (data.errors) {
                    alert(data.error);
                } else if (data.complete) {
                    toastr.success("DNS Will update now. You may need to restart channels in order to take effect.");
                    $(".cmodal").modal('hide');
                }

            },

            error: function (xhr, ajaxOptions, thrownError) {
                $("#formModalDnsReplacement button[type='submit']").prop("disabled", false);
                var response = xhr.responseJSON;
                switch (xhr.status) {
                    case 401:
                        window.location.href = 'login.php';
                        break;
                    default:
                        if (typeof response != "undefined" && response.error)
                            toastr.error(response.error)
                        else
                            toastr.error('System Error');
                }
            }

        });

        return false;

    });
</script>