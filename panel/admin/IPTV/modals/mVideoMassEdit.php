<?php

$Admin = new \ACES2\Admin();

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new \ACES2\DB();
$r_categories = $db->query("SELECT id,name FROM iptv_stream_categories");
$r_bouquets = $db->query("SELECT id,name FROM iptv_bouquets ");

?>
<form id="formModal">
    <input type="hidden" name="action" value="mass_edit_video"/>
    <input type="hidden" name="ids" value="<?=$_GET['ids'];?>" />
    <div class="modal-header">
        <div class="modal-title">
            <h5>Mass Edit Selected</h5>
        </div>
    </div>

    <div class="modal-body">

        <div class="form-group">
            <label>Set Categories</label>
            <select id="selectCategory" onchange="modalAddCategory()" class="form-control select2">
                <option value="">Categories</option>
                <?php while($cat = $r_categories->fetch_assoc()) { ?>
                    <option value="<?=$cat['id'];?>"><?=$cat['name'];?></option>
                <?php } ?>
            </select>
            <div class="list-categories pt-3"></div>
        </div>


        <div class="form-group">
            <label> Set bouquets </label>
            <select onchange="toggleBouquets(this.value)" name="set_bouquets" class="form-control">
                <option value="0"> Do not change bouquets on selected.</option>
                <option value="1"> Set bouquets below on selected.</option>
            </select>
        </div>

        <div style="display:none;" class="checkbox packageGroup">

            <h4 style="margin-top:30px; font-weight:bold;">Bouquets <span style="font-size:16px">
                        <a href="#!" onclick="toggleAllBouquets(true);">Check</a>/
                        <a href="#!" onclick="toggleAllBouquets(false);">Un Check All.</a></span></h4>

            <input name="bouquets[]" value="" type="hidden">

            <?php while($bouquets = $r_bouquets->fetch_assoc()) { ?>
                <label style="margin-left:10px; margin-bottom:10px">
                    <input name="bouquets[]" value="<?=$bouquets['id']?>" disabled
                           type="checkbox"> <?=$bouquets['name']?> </label>
            <?php } ?>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>

    function modalAddCategory() {

        txt = $("#selectCategory>option:selected").text();
        val = $("#selectCategory>option:selected").val();

        if( $("input[value='"+val+"'][name='categories[]']").length == 0 )
            $(".list-categories").append("<span class='badge badge-success p-2 m-2 text-md '>" +
                "<input type='hidden' name='categories[]' value='"+val+"'/>"+txt+"<a class='ml-2 text-danger' " +
                "href='#!' onclick='modalRemoveCat(this)'>X</a></span>");

        $("#selectCategory").val(0);
        $("#selectCategory").select2();
    }

    function modalRemoveCat(obj) {
        $(obj).parent().remove();
    }

    function toggleBouquets(v) {

        $("#formModal input[name='bouquets[]']").prop('checked', false);
        $("#formModal input[name='bouquets[]']").prop('disabled', false);

        if (v == 0) {
            $(".packageGroup").hide();
            $("#formModal input[name='bouquets[]']").prop('disabled', true);
        } else {
            $(".packageGroup").show();
            $("#formModal input[name='bouquets[]']").prop('disabled', false);
        }

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

    $(document).ready(function() {
        $(".select2").select2();
    });

</script>