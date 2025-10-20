<?php

if (!adminIsLogged(false)) {
    http_response_code(401);
    die;
}

$ADMIN = new \ACES2\ADMIN ();
if(!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    http_response_code(403);
    die;
}

$DB = new \ACES2\DB();
$r=$DB->query("SELECT id,host,username FROM iptv_xc_videos_imp ");


$Watch = isset($_GET['watch']);
$ImportStreams = isset($_GET['import_streams']);

?>

<div class="modal-header">
    <h4 class="modal-title align-center">XC Accounts</h4>
</div>
<div class="modal-body">
    <div style="overflow-y: auto; height:250px;">
        <table class="table table-hover">
            <tbody>
            <tr>
                <th>ID</th>
                <th>HOST</th>
                <th>USERNAME</th>
                <th>Actions</th>
            </tr>
            <?php while($row=$r->fetch_assoc()) { ?>
                <tr>
                    <td><?=$row['id']?></td>
                    <td><?=$row['host']?></td>
                    <td><?=$row['username']?></td>
                    <td>
                        <?php if($Watch) { ?>
                            <a href="form/formVideoWatch.php?xc_account=<?=$row['id']?>"
                               title="Add To Watch"><i style="margin:5px" class="fa fa-plus fa-lg"></i></a>
                        <?php } else { ?>
                            <a href="#" title="Import Now" onclick="importer(<?=$row['id']?>)"><i style="margin:5px" class="fa fa-download fa-lg"></i></a>
                        <?php } ?>
                        <a href="#" title="Edit" onclick="MODAL('modals/mXCAccount.php?account_id=<?=$row['id']?>')"><i style="margin:5px" class="fa fa-edit fa-lg"></i></a>
                        <a href="#" title="Remove" onclick="removeXCAccount(<?=$row['id']?>)"><i style="margin:5px" class="fa fa-trash fa-lg"></i></a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    <?php if($Watch) { ?>
        <button type="button" onclick="MODAL('modals/mXCAccount.php?watch=1')" class="btn btn-primary">Add New Account</button>
    <?php } else { ?>
        <button type="button" onclick="MODAL('modals/mXCAccount.php')" class="btn btn-primary">Add New Account</button>
    <?php } ?>
</div>

<script>

    function importer(id) {
        var watch = "";
        //showOverlayMsg('Getting info from portal..');

        <?php if($ImportStreams) { ?>

            MODAL('modals/XC/mXCImportStreams.php?account_id='+id );

        <?php } else { ?>


            <?php if($Watch) { ?>
                watch = "&watch=1";
            <?php } ?>

            MODAL('modals/mVideoXCImport.php?account_id='+id );

            //MODAL('modals/videos/mImportFromXC.php?edit='+id+watch,'Modal', hideOverlayMsg );

        <?php } ?>

    }

    function removeXCAccount(id) {

        if(!confirm("Are you sure you want to remove this account?"))
            return false;

        $.post('ajax/pXC.php',{id:id, action: 'remove_account'}, function(data){
            if(data.not_logged) { window.location.href='index.php'; }
            else if(data.errors) { alert(data.errors); }
            else if(data.complete) {
                toastr.success('Account been removed.');
                $(".cmodal").modal('hide');
                $(".modal-backdrop").remove();
                <?php if($Watch) { ?>
                MODAL('modals/mXCAccounts.php?watch=1');
                <?php } else { ?>
                MODAL('modals/mXCAccounts.php');
                <?php } ?>
            }
        },'json');

    }

</script>