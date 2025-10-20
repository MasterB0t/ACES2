<?php

session_start();

if(!$_SESSION['admin_id']) {
    Redirect('/admin/login.php');
}

//FORCE LOCK.
$_SESSION['admin_expiration'] = 0;

$ref = $_GET['ref'] ? $_GET['ref'] : '/admin/profile.php';

$Admin = new \ACES2\Admin($_SESSION['admin_id']);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME; ?> | Lockscreen </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
</head>

<body class="hold-transition lockscreen">
<!-- Site wrapper -->
<!-- Automatic element centering -->
<div class="lockscreen-wrapper">
    <div class="lockscreen-logo">
        <a href="#!"><b><?=SITENAME;?></b></a>
    </div>
    <!-- User name -->
    <div class="lockscreen-name"><?=$_SESSION['admin_full_name'];?></div>

    <!-- START LOCK SCREEN ITEM -->
    <div class="lockscreen-item shakethis">
        <!-- lockscreen image -->
        <div class="lockscreen-image">
            <img src="/avatars/<?=$Admin->profile_pic;?>" alt="<?=$Admin->name;?>">
        </div>
        <!-- /.lockscreen-image -->

        <!-- lockscreen credentials (contains the form) -->
        <form id="formLock" class="lockscreen-credentials">
            <input type="hidden" name="action" value="un_lock"/>
            <input type="hidden" name="token" value="<?= \ACES2\Armor\Armor::createToken('admin_lock', 60 * 60);?>" />
            <div class="input-group">
                <input type="password" name="password" class="form-control"
                       autocomplete="new-password" placeholder="password">

                <div class="input-group-append">
                    <button type="submit" class="btn">
                        <i class="fas fa-arrow-right text-muted"></i>
                    </button>
                </div>
            </div>
        </form>
        <!-- /.lockscreen credentials -->

    </div>
    <!-- /.lockscreen-item -->
    <div class="help-block text-center">
        Enter your password to retrieve your session
    </div>
    <div class="text-center">
        <a href="/admin/logout.php">Or sign in as a different user</a>
    </div>
    <div class="lockscreen-footer text-center">
        Copyright &copy; <?=date('Y');?> <b><a href="#!" class="text-black"><?=SITENAME; ?></a></b><br>
        All rights reserved
    </div>
</div>
<!-- /.center -->
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<script src="/plugins/jquery-ui/jquery-ui.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script>
    $("#formLock").submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '/admin/ajax/pAdmin.php',
            type: 'post',
            dataType : 'json',
            data: $(this).serialize(),
            success: function(resp) {
                window.location = '<?=$ref;?>';
            }, error: function(xhr) {
                if( xhr.status == 401 ) {
                    window.location = '/admin/login.php';
                    return;
                } else if(xhr.status == 403 ) {
                    window.location.reload();
                    return;
                }

                $("input[name='token']").val(data.resp.token)
                $("input[name='password']").val('');
                $('.shakethis').effect( "shake" );

            }
        })
    });

    setTimeout( function(){
        window.location = '/admin/logout.php';
    }, 60000 * 60  )

</script>
</body>
</html>