<?php
$a=0;

?><!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-1">
    <!-- Brand Logo -->
    <a href="#!" class="brand-link">
        <img src="/dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><?=SITENAME;?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="/avatars/<?=$ADMIN->profile_pic;?>" class="profileUserImage img-circle elevation-2" alt="User Profile Image">
            </div>
            <div class="info">
                <a href="/admin/profile.php" class="d-block"><?=$ADMIN->name;?></a>
            </div>
        </div>

<!--         SidebarSearch Form -->
<!--              <div class="form-inline">-->
<!--                <div class="input-group" data-widget="sidebar-search">-->
<!--                  <input class="form-control form-control-sidebar " type="search" placeholder="Search" aria-label="Search">-->
<!--                  <div class="input-group-append">-->
<!--                    <button class="btn btn-sidebar">-->
<!--                      <i class="fas fa-search fa-fw"></i>-->
<!--                    </button>-->
<!--                  </div>-->
<!--                </div>-->
<!--              </div>-->
        <!-- Sidebar Menu -->

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent"
                data-widget="treeview" role="menu" data-accordion="false">

                <?php if($ADMIN->hasPermission()){ ?>

                <li class="nav-item">
                    <a href="/admin/IPTV/dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-dashboard"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <?php } if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VIEW_STREAMS)
                    || $ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_RESTART_STOP_STREAMS)
                    || $ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)
                ) { ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-video"></i>
                        <p>
                            Streams
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/streams.php" class="nav-link ">
                                <i class="nav-icon fa fa-video"></i>
                                <p>All Streams</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/stream_profiles.php" class="nav-link ">
                                <i class="nav-icon fa fa-gears"></i>
                                <p>Stream Profiles</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/epg_sources.php" class="nav-link ">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Epg Sources</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/streams/formStream.php" class="nav-link ">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Add Stream</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/streams/formChannel.php" class="nav-link ">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Add Channel 24/7</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formStreamProfile.php" class="nav-link ">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Add Stream Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/channel_order.php" class="nav-link ">
                                <i class="nav-icon fa fa-list"></i>
                                <p>Channel Order</p>
                            </a>
                        </li>
                    </ul>
                </li>

                    <li class="nav-item ">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fa fa-calendar"></i>
                            <p>
                                Events
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview" >

                            <li class="nav-item">
                                <a href="/admin/IPTV/stream_events.php" class="nav-link ">
                                    <i class="nav-icon fa fa-calendar"></i>
                                    <p>All Events Automation</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/admin/IPTV/form/formStreamEvent.php" class="nav-link ">
                                    <i class="nav-icon fa fa-plus"></i>
                                    <p>Add Event Automation</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php }

                    if($ADMIN->hasPermission(ACES2\IPTV\AdminPermissions::IPTV_FULL_STREAMS)) {

                ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-right-left"></i>
                        <p>
                            Providers
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/providers.php" class="nav-link ">
                                <i class="nav-icon fa fa-right-left"></i>
                                <p>Providers</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/provider_content.php" class="nav-link ">
                                <i class="nav-icon fa fa-list"></i>
                                <p>Provider Content</p>
                            </a>
                        </li>
                    </ul>
                </li>



                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-table-list"></i>
                        <p>
                            Categories
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/categories.php" class="nav-link ">
                                <i class="nav-icon fa fa-list"></i>
                                <p>All Categories</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/category_order.php" class="nav-link ">
                                <i class="nav-icon fa fa-list"></i>
                                <p>Category Order</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php
                    }

                    if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) {

                ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-tv"></i>
                        <p>
                            Accounts
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/accounts.php" class="nav-link ">
                                <i class="nav-icon fa fa-tv"></i>
                                <p>All Accounts</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/accounts.php?filter_device=mag" class="nav-link ">
                                <i class="nav-icon fa fa-tv"></i>
                                <p>Mag/Stb Accounts</p>
                            </a>
                        </li>
                        <?php if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_ACCOUNT)) { ?>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formAccount.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Account</p>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </li>

                <?php } ?>

                <?php if($ADMIN->hasPermission()) { ?>

                <li class="nav-item">
                    <a href="/admin/IPTV/servers.php" class="nav-link">
                        <i class="nav-icon fas fa-server"></i>
                        <p>
                            Servers
                        </p>
                    </a>
                </li>

                <?php } ?>

                <?php if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD)) { ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-film"></i>
                        <p>
                            Videos
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/videos.php" class="nav-link ">
                                <i class="nav-icon fa fa-film"></i>
                                <p>All Videos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/episodes.php" class="nav-link ">
                                <i class="nav-icon fa fa-list"></i>
                                <p>All Episodes</p>
                            </a>
                        </li>

                        <?php if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_VOD_FULL)) { ?>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formVideo.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Movie</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formVideo.php?type=series" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Series</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/watch.php" class="nav-link ">
                                <i class="nav-icon fa fa-eye"></i>
                                <p>Watch</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/video_reports.php" class="nav-link ">
                                <i class="nav-icon fa fa-triangle-exclamation"></i>
                                <p>Video Reports</p>
                            </a>
                        </li>

                        <?php } ?>
                    </ul>
                </li>

                <?php } ?>
                <?php if($ADMIN->hasPermission(\ACES2\IPTV\AdminPermissions::IPTV_MANAGE_BOUQUETS)) { ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-suitcase"></i>
                        <p>
                            Bouquets & Packages
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/bouquets.php" class="nav-link ">
                                <i class="nav-icon fa fa-suitcase"></i>
                                <p>All Bouquets & Packages</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formBouquet.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Bouquet</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formBouquetPackage.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Package</p>
                            </a>
                        </li>

                    </ul>
                </li>

                <?php } ?>
                <?php if($ADMIN->hasPermission()) { ?>

                <li class="nav-item ">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-users-gear"></i>
                        <p>
                            Admins
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/admins.php" class="nav-link ">
                                <i class="nav-icon fa fa-users-gear"></i>
                                <p>All Admins</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/admin_group.php" class="nav-link ">
                                <i class="nav-icon fa fa-people-line"></i>
                                <p>Admin Groups</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formAdmin.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Admin</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formAdminGroup.php" class="nav-link ">
                                <i class="nav-icon fa fa-plus"></i>
                                <p>Add Admin Group</p>
                            </a>
                        </li>
                    </ul>
                </li>


                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-users"></i>
                        <p>
                            Resellers
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/resellers.php" class="nav-link">
                                <i class="nav-icon fas fa-users "></i>
                                <p>
                                    All Resellers
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/IPTV/form/formReseller.php" class="nav-link">
                                <i class="nav-icon fas fa-plus "></i>
                                <p>
                                    Add Reseller
                                </p>
                            </a>
                        </li>
                    </ul>

                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa fa-gears"></i>
                        <p>
                            Settings
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" >
                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Stream & EPG
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php?tabVods" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Movies & Series
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php?tabAccounts" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Accounts
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php?tabBackups" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Backups
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php?tabBackupLocations" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Backup Locations
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/admin/IPTV/settings.php?tabMaintenance" class="nav-link">
                                <i class="nav-icon fas fa-gears "></i>
                                <p>
                                    Maintenance
                                </p>
                            </a>
                        </li>

                    </ul>

                </li>

                <li class="nav-item">
                    <a href="/admin/firewall.php" class="nav-link">
                        <i class="nav-icon fa-solid fa-shield-alt"></i>
                        <p>
                            Firewall
                        </p>
                    </a>
                </li>

                <?php } ?>

<!--                <li class="nav-item">-->
<!--                    <a href="#" class="nav-link">-->
<!--                        <i class="nav-icon fa fa-gears"></i>-->
<!--                        <p>-->
<!--                            Settings-->
<!--                            <i class="fas fa-angle-left right"></i>-->
<!--                        </p>-->
<!--                    </a>-->
<!--                    <ul class="nav nav-treeview" >-->
<!--                        <li class="nav-item">-->
<!--                            <a href="/admin/settings/settings" class="nav-link">-->
<!--                                <i class="nav-icon fas fa-gear "></i>-->
<!--                                <p>-->
<!--                                    General-->
<!--                                </p>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                    </ul>-->
<!---->
<!--                </li>-->

                <li class="nav-item">
                    <a href="/admin/update.php" class="nav-link">
                        <i class="nav-icon fa-solid fa-upload"></i>
                        <p>
                            Update
                            <?= \ACES2\Aces::isNewVersion()
                                ? '<span class="right badge badge-success">New Version</span>'
                                : '' ?>
                        </p>
                    </a>
                </li>



                <li class="nav-item">
                    <a href="/admin/logout.php" class="nav-link">
                        <i class="nav-icon fa-solid fa-right-from-bracket"></i>
                        <p>
                            Logout
                        </p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>