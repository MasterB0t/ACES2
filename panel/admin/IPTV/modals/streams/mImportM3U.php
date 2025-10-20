<?php

$DB = new \ACES2\DB;

$r_categories = $DB->query("SELECT id,name FROM iptv_stream_categories ORDER BY name  ");

$r_bouquets = $DB->query("SELECT id,name FROM iptv_bouquets ");

$r_servers = $DB->query("SELECT id,name FROM iptv_servers ");
$r_stream_profiles = $DB->query("SELECT id,name FROM iptv_stream_options WHERE only_chan_id = 0 ");

?><div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title align-center">Import From M3U Playlist</h4>
        </div>
        <div class="modal-body">

            <form id='formUploadM3U'>

                <input type="hidden" name="token"
                       value="<?=\ACES2\Armor\Armor::createToken('iptv.m3u_import');?>" />

                <ul class="nav nav-tabs nav-fill" id="custom-tabs-three-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active"
                           id="custom-tabs-1-tab" data-toggle="pill"
                           href="#custom-tabs-1-content" role="tab"
                           aria-controls="custom-tabs-1-home"
                           aria-selected="true">Info</a>
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

                        <div class="form-group">
                            <select name="category_id" class="form-control select2">
                                <option value="">[ADD TO PLAYLIST CATEGORY]</option>
                                <?php
                                while($c=$r_categories->fetch_assoc()) {
                                    echo "<option value='{$c['id']}'> {$c['name']} </option> ";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Stream Server</label>
                            <select name="server_id" aria-hidden="true" class="form-control select2">

                                <?php
                                while($s=$r_servers->fetch_assoc()) {
                                    echo "<option value='{$s['id']}'> {$s['name']} </option> ";
                                }
                                ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Stream Profile</label>
                            <select name="stream_profile" class="form-control select2">
                                <?php
                                while ($sp = $r_stream_profiles->fetch_assoc()) {
                                    echo "<option value='{$sp['id']}'> {$sp['name']} </option> ";
                                }
                                ?>
                            </select>
                        </div>


                        <div class="form-group">
                            <label>Ondemand</label>
                            <select name="ondemand" class="form-control select2">
                                <option value="0"> Do not set on-demand </option>
                                <option value="1"> Set on-demand </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Stream Channel</label>
                            <select name="stream" class="form-control select2">
                                <option value="1">Stream Channels</option>
                                <option value="0">Do not stream. Redirectect client to source.</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label> Select m3u playlist file. </label>
                            <input id="inputFileM3u" name="m3u_file" type="file">
                        </div>

                    </div>



                    <!-- BOUQUETS -->
                    <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                        <h4 style=" font-weight:bold;">Bouquets
                            <span style='font-size:16px'>
                                <a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",true);'>Check</a>/<a href="#!" onclick='$("input[name=bouquets\\[\\]]").prop("checked",false);'>Un Check All.</a></span></h4>

                        <input type="hidden" name='bouquets[]' value='' />
                        <?php  while($opt = $r_bouquets->fetch_assoc() ) { ?>

                            <label style='margin-left:10px; margin-bottom:10px'>
                                <input name="bouquets[]" value="<?=$opt['id'];?>"  type="checkbox"> <?=$opt['name']; ?>
                            </label>

                        <?php } ?>
                    </div>

                </div>


            </form>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
            <button onclick='submitModal()' type="button" class="btn btn-primary btnSubmitModal">Send</button>
        </div>

    </div>
<script>

    function submitModal() {

        $(".btnSubmitModal").prop('disabled', true);

        $.ajax({

            url: 'ajax/streams/pImportM3U.php',
            type: 'post',
            dataType: 'json',
            data: new FormData($("#formUploadM3U")[0]),
            contentType: false,
            processData:false,
            success: function (data) {
                $(".btnSubmitModal").prop('disabled', false);
                if (data) {
                    if (data.not_logged) {
                        window.location.href = 'index.php';
                    } else if (data.error) {
                        toastr.error(data.error);
                    } else if (data.complete) {
                        toastr.success('Complete');
                        $(".cmodal").modal('hide');
                        //loadTable();
                    }

                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $(".btnSubmitModal").prop('disabled', false);
                var response = xhr.responseJSON;
                switch (xhr.status) {
                    case 401:
                        window.location.href = 'login.php';
                        break;
                    default:
                        if(typeof response != "undefined" && response.error )
                            toastr.error(response.error)
                        else
                            toastr.error('System Error');
                }
            }

        });

    }

    $("#formModal ").find(".select2").select2();

</script>