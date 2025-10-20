<?php
    $EditID = 0;

    if(isset($_REQUEST['edit'])) {
        $Category = new \ACES2\IPTV\Category($_REQUEST['edit']);
        $EditID = $Category->id;
    }

?>
<form id="formCategory">
    <?php if($EditID) { ?>
        <input type="hidden" name="id" value="<?=$EditID?>" />
        <input type="hidden" name="action" value="edit_category" />
    <?php }  else { ?>
        <input type="hidden" name="action" value="add_category" />
    <?php } ?>
    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.category');?>" />
<div class="modal-header">
    <?= $EditID != 0 ? '<h4 class="text-center">Edit Category</h4>'
    : '<h4 class="text-center">Add Category</h4>' ?>
</div>

<div class="modal-body">
    <div class="form-group">
        <label> Category Name </label>
        <input name="name" class="form-control"  />
    </div>

    <div class="form-group form-group-bs">
        <label>
            Is An Adult Category</label>
        <input type="checkbox" name="is_adult" class="boostrap-switch" />
    </div>

</div>

<div class="modal-footer">
    <button type="button"  class="btn btn-default flaot-left" data-dismiss="modal">Close</button>
    <button type="submit"  class="btn btn-primary">Submit</button>
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
                reloadTable();
                toastr.success("Success");
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
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");

                }
            }
        });

    });

    $(document).ready(function(){

        <?php if($EditID) { ?>
            $("#formCategory input[name='name']").val('<?=$Category->name;?>');
            <?php if($Category->is_adult) { ?>
                $("#formCategory input[name='is_adult']").prop('checked',true);
            <?php } ?>

        <?php } ?>

        $(".boostrap-switch").bootstrapSwitch();

    });


</script>