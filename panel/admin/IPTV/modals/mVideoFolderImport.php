<?php

use \ACES2\IPTV\Settings;

$Admin = new \ACES2\Admin();

$EditID = 0;
$is_series = (bool)isset($_GET['series']);
$no_recursive = (bool)isset($_GET['no_recursive']);

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new ACES2\DB;

$r_category = $db->query("SELECT * FROM iptv_stream_categories ORDER BY name ");
$r_servers = $db->query("SELECT id,name FROM iptv_servers ");
$r_bouquets=$db->query("SELECT id,name FROM  iptv_bouquets ");

$r2=$db->query("SELECT value FROM settings WHERE name='iptv.videos.tmdb_api_v3' LIMIT 1 ");
$tmdb_api = $r2->fetch_assoc()['value'];


include DOC_ROOT . "/includes/languages.php";


$tmdb_lang = Settings::get( Settings::TMDB_LANGUAGE );
$do_not_download_images = Settings::get( Settings::VOD_DONT_DOWNLOAD_LOGOS );

?>
<form id="formModal">
    <input type="hidden" name="action" value="import_from_directory"/>
    <div class="modal-header">
        <div class="modal-title">
            <h4>Import From Folder</h4>
        </div>
    </div>
    <div class="modal-body">

        <ul class="nav nav-tabs nav-fill" id="custom-tabs-three-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active"
                   id="custom-tabs-1-tab" data-toggle="pill"
                   href="#custom-tabs-1-content" role="tab"
                   aria-controls="custom-tabs-1-home"
                   aria-selected="true">Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   id="custom-tabs-2-tab" data-toggle="pill"
                   href="#custom-tabs-2-content" role="tab"
                   aria-controls="custom-tabs-2-profile"
                   aria-selected="false">Bouquets</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="custom-tabs-three-tabContent">
            <div class="tab-pane fade show active" id="custom-tabs-1-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                <?php if($is_series) { ?>
                    <input type="hidden" name="series" value="1" />
                    <?php if($no_recursive) { ?>

                        <h4><b>This function will add a series from a folder on server. </b></h4>
                        <p>Select the folder of the series to be add, it must named as the series title, inside of it the episodes, episode could be on sub folders.</p>
                        <p>Each episode must contain the SXX and EXX on name. <br/><b>IE:</b></p>
                        <p>...EpisodeTitle_s01.e01.mp4</p>
                        <p>...S01E02.mp4</p>
                        <p>...Title-E03S01.mp4</p>
                        <br/><br/>

                    <?php } else { ?>

                        <input type="hidden" name="no_recursive" value="1" />

                        <h4><b>This function will add all the series inside a folder on server. </b></h4>
                        <p>Select the parent folder that contain all the series to be add. Inside that folder each series must be in a folder with named with the title of the series.</p>
                        <p>Each episode must contain the SXX and EXX on name. <br/><b>IE:</b></p>
                        <p>...SeriesTitle1/EpisodeTitle_s01.e01.mp4</p>
                        <p>...SeriesTitle1/S01E02.mp4</p>
                        <p>...SeriesTitle1/Title-E03S01.mp4</p>
                        <p>...Series2/EpisodeTitle_s01.e01.mp4</p>
                        <p>...Series2/S01E02.mp4</p>
                        <p>...Series2/Title-E03S01.mp4</p>
                        <br/><br/>

                <?php } }?>

                <div class="form-group">
                    <label> Select Categories to be added to those movies/series </label>
                    <select onchange="addCategory(this)" name="sub_category" class="form-control select2">
                        <option value="">Categories</option>
                        <option value="-1">ADD TMDB CATEGORY</option>
                        <?php while($cat = $r_category->fetch_assoc()) {
                            echo "<option value=\"{$cat['id']}\">{$cat['name']}</option>";
                        } ?>
                    </select>
                    <div class="list-categories"></div>
                </div>

                <div class="form-group " >
                    <label>Select Stream Server</label>
                    <select onchange="getContent($('#browser'),'/home','directories')"  id="server_id" name="server_id" class="form-control select2">

                        <?php
                        while($s=$r_servers->fetch_assoc()) {
                            echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                        }
                        ?>

                    </select>
                </div>

                <div class="form-group">
                    <label> IMDB Info Language  </label>
                    <select class="select2 form-control" name="tmdb_lang" >
                        <?php foreach($__LANGUAGES as $k => $l) { ?>
                            <option value="<?=$k?>"><?=$l?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label> Select Folder  </label>
                    <select id="browser" onchange="getContent(this,this.value,'directories');  $(this).select2('open');"  class="select2 form-control" name="directory" >
                    </select>
                </div>

                <div class="form-group-bs">
                    <label>Do not download images.</label>
                    <input type="checkbox" class="bootstrap-switch"  name="do_not_download_images"/>
                </div>


            </div>

            <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                <div class="checkbox packageGroup">

                    <h4 style="margin-top:30px; font-weight:bold;">
                        <span style='font-size:16px'><a href="#!" onclick='toggleAllBouquets(true);'>Check</a>/<a href="#!" onclick='toggleAllBouquets(false);'>Un Check All.</a></span></h4>

                    <input  name="bouquets[]" value="" type="hidden">

                    <?php

                    while ($row=$r_bouquets->fetch_assoc()) { ?>

                        <label style='margin-left:10px; margin-bottom:10px'> <input name="bouquets[]" value="<?php echo $row['id'];?>"
                                                                                    type="checkbox"> <?php echo $row['name']; ?>
                        </label>

                    <?php } ?>

                </div>
            </div>

        </div>


    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    function getContent(obj,path='/home/aces/',type='directories'){

        formats  = [".mp4", ".mp3", ".avi", ".mkv", '.flv', '.mp2', '.wav', '.ts', '.wav', 'srt' ];

        if(formats.some(path.includes.bind(path))) {
            return false;
        }

        server_id = $("#server_id").val();
        if(!server_id) server_id = 1;

        $.ajax({
            async:false,
            url: 'ajax/pServerContent.php',
            type: 'post',
            dataType: 'json',
            data: { 'get_server_content' : server_id, path:path, type:type },
            success: function(resp) {
                $(obj).html(resp.content);
                $(obj).select2({ width: '100%', dropdownParent: $(".cmodal") });


            }
        });
    }

    function removeCat(obj) {
        $(obj).parent('span').fadeOut('slow', function() { $(this).remove(); })
    }

    function addCategory(obj) {

        txt = $("select[name='sub_category']>option:selected").text();
        val = $("select[name='sub_category']>option:selected").val();


        if( $("input[value='"+val+"'].subCats").length == 0 )
            $(".list-categories").append("<span class='badge badge-success'>" +
                "<input type='hidden' class='subCats' name='categories[]' value='"+val+"'/>"+txt+"" +
                "<a href='#!' onclick='removeCat(this)'>X</a></span>");

        $("select[name='sub_category']").val(0);
        $("select[name='sub_category']").select2({ width: '100%', dropdownParent: $(".cmodal") });

    }

    function toggleAllBouquets(s) {

        if(s == true ) {
            $("input[name=bouquets\\[\\]]").prop("checked",true);
        } else
            $("input[name=bouquets\\[\\]]").prop("checked",false);

    }

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $("#formModal button[type='submit']").prop("disabled", true);
        $.ajax({
            url: 'ajax/pVideos.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                //setTimeout(reloadTable, 1000);
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

        $("#formModal input[name='do_not_download_images']")
            .prop("checked", <?=$do_not_download_images ? 'true' : 'false'; ?>);

        getContent($("#browser"),'/home','directories');

        $("select[name='tmdb_lang']").val('<?=$tmdb_lang?>');

        $(".select2").select2({ width: '100%', dropdownParent: $(".cmodal") });

        $(".bootstrap-switch").bootstrapSwitch();

    });

</script>