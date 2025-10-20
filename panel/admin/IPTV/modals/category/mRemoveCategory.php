<?php
 $Category = new ACES2\IPTV\Category($_REQUEST['id']);
?><form id="formCategory">
    <input type="hidden" name="id" value="<?=$Category->id?>" />
    <input type="hidden" name="action" value="remove_category" />
    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.category');?>" />
<div class="modal-header">
    <h4 class="text-center">Remove Category</h4>
</div>

<div class="modal-body">
    <h5>Are you sure you want to remove '<i><?=$Category->name;?></i>' category?</h5>
</div>

<div class="modal-footer">
    <button type="button"  class="btn btn-default pull-right" data-dismiss="modal">Close</button>
    <button type="submit"  class="btn btn-danger">Remove</button>
</div>

</form>
<script>
$("#formCategory").submit(function(e){
    e.preventDefault();

    $.ajax({
        url: 'ajax/pCategory.php',
        type: 'post',
        dataType: 'json',
        data: $(this).serialize(),
        success: function (resp) {
            toastr.success("Category have been removed.");
            $(".cmodal").modal('hide');
            reloadTable();
        }, error: function (xhr) {
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



</script>