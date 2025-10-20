<?php

$Admin = new \ACES2\Admin();


if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$AccountID = (int)$_REQUEST['id'];
$Account = new \ACES2\IPTV\Account($AccountID);

?>
<form id="formModal">
    <div class="modal-header">
        <div class="modal-title">
            <h5>Download Playlist</h5>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <select onchange="updateLink(this.value)" class="select2 form-control">
                <option value="">Select Format</option>
                <option value="m3u-hls">M3U HLS</option>
                <option value="m3u-ts">M3U MPEGTS</option>
                <option value="m3u-ts-simple">M3U HLS No Info </option>
                <option value="m3u-ts-simple">M3U MPEGTS No Info</option>
                <option value="simple-list-hls">Simple List HLS</option>
                <option value="simple-list">Simple List MPEGTS</option>
                <option value="webtv-hls">WebTV HLS</option>
                <option value="webtv-mpegts">WebTV MPEGTS</option>
            </select>
        </div>


        <div class="input-group ">
            <input placeholder="Select format to get URL." id="playlist-link" readonly class="form-control">
            <span class="input-group-append">
                <button onclick="copy()" type="button" class="btn btn-info btn-flat">Copy</button>
            </span>
            <span class="input-group-append">
                <button onclick="download()" type="button" class="btn btn-success btn-flat">Download</button>
            </span>
        </div>


<!--        <div class="form-group">-->
<!--            <label>Playlist Link</label>-->
<!--            <div class="input-group">-->
<!--                <input id="playlist-link" readonly class="form-control">-->
<!--            </div>-->
<!--            <div class="input-group">-->
<!--                <div class="input-group-append">-->
<!--                    <span class="input-group-text">Upload</span>-->
<!--                </div>-->
<!--                <div class="input-group-append">-->
<!--                    <span class="input-group-text">Upload</span>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

    </div>


</form>

<script>

    var USERNAME = '<?=$Account->username?>';
    var PASSWORD = '<?=$Account->password?>';
    var HOST = '<?=HOST?>';

    function updateLink(val) {

        if(!val)
            $("#playlist-link").val('');
        else
            $("#playlist-link").val(HOST+"/playlist.php?username="+USERNAME+"&password="+PASSWORD+"&type="+val);

    }

    function download() {
        if(!$("#playlist-link").val())
            return;
        window.open($("#playlist-link").val(),'_blank');
    }

    function copy() {
        if(!$("#playlist-link").val())
            return;

        $("#playlist-link").select();
        document.execCommand('copy');
        toastr.success("Copied to clipboard.");
    }

    $(document).ready(function(){
        $(".select2").select2();
    })


</script>