<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");

$User = new \ACES2\User($UserID);

$db = new ACES2\DB;


$r_logins=$db->query("SELECT l.login_time, l.ip_address, l.os, l.browser, ip.country, 
       lower(ip.country_code) as country_code,
       ip.org
FROM admin_logins l 
LEFT JOIN iptv_ip_info ip ON l.ip_address = ip.ip_address 
WHERE l.admin_id = '$User->id' 
ORDER BY l.login_time DESC LIMIT 25 ");

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Profile </title>

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
    <!-- Flags Icons -->
    <link rel="stylesheet" href="/plugins/flag-icon-css/css/flag-icons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/dist/css/admin.css">
    <style>

        .table th { border-top:none; }

        .custom-control {
            position: relative;
            display: block;
            min-height: 1.5rem;
            padding-left: 1.5rem;
        }

        .setting-notifications {
            padding:0;
            margin:0;
        }

        .setting-notifications p {
            padding-bottom :0;
            margin-bottom: 0;
            font-size:20px;
        }

        .setting-notifications span {
            color:#4f5966;
        }

        .setting-notifications li {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            padding-top: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f3fa;
            align-items: center;
        }

        .hiddenfile {
            width: 0px;
            height: 0px;
            overflow: hidden;
        }

        #custom-tabs-logins {
            max-height:600px;
            overflow:auto;
        }

    </style>
</head>

<body class="hold-transition text-sm layout-top-nav" >
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1></h1>
                    </div>
                    <div class="col-sm-6">
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12 col-md-10 offset-md-1 col-xl-8 offset-xl-2 ">
                        <div style="min-height:720px;" class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle"
                                         src="/avatars/<?=$User->profile_picture;?>" alt="User profile picture">
                                </div>

                                <h3 class="profile-username text-center"><?=$User->name;?></h3>
                                <p class="text-muted text-center">Click on image to change.</p>

                                <div class="d-none">
                                    <form id="formProfileImage">
                                        <input type="hidden" form="formProfileImage" name="action" value="update_profile_picture" />
                                        <input type="file" form="formProfileImage" name="profile_picture" />
                                    </form>
                                </div>

                                <div class="mt-5">
                                    <div class="card-header p-0 pt-1 border-bottom-0">
                                        <ul class="nav nav-tabs nav-fill" id="custom-tabs-three-tab" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active"
                                                   id="custom-tabs-info-tab" data-toggle="pill"
                                                   href="#custom-tabs-info" role="tab" aria-controls="custom-tabs-three-home"
                                                   aria-selected="true">Profile</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                   id="custom-tabs-settings-tab" data-toggle="pill"
                                                   href="#custom-tabs-settings" role="tab"
                                                   aria-controls="custom-tabs-three-profile"
                                                   aria-selected="false">Theme</a>
                                            </li>
                                            <!--                                            <li class="nav-item">-->
                                            <!--                                                <a class="nav-link" id="custom-tabs-logs-tab" data-toggle="pill" href="#custom-tabs-logs" role="tab" aria-controls="custom-tabs-three-messages" aria-selected="false">Logs</a>-->
                                            <!--                                            </li>-->
                                            <li class="nav-item">
                                                <a class="nav-link" id="custom-tabs-logins-tab"
                                                   data-toggle="pill" href="#custom-tabs-logins" role="tab"
                                                   aria-controls="custom-tabs-three-settings"
                                                   aria-selected="false">Logins</a>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="tab-content mt-3" id="custom-tabs-three-tabContent">
                                        <div class="tab-pane fade show active" id="custom-tabs-info" role="tabpanel" aria-labelledby="custom-tabs-info-tab">
                                            <form id="formInfo">
                                                <input type="hidden" name="action" value="profile" />
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="inpName">Name</label>
                                                            <input  id="inpName" type="text" name="name" class="form-control"
                                                                    value="<?=$User->name?>" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inpNewPassword">New Password</label>
                                                            <input minlength="6" id="inpNewPassword" type="password" name="new_password" autocomplete="new-password" class="form-control"
                                                                   placeholder="Leave blank to keep current password." value="" />
                                                        </div>



                                                        <div class="form-group">
                                                            <label for="inpPin">Pin</label>
                                                            <input id="inpPin" type="number" maxlength="6" minlength="4" name="pin"
                                                                   class="form-control" placeholder="Leave blank to keep current pin." value="" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inpPassword">Password</label>
                                                            <input id="inpPassword" type="password" name="password"
                                                                   autocomplete="new-password" class="form-control"
                                                                   required placeholder="Enter your password to save settings" value="" />
                                                        </div>

                                                    </div>

                                                    <div class="col-12 col-md-6">

                                                        <div class="form-group">
                                                            <label for="inpEmail">Email</label>
                                                            <input disabled id="inpEmail" type="email" name="email" class="form-control"
                                                                   value="<?=$_SESSION['user_email'];?>" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inpConfirmPassword">Confirm New Password</label>
                                                            <input id="inpConfirmPassword" type="password" name="confirm_password"
                                                                   autocomplete="new-password" class="form-control"
                                                                   placeholder="" value="" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label>Auto Lock In</label>
                                                            <select class="form-control" name="auto_lock_in" id="selectAutoLock">
                                                                <option value="300">5 minutes</option>
                                                                <option value="600">10 minutes</option>
                                                                <option value="900">15 minutes</option>
                                                                <option value="1800">30 minutes</option>
                                                                <option value="3600">1 hour</option>
                                                                <option value="10800">3 hours</option>
                                                                <option value="21600">6 hours</option>
                                                                <option value="43200">12 hours</option>
                                                            </select>
                                                        </div>

                                                        <button class="btn btn-primary float-right mt-4" type="submit" >Save</button>

                                                    </div>



                                                </div>

                                            </form>
                                        </div>
                                        <div class="tab-pane fade" id="custom-tabs-settings" role="tabpanel" aria-labelledby="custom-tabs-settings-tab">
                                            <form id="formSettings">
                                                <input type="hidden" name="action" value="settings" />
                                                <ul class="setting-notifications">

                                                    <li>
                                                        <div class="notification-info">
                                                            <p>Text Size</p>
                                                            <span></span>
                                                        </div>
                                                        <div class="custom-control form-group">
                                                            <select class="form-control" name="text_size" id="selectTextSize">
                                                                <option value="very_small">Vew Small</option>
                                                                <option value="small">Small</option>
                                                                <option selected value="normal">Normal</option>
                                                                <option value="big">Big</option>
                                                            </select>
                                                        </div>
                                                    </li>

                                                    <li>
                                                        <div class="notification-info">
                                                            <p>Dark Mode</p>
                                                            <span>Enable Dark Mode</span>
                                                        </div>
                                                        <div class="custom-control form-group">
                                                            <input type="checkbox" name="dark-mode" class="boostrap-switch" >
                                                        </div>
                                                    </li>

                                                </ul>

                                            </form>
                                        </div>
                                        <div class="tab-pane fade" id="custom-tabs-logs" role="tabpanel" aria-labelledby="custom-tabs-logs-tab">
                                        </div>
                                        <div class="tab-pane fade" id="custom-tabs-logins" role="tabpanel" aria-labelledby="custom-tabs-logins-tab">
                                            <table id="tableLogins" class="table table-hover">
                                                <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>OS</th>
                                                    <th>Browser</th>
                                                    <th>IP</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php while($row=$r_logins->fetch_assoc()) { ?>
                                                    <tr>
                                                        <td><?=date('D M, Y h:i', $row['login_time'])?></td>
                                                            <td><?=$row['os']?></td>
                                                            <td><?=$row['browser']?></td>
                                                        <td><?=$row['ip_address']."<i title='{$row['country']}' class='ml-2 flag-icon flag-icon-{$row['country_code']}'></i>"?></td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>

                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include 'footer.php'; ?>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="/plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap Switch -->
<script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>
<!-- Custom -->
<script src="/dist/js/functions.js"></script>
<script src="/dist/js/main.js"></script>
<script src="/dist/js/user.js"></script>
<script>

    var autoLockIn = localStorage.getItem('AUTOLOCK_IN') == null ? '300' : localStorage.getItem('AUTOLOCK_IN');

    if(localStorage.getItem('text-size'))
        $("#selectTextSize").val(localStorage.getItem('text-size'));

    $("select[name='auto_lock_in']")
        .val(autoLockIn);

    if(localStorage.getItem('dark_mode') == 'true' )
        $(".boostrap-switch").prop('checked', true )

    $('.profile-user-img').click(function(){
        $("input[name='profile_picture']").trigger('click');
    })

    $("input[name='profile_picture']").change(function() {

        var f = document.getElementById('formProfileImage');

        $.ajax({
            url: 'ajax/pUser.php',
            type: 'post',
            dataType: 'json',
            data: new FormData(f),
            contentType: false,
            processData:false,
            success: function (resp) {
                toastr.success("Success");

                $('.profile-user-img').attr('src', '/avatars/'+ resp.data.image);
                $('.profileUserImage').attr('src', '/avatars/'+ resp.data.image);
                $("#formProfileImage input[name='token']").val(resp.data.token);

            }, error: function (xhr) {

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        var response = xhr.responseJSON;
                        if (response.error)
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");

                }
            }
        });
    });

    $("#formInfo ").submit(function(e) {

        e.preventDefault();

        $.ajax({
            url: 'ajax/pUser.php',
            type: 'post',
            dataType: 'json',
            data: $(this).serialize(),
            success: function (resp) {
                toastr.success("Saved");
                $("input[name='password']").val('');
                $("input[name='new_password']").val('');
                $("input[name='confirm_password']").val('');
                $("input[name='pin']").val('');
                $(".tokenSettings").val(resp.data.token);

                localStorage.setItem('AUTOLOCK_IN', $("#selectAutoLock").val())

            }, error: function (xhr) {

                $("input[name='password']").val('');
                $("input[name='pin']").val('');

                switch (xhr.status) {
                    case 401:
                    case 403:
                        window.location.reload();
                        break;
                    default :
                        var response = xhr.responseJSON;
                        if (response.error)
                            toastr.error(response.error);
                        else
                            toastr.error("System Error");

                }
            }
        });
    });


    $("input[name='dark-mode']").on('switchChange.bootstrapSwitch', function (event, state) {
        localStorage.setItem('dark_mode', state);
        setPreferences();
    });

    $("#selectTextSize").change(function(){
        localStorage.setItem('text-size',this.value);
        setPreferences();
    })

    $(document).ready(function(){

        $(".boostrap-switch").bootstrapSwitch();
    })

</script>
</body>
</html>