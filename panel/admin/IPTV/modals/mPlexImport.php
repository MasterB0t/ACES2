<?php
$ADMIN = new \ACES2\ADMIN();

if (!$ADMIN->isLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$DB = new \ACES2\DB();

if(!$AccountID = (int)$_GET['account_id']) {
    setAjaxError("System Error"); die; }

$Watch = @$_GET['watch'] ? 1 : 0;

$r=$DB->query("SELECT id,host,port,plex_token FROM iptv_plex_accounts WHERE id = '$AccountID'");
if(!$plex_account = $r->fetch_assoc()){
    http_response_code(501); die;
}

$curl = curl_init("{$plex_account['host']}/library/sections/?X-Plex-Token={$plex_account['plex_token']}");
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'X-Plex-Token: '.$plex_account['plex_token'],
));
$resp = curl_exec($curl);
$plex_categories = json_decode($resp,1);
$errno = curl_errno($curl);
$curl_response_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

if($errno == 28)
    setAjaxError("Timeout Error. Make sure the plex server address and port are correctly.");
else if($curl_response_code == 401)
    setAjaxError("The plex token entered is invalid.");


$r_cats = $DB->query("SELECT id,name FROM iptv_stream_categories");

$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");

$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets");


$r2=$DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
$tmdb_api = $r2->fetch_assoc()['value'];
if($tmdb_api)
    $langs = json_decode(file_get_contents("https://api.themoviedb.org/3/configuration/languages?api_key=$tmdb_api"),1);

?>
<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">Add Movies/Series From  <?=$plex_account['host']?></h4>
    </div>
    <div class="modal-body">
        <form id="formModal2ImportPlex" role="form">
            <input type="hidden" name="action" value="start_import" />
            <input type="hidden" name="account_id" value="<?=$AccountID;?>" />
        </form>
        <div class="row">
            <div class="col-md-4">
                <label>Parallel Downloads</label>
                <input class="form-control" type="text" form="formModal2ImportPlex" name="parallel_downloads"
                       value="1">
            </div>
            <input type="hidden" form="formModal2ImportPlex" name="from_last_import" value="0">
            <div class="col-md-8"><label>Import only from Category</label>
                <select class="form-control" form="formModal2ImportPlex" name="import_from_category">
                    <option value="0">Import from all categories </option>
                    <?php foreach($plex_categories['MediaContainer']['Directory'] as $c ) { ?>
                        <option value="<?=$c['key'];?>"><?=$c['title'];?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <label> Add Categories </label>
        <select onchange="addCategory(this)" name="sub_category" form="formModal2ImportPlex" class="select2 form-control">
            <option value="">Select Categories </option>
            <option value="-1">Add Plex Category </option>
            <option value="-2">Add Genres as Category </option>
            <?php while($row=$r_cats->fetch_assoc()) { ?>
                <option value="<?=$row['id']?>"><?=$row['name'];?></option>
            <?php } ?>
        </select>
        <div class="list-categories"></div>
        <div class="form-group">
            <label>Transcoding</label>
            <select class="form-control" name="transcoding"form="formModal2ImportPlex">
                <option value="copy">Copy</option>
                <option value="h264:aac">H264 AAC</option>
                <option value="symlink">Redirect</option>
                <option value="stream">Stream</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" form="formModal2ImportPlex" name="import_movies" checked="">
                        Import Movies</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="checkbox">
                    <label><input type="checkbox" form="formModal2ImportPlex" name="import_series" checked="">
                        Import Series</label></div>
            </div>
        </div>

        <div class="form-group">
            <label>Select Server To import</label><select class="form-control select2" form="formModal2ImportPlex" name="server_id">
                <?php while($row=$r_servers->fetch_assoc()) { ?>
                    <option value="<?=$row['id']?>"><?=$row['name'];?></option>
                <?php } ?>
            </select></div>

        <div class="checkbox packageGroup">
            <h4 style="margin-top:30px; font-weight:bold;">Bouquets
                <span style="font-size:16px">
                    <a href="#!" onclick="toggleAllBouquets(true);">Check</a>/<a href="#!" onclick="toggleAllBouquets(false);">Un Check All.</a>
                </span>
            </h4><input form="formModal2ImportPlex" name="bouquets[]" value="" type="hidden">
            <?php while($row=$r_bouquets->fetch_assoc()) { ?>
                <label style="margin-left:10px; margin-bottom:10px">
                    <input form="formModal2ImportPlex"
                           name="bouquets[]" value="<?=$row['id']?>" type="checkbox"><?=$row['name']?></label>
            <?php } ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <?php if($Watch) { ?>
                <input type="hidden" form="formModal2ImportPlex" name="add_to_watch" value="1" />
                <button type="button" onclick="startImport()" class="btn btn-primary bnAddToWatch">Add To Watch</button>
            <?php } else {  ?>
                <button onclick="startImport()" class="btn btn-primary bnAddToWatch">Import Now </button>
            <?php } ?>
            </button>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $(".select2").select2({ width: '100%', dropdownParent: $(".cmodal") });
        $('#formModalAddXCAccount').unbind('submit').submit(); });


    function startImport() {

        //$(".bnAddToWatch").attr('disable',true);

        $.ajax({
            url: 'ajax/pPlex.php',
            type: 'post',
            dataType: 'json',
            data: $('#formModal2ImportPlex').serialize(),
            success: function (resp) {
                toastr.success("Success");
                $(".cmodal").modal('hide');

                <?php if($Watch) { ?>
                MODAL('modals/mWatchs.php?watch=1');
                <?php } ?>

            }, error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        if (typeof xhr.responseJSON == 'undefined') {
                            toastr.error("System Error");
                            return;
                        }

                        var response = xhr.responseJSON;
                        if (response.error)
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");
                }

            }
        });



        return false;

    }



    function addCategory(obj) {

        txt = $("select[name='sub_category']>option:selected").text();
        val = $("select[name='sub_category']>option:selected").val();


        if( $("input[value='"+val+"'].otherCats").length == 0 )
            $(".list-categories").append("<span class='label label-success'><input type='hidden' " +
                "form='formModal2ImportPlex' class='otherCats' name='categories[]' value='"+val+"'/>"+txt+"" +
                "<a href='#!' onclick='removeCatXC(this)'>X</a></span>");

        $("select[name='sub_category']").val(0);
        $("select[name='sub_category']").select2();


    }

    function removeCatXC(obj) {
        $(obj).parent('span').fadeOut('slow', function() { $(this).remove(); })
    }

    $("select[name='tmdb_lang']").val('en');
    $(".select2").select2();



</script>