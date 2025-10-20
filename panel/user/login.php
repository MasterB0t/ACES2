<?php

session_start();

if($_SESSION['user_expiration'] > time() )
    Redirect('/user/profile.php');

$ref = str_replace(HOST."/user/", '', $_SERVER['HTTP_REFERER']);
if($_SESSION['user_id'])
    Redirect('/user/lock.php?ref='.$ref);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACES | Reseller Login</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <!--  <link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">-->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">

    <link rel="stylesheet" href="/plugins/toastr/toastr.min.css">
</head>
<body class="hold-transition login-page">
<div style="margin-top:-500px;"  class="login-box">
    <div class="login-logo">
        <a href="#!"><b><?=SITENAME;?></b></a>
    </div>
    <!-- /.login-logo -->
    <div  class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to start your session</p>

            <form method="post" id="login">
                <input type="hidden" name="action" value="login" />
                <input type="hidden" name="token" value="<?= \ACES2\Armor\Armor::createToken('user_login',
                    60 * 10 );?>" />
                <div class="input-group mb-3">
                    <input  class="form-control" name="username" placeholder="Username">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <?php if(!defined('NO_CAPTCHA')) { ?>
                    <!--                    <div class="mb-3">-->
                    <!--                        <div style='margin-bottom:5px;' class="divCaptch">-->
                    <!--                            <img id="captcha" src="/ajax/gCaptcha.php"/><a href="#!" onclick="reloadCaptcha()"><i style="float:right;"-->
                    <!--                                                                                                                  class="fa fa-2x fa-rotate"></i> </a>-->
                    <!--                        </div>-->
                    <!---->
                    <!--                        <div class="form-group">-->
                    <!--                            <input required type="text" class="form-control" name="captcha" placeholder="Enter Captcha" />-->
                    <!--                        </div>-->
                    <!--                    </div>-->
                <?php } ?>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember">
                            <label for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                    <!-- /.col -->
                </div>


            </form>

            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/plugins/toastr/toastr.min.js"></script>
    <!-- AdminLTE App -->
    <script src="/dist/js/adminlte.js"></script>
    <script src="/dist/js/functions.js"></script>
    <script>

        var go_to = "<?php echo $_GET['ref'] ? $_GET['ref'] : 'profile.php'; ?>";

        $("#login").submit(function (e) {

            e.preventDefault();
            $("button[type=submit]").prop('disabled', true);

            $.ajax({
                async: false,
                type: 'post',
                url: 'ajax/pUser.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (resp) {

                    //userGetSettings(function(){ window.location = go_to; })
                    window.location = go_to;

                }, error: function (xhr, ajaxOptions, thrownError) {

                    setTimeout( function() { window.location.reload() } , 1000 );
                    var response = xhr.responseJSON;
                    if (response.error)
                        toastr.error(response.error)
                    else
                        toastr.error('System Error');

                }

            });

        });

        function reloadCaptcha() {
            $("#captcha").attr("src", '');
            $("#captcha").attr("src", "/ajax/gCaptcha.php?" + new Date().getTime());
            return false;
        }
    </script>
</body>
</html>