<?php


$Admin = new \ACES2\Admin();

$EditID = 0;

if (!adminIsLogged(false)) {
    setAjaxError("Not Logged", 401);
}

if (!$Admin->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {
    setAjaxError(\ACES2\ERRORS::NO_PERMISSIONS, 403);
}

$db = new \ACES2\DB;
$r_streams = $db->query("SELECT s.stream_id,s.name,s.stream_icon,xc.host as provider,xc.is_ssl, xc.username, xc.password
    FROM iptv_provider_content_streams s
    INNER JOIN iptv_xc_videos_imp xc ON xc.id = s.provider_id
    ORDER BY name ");

$r_providers = $db->query("SELECT id,host FROM iptv_xc_videos_imp ");

?>
<form id="formModal">
    <input type="hidden" name="action" value=""/>
    <div class="modal-header">
        <div class="modal-title">
            <h5 class="text-center">Add Source From Provider </h5>
        </div>
    </div>

    <div class="modal-body ">

        <div class="row pb-3">
            <div class="col-md-12">
                <form id='formProviderSourceFilter'>
                    <div class="form-group">
                        <label>Filter Providers</label>
                        <select id="filterProvider" multiple name="provider" class="form-control select2 ">
                            <option value=""> All </option>
                            <?php while($o=$r_providers->fetch_assoc()) {?>
                                <option value="<?=$o['id']?>"><?=$o['host']?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>

        </div>

        <table id="tableProviderStreamSources" class="table table-hover ">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Provider</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

    </div>

</form>

<script>

    var tableProviderSource;

    tableProviderSource = $('#tableProviderStreamSources').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "serverSide": true,
        "stateSave" : false,
        pageLength : 10,
        "ajax": {
            url: "/admin/IPTV/tables/gTableProviderStreamSources.php"
        },
        "drawCallback": function (settings) {
            var api = this.api();
            var json = api.ajax.json();
            if (json.not_logged == 1)
                window.location.reload();
        },
    });


    $(document).ready(function() {

        $("#filterProvider").change(function() {

            filter_ids = $(this).val();

            url = tableProviderSource.ajax.url().split("?")[0] + "?filter_providers=" + filter_ids;
            tableProviderSource.ajax.url(url);
            tableProviderSource.ajax.reload(null,false);
            tableProviderSource.page(0);

        })

        $("#filterProvider").select2();

    });


</script>
