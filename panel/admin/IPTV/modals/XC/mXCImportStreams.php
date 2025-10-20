<?php

use ACES2\IPTV\XCAPI\XCAccount;

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

$AccountID = (int)$_GET['account_id'];
if(!$AccountID) { setAjaxError(); }

$Watch= 0;
if(isset($_GET['watch'])) $Watch = 1;

$r=$DB->query("SELECT id,host,username,password,port FROM iptv_xc_videos_imp WHERE id = '$AccountID'");
$xc_account = $r->fetch_assoc();

$XCAccount = new XCAccount($AccountID);
$url = $XCAccount->url;

//$url = str_replace("/", "", $xc_account['host']);
//
//if(strpos($url, "https:") !== false  ) {
//    $url = str_replace("https:", "", $url);
//    $url = "https://$url"; }
//else if(strpos($url, "http:") !== false  ) {
//    $url = str_replace("https:", "", $url);
//    $url = "https://$url";
//} else {
//    $url = "http://".$url;
//}



$api_resp = json_decode(file_get_contents("$url/player_api.php?username={$xc_account['username']}&password={$xc_account['password']}"),true);
$vod_cats = json_decode(file_get_contents("$url/player_api.php?username={$xc_account['username']}&password={$xc_account['password']}&action=get_live_categories"),true);


if($api_resp['user_info']['status'] != 'Active')
    setAjaxError("XC Account is Expired ");

$xc_categories=[];
foreach($vod_cats as $i => $c) $xc_categories[] = $c;


$r_cats = $DB->query("SELECT id,name FROM iptv_stream_categories");

$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");

$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets");

$r_stream_profiles = $DB->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0");

$r2=$DB->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
$tmdb_api = $r2->fetch_assoc()['value'];
if($tmdb_api)
    $langs = json_decode(file_get_contents("https://api.themoviedb.org/3/configuration/languages?api_key=$tmdb_api"),1);

?>

<form id="formModalXCImport" role="form"></form>
<input type="hidden" form="formModalXCImport" name="xc_account_id" value="<?=$AccountID?>">
<input type="hidden" name="token" form="formModalXCImport"
       value="<?=\ACES2\Armor\Armor::createToken('iptv.import_xc');?>" />

<div class="modal-header">
    <h4 class="modal-title">Add Stream From  <?=$xc_account['host']?></h4>
</div>

<div class="modal-body">

    <div class="form-group">
        <label>Import from Category</label>
        <select class="form-control select2" form="formModalXCImport" name="import_from_category">
            <option value="0">Import from all categories </option>
            <?php foreach($xc_categories as $i => $c ) { ?>
                <option value="<?=$c['category_id'];?>"><?=$c['category_name'];?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label> Add To Category </label>
        <select required  name="category_id" form="formModalXCImport" class="form-control select2">
            <option value="">Select Category</option>
            <option value="-1">[Add XtreamCodes Category]</option>
            <?php while($row=$r_cats->fetch_assoc()) { ?>
                <option value="<?=$row['id']?>"><?=$row['name'];?></option>
            <?php } ?>
        </select>
    </div>


    <div class="form-group">
        <label>Stream Profile </label>
        <select class="form-control" name="stream_profile" form="formModalXCImport">
            <?php while($row=$r_stream_profiles->fetch_assoc()) { ?>
                <option value="<?=$row['id']?>"><?=$row['name'];?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>On Demand </label>
        <select class="form-control" name="ondemand" form="formModalXCImport">
            <option selected value="">Do Not Set On Demand</option>
            <option value="1">Set On demand</option>
        </select>
    </div>

    <div class="form-group">
        <label>Stream It</label>
        <select class="form-control" name="stream" form="formModalXCImport">
            <option selected value="1">Stream</option>
            <option value="0">Do Not Stream.</option>
        </select>
    </div>

    <div class="form-group">
        <label>Stream Server</label><select class="form-control select2" form="formModalXCImport" name="server_id">
            <?php while($row=$r_servers->fetch_assoc()) { ?>
                <option value="<?=$row['id']?>"><?=$row['name'];?></option>
            <?php } ?>
        </select></div>

    <div class="form-group">
        <label>Protocol</label>
        <select class="form-control select2" form="formModalXCImport" name="protocol">
            <option value="ts">TS</option>
            <option value="m3u8">M3U8 HLS</option>
        </select>
    </div>

    <h4 style=" font-weight:bold;">Bouquets
        <span style='font-size:16px'>
                                <a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",true);'>Check</a>/<a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",false);'>Un Check All.</a></span></h4>

    <input type="hidden" name='bouquets[]' value='' />
    <?php  while($opt = $r_bouquets->fetch_assoc() ) { ?>

        <label style='margin-left:10px; margin-bottom:10px'>
            <input form="formModalXCImport" name="bouquets[]" value="<?=$opt['id'];?>"  type="checkbox"> <?=$opt['name']; ?>
        </label>

    <?php } ?>


</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    <button type="button" onclick="startImport()" class="btn btn-primary btnSubmit">Import Now</button>
</div>
<script type="text/javascript">



    function startImport() {

        $(".btnSubmit").attr('disable',true);

        $.ajax({
            url: 'ajax/streams/pXCImport.php',
            type: 'post',
            dataType: 'json',
            data: $('#formModalXCImport').serialize(),
            success: function (resp) {
                toastr.success("Importing.");
                $(".cmodal").modal('hide');

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

        return false;

    }


    $(document).ready(function(){
        $(".select2").select2();
    });




</script>