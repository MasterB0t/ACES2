<?php

$ADMIN = new \ACES2\ADMIN();

if (!$ADMIN->isLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$DB = new \ACES2\DB();
$r=$DB->query("SELECT id,host,plex_token FROM iptv_plex_accounts ");

$Watch = 0;
if($_GET['watch'])
    $Watch = 1;

?>
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title align-center">Plex Accounts</h4>
    </div>
    <div class="modal-body">
        <div style="overflow-y: auto; height:250px;">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th>ID</th>
                    <th>HOST</th>
                    <th>Plex Token</th>
                    <th>Actions</th>
                </tr>
                <?php while($row=$r->fetch_assoc()) { ?>
                    <tr>
                        <td><?=$row['id']?></td>
                        <td><?=$row['host']?></td>
                        <td><?=$row['plex_token']?></td>
                        <td>
                            <?php if($Watch) { ?>
                                <a href="#" title="Add To Watch" onclick="importer(<?=$row['id']?>)"><i style="margin:5px" class="fa fa-plus fa-lg"></i></a>
                            <?php } else { ?>
                                <a href="#" title="Import Now" onclick="importer(<?=$row['id']?>)"><i style="margin:5px" class="fa fa-download fa-lg"></i></a>
                            <?php } ?>

                            <a href="#" title="Remove" onclick="removeAccount(<?=$row['id']?>)"><i style="margin:5px" class="fa fa-trash fa-lg"></i></a>
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
            <button type="button" onclick="MODAL('modals/videos/PlexAddAccount.php?watch=1')" class="btn btn-primary">Add New Account</button>
        <?php } else { ?>
            <button type="button" onclick="MODAL('modals/videos/PlexAddAccount.php')" class="btn btn-primary">Add New Account</button>
        <?php } ?>
    </div>
</div>
<script>

    function importer(id) {

        //showOverlayMsg('Getting info from portal..');

        var watch = "";
        <?php if($Watch) { ?>
        watch = "&watch=1";
        <?php } ?>

        MODAL('modals/mPlexImport.php?account_id='+id+watch, 'MODAL' );

    }

    function removeAccount(id) {

        if(!confirm("Are you sure you want to remove this account?"))
            return false;

        $.post('ajax/pPlex.php',{action:'remove_account',account_id:id}, function(data){
            if(data.not_logged) { window.location.href='index.php'; }
            else if(data.errors) { alert(data.errors); }
            else if(data.complete) {
                toastr.success('Account been removed.');
                $(".cmodal").modal('hide');
                $(".modal-backdrop").remove();
                <?php if($Watch) { ?>
                MODAL('modals/mPlexAccounts.php?watch=1');
                <?php } else { ?>
                MODAL('modals/mPlexAccounts.php');
                <?php } ?>
            }
        },'json');

    }

</script>