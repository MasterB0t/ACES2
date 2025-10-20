<?php

$Admin =  new \ACES2\Admin();

$EditID = 0;
if((int)$_GET['id']) {
    $EditID = (int)$_GET['id'];
    $admin = new \ACES2\Admin($EditID);
}

if(!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if(!$Admin->hasPermission("")) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new \ACES2\DB();
$r=$db->query("SELECT id,name FROM `admin_groups` ");

?>
<form id="formModalAdmin">
    <?php if($EditID) { ?>
        <input type="hidden" name="action" value="edit_admin" />
        <input type="hidden" name="id" value="<?=$EditID?>" />
    <?php } else { ?>
        <input type="hidden" name="action" value="add_admin" />
    <?php } ?>

    <input type="hidden" name="token" value="<?=\ACES2\Armor\Armor::createToken('iptv.admin');?>" />
    <div class="modal-header">
        <div class="modal-title">
            <?= $EditID != 0 ? '<h4 class="text-center">Edit Admin</h4>'
                : '<h4 class="text-center">Add Admin</h4>' ?>
        </div>
    </div>


    <div class="modal-body">

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control"  />
        </div>

        <div class="form-group">
            <label>Username</label>
            <input required type="text" name="username" class="form-control" />
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" />
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-group ">
                <input type="text" name="password" class="form-control">
                <span class="input-group-append">
                    <button onclick="randomPassword()" type="button" class="btn btn-info btn-flat">Random</button>
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>Admin Group</label>
            <select name="group_id" class="form-control select2">
                <option value="0">[FULL ADMIN]</option>
                <?php
                    while($o = $r->fetch_assoc()) {
                        echo "<option value='{$o['id']}'> {$o['name']} </option>";
                    }?>
            </select>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>

</form>

<script>
    function randomPassword() {
        chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        pass = "";
        for(x=0;x<8;x++)
        {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
        }
        $("#formModalAdmin input[name='password']").val(pass);
    }

    $("#formModalAdmin").submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/admin/pAdmin.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Success");
                setTimeout(reloadTable, 1000);
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

        $(".select2").select2();

        <?php if($EditID) { ?>

            $("#formModalAdmin input[name='name']").val('<?=$admin->name;?>');
            $("#formModalAdmin input[name='username']").val('<?=$admin->username;?>');
            $("#formModalAdmin input[name='email']").val('<?=$admin->email;?>');
            $("#formModalAdmin select[name='group_id']").val(<?=$admin->group_id;?>);
            $("#formModalAdmin input[name='password']").prop('placeholder','Leave it blank for no change.');

        <?php }  ?>

    });

</script>
