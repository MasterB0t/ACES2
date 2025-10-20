<?php


$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$Account = new ACES2\Iptv\Account((int)$_GET['account_id']);

$HOST = $_SERVER['HTTP_HOST'];
$PROTO = isset($_SERVER['HTTPS']) ? "https://" : "http://";

?>
<style>
    .boxinfo {
        width: 100%;
        height: 350px;
        border: solid 1px;
        padding:10px;
        border-radius: 4px;
        font-size:16px;
    }
</style>
<form id="formModal">
    <input type="hidden" name="action" value=""/>
    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">Account Info <i><?=$Account->name;?></i> </h4>
        </div>
    </div>

    <div class="modal-body">

        <div id="account-info" class="boxinfo" >
Account ID : <?=$Account->id;?><br/>
Username : <?=$Account->username;?><br/>
Password : <?=$Account->password;?><br/>
Playlist Link <br/>
   MPEGTS LINK : <?=HOST."/streams/{$Account->username}/{$Account->password}"?> <br/>
   HLS : <?=HOST."/streams/hls/{$Account->username}/{$Account->username}"?> <br/><br/>

EPG/GUIDE Links<br/>
  GZ : <?=HOST."/guide/{$Account->username}/{$Account->password}/guide.gz"?><br/>
  XML : <?=HOST."/guide/{$Account->username}/{$Account->password}/guide.xml"?><br/><br/>

<?php if($Account->mac_address != '') { ?>
    STB Portal : <?=HOST;?>/c <br/>
    MAC : <?=$Account->mac_address; ?><br/><br/>
<?php } ?>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button onclick="CopyToClipboard()" type="button" class="btn btn-primary">Copy</button>
    </div>

</form>

<script>

    function CopyToClipboard() {
        containerid = 'account-info';
        if (window.getSelection) {
            if (window.getSelection().empty) { // Chrome
                window.getSelection().empty();
            } else if (window.getSelection().removeAllRanges) { // Firefox
                window.getSelection().removeAllRanges();
            }
        } else if (document.selection) { // IE?
            document.selection.empty();
        }

        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText(document.getElementById(containerid));
            range.select().createTextRange();
            document.execCommand("copy");
        } else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode(document.getElementById(containerid));
            window.getSelection().addRange(range);
            document.execCommand("copy");
        }

        toastr.success("Copied to clipboard.")

    }


</script>