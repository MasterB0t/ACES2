<?php

    if(!is_object($USER)) {
        $USER = new \ACES2\IPTV\Reseller2(userIsLogged(false));
    }

?><nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div style="max-width:100%;" class="container">
        <a href="#" class="navbar-brand">
            <img src="#" alt="" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?= SITENAME ?></span>
        </a>

        <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-3" id="navbarCollapse">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="/user/IPTV/dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a id="dropAccount" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       class="nav-link dropdown-toggle">Accounts</a>
                    <ul aria-labelledby="dropAccount" class="dropdown-menu border-0 shadow"
                        style="left: 0px; right: inherit;">
                        <li><a href="/user/IPTV/accounts.php" class="dropdown-item">All Accounts</a></li>
                        <li><a href="/user/IPTV/form/formAccount.php" class="dropdown-item">New Account</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a id="dropAccount" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       class="nav-link dropdown-toggle">Resellers</a>
                    <ul aria-labelledby="dropAccount" class="dropdown-menu border-0 shadow"
                        style="left: 0px; right: inherit;">
                        <li><a href="/user/IPTV/resellers.php" class="dropdown-item">All Resellers</a></li>
                        <li><a href="/user/IPTV/form/formReseller.php" class="dropdown-item">New Reseller</a></li>
                    </ul>
                </li>

                <?php if($USER->allow_channel_list) { ?>

                    <li class="nav-item ">
                        <a  href="/user/IPTV/streams.php"  aria-haspopup="true" aria-expanded="false"
                           class="nav-link">Streams</a>
                    </li>

                <?php } if($USER->allow_vod_list ) { ?>
                    <li class="nav-item ">
                        <a  href="/user/IPTV/videos.php"  aria-haspopup="true" aria-expanded="false"
                            class="nav-link">Movies/Series </a>
                    </li>
                <?php } ?>

                <li class="nav-item dropdown">
                    <a id="dropProfile" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       class="nav-link dropdown-toggle">Profile</a>
                    <ul aria-labelledby="dropProfile" class="dropdown-menu border-0 shadow"
                        style="left: 0px; right: inherit;">
                        <li><a href="/user/profile.php" class="dropdown-item">Profile</a></li>
                        <li><a href="/user/logout.php" class="dropdown-item">Logout</a></li>
                    </ul>
                </li>

            </ul>

        </div>

        <!-- Right navbar links -->
        <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge notification-badge"></span>
                </a>
                <div id="notifications-dropdown" class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">0 Notifications</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" style="padding-bottom:24px;" class="dropdown-item dropdown-footer"></a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fa fa-certificate "></i>
                    <span class=" badge badge-warning navbar-badge badgeUserCredits">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header UserCredits">Credits 0</span>
                </div>
            </li>
        </ul>
    </div>
</nav>