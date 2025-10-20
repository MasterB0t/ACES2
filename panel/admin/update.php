<?php

    if(!$AdminID=adminIsLogged()){
        Redirect("/login.php");
        exit;
    }

    $ADMIN = new \ACES2\Admin($AdminID);

    if(!$ADMIN->hasPermission("")) {
        Redirect("/profile.php");
    };

    $json = '';
    if($new_version = \ACES2\ACES::isNewVersion()) {
        $json = \ACES2\ACES::getVersion();
    }

    $title = $new_version ? "New Version $new_version" : 'Panel is up to date.';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=SITENAME;?>| Update </title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/plugins/fontawesome-free-6.2.1-web/css/all.min.css">
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
        .wrap  {
            text-align: center;
        }
        ul {
            display: inline-block;
            margin: 0;
            padding: 0;
            /* For IE, the outcast */
            zoom:1;
            *display: inline;
        }
        li {
            border:none;
            width:auto;
            padding: 2px 0px;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini text-sm layout-footer-fixed layout-fixed" >
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <?php include 'header.php'; ?>

    <!-- Main Sidebar Container -->
    <?php include 'sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1></h1>
                        <ol class="breadcrumb ">
                            <li class="breadcrumb-item active"></li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <h2 class="text-center"><?=$title;?></h2>
                <?php
                if($new_version){
                    $change_log = explode(PHP_EOL,$json['change_log']);
                    echo "<br><br><div class='wrap'>";
                    echo "<ul style='font-size:14pt;' class=''>";
                    foreach($change_log as $line){
                        echo "<li>{$line}</li>";
                    }
                    echo "</ul></div>";
                }
                ?>
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
<script src="/dist/js/admin.js"></script>