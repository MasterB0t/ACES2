<?php

$Admin = new \ACES2\Admin();

$EditID = $_GET['ids'];

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESELLERS)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$DB = new \ACES2\DB();
$MAIN_RESELLERS = [];
$r_resellers=$DB->query("SELECT u.id,u.username FROM users u INNER JOIN iptv_user_info i ON i.user_id = u.id  ");

$r_credits = $DB->query("SELECT * FROM iptv_bouquet_packages ");

$PKG_VALS = [];

?>
<form id="formModal">

    <input type='hidden' name='ids' value='<?=$_GET['ids'];?>' />
    <input type="hidden" name="action" value="mass_update_resellers" />

    <div class="modal-header">
        <div class="modal-title">
            <h4 class="text-center">Mass Edit Resellers </h4>
        </div>
    </div>

    <div class="modal-body">

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
                   aria-selected="false">Credit Values</a>
            </li>

        </ul>

        <div class="tab-content mt-3" id="custom-tabs-three-tabContent">
            <div class="tab-pane fade show active" id="custom-tabs-1-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">

                <div class="form-group">
                    <label>Block Unblock Selected Reseller Account</label>
                    <select class="form-control" name="enabled">
                        <option value=''> Do not change </option>
                        <option value='enable'> Enable Resellers Account </option>
                        <option value='disabled'> Disabled Resellers Account </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Set Selected Resellers Subreseller Of</label>
                    <select class="form-control select2" name="reseller_of">
                        <option value=''>Do not change</option>
                        <option value='0'>Admins</option>
                        <?php while($m = $r_resellers->fetch_assoc() ) { ?>
                            <option value='<?=$m['id'];?>'><?=$m['username'];?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Selected Resellers Will be Able To Add Sub Resellers</label>
                    <select class="form-control" name="can_add_resellers">
                        <option value=''>Do Not Change</option>
                        <option value='0'>No</option>
                        <option value='1'>Yes</option>
                    </select>
                </div>

                <div class='row'>
                    <div class='col-sm-6 form-group'>
                        <label>Set Reseller Credits </label>
                        <select name='set_reseller_credits' class='form-control resellerCredits'>
                            <option value=''> Do not set reseller credits </option>
                            <option value='1'> Set Reseller Credits</option>
                        </select>
                    </div>
                    <div class='col-sm-6 form-group'>
                        <label></label>
                        <input disabled class='form-control mt-2' required type='number' value='' name='credits'
                               placeholder="Enter the amount credits to set to selected resellers." />
                    </div>
                </div>


            </div>


            <div class="tab-pane fade show" id="custom-tabs-2-content" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                <h5 class="pb-3">Set the reseller credits value here. If you want to reseller able to create an account
                    free set it to 0 or if you don't want him from create an account on such package keep value empty.</h5>

                <div class="form-group">
                    <label>Change Credit Value Of Selected Resellers</label>
                    <select name="override_package" class="form-control resellerOverridePkg" >
                        <option value=''>No</option>
                        <option value='1'>Yes</option>
                    </select>
                </div>

                <div class='row'>
                    <div class='col-md-6 pt-2 pb-1'><h5>Official Credit</h5></div>
                    <div class='col-md-6 pt-2 pb-1'><h5>Trial Credit</h5></div>
                </div>

                <?php
                if($r_credits->num_rows==0) {
                    echo "<h4>You have not add any package yet. Package are needed in order to allow user to create accounts.</h4> ";

                } else while ($row = $r_credits->fetch_assoc()) {

                    $off_val = $row['official_credits'];
                    $trial_val = $row['trial_credits'];


                    ?>
                    <h5 class="pt-2"> Package <?=$row['name'];?></h5>
                    <div class='row'>
                        <div class='col-md-6'>
                            <input id='official-c-<?=$row['id'];?>' type='text' name='official_credits[<?=$row['id'];?>]'
                                   placeholder='Credits Value'
                                   title='Enter the amount of credits the user needs to add an account on this package'
                                   class='form-control credits_input' value='<?=$off_val;?>'/>
                        </div>
                        <div class='col-md-6'>
                            <input id='trial-c-<?=$row['id'];?>' type='text' name='trial_credits[<?=$row['id'];?>]'
                                   placeholder='Trial Credits Value'
                                   title='Enter the amount of credits the user needs to create a trial on this package.
                                   If there are any the user can create a trial without credit.' class='form-control credits_input'
                                   value='<?=$trial_val?>' />
                        </div>
                    </div>

                    <?php

                }
                ?>

            </div>
            </div>


        </div>


    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Update</button>
    </div>

</form>

<script>

    $(".resellerCredits").change(function() {
        $("input[name=credits]").prop('disabled',true);
        $("input[name=credits]").val('');

        if( $(this).val() == '1' )
            $("input[name=credits]").prop('disabled', false);
    });

    $(".resellerOverridePkg").change(function() {
        $(".credits_input").prop('disabled',true);

        if( $(this).val() == 1  ) {
            $(".credits_input").prop('disabled',false);
        }

    });

    $("#formModal").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/pReseller.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Updated");
                setTimeout(reloadTable, 1200);
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
        $(".credits_input").prop('disabled',true);
    });

</script>