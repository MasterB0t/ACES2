<?php

function ACESiSAdminLogged() {

    $admin = new \ACES\ADMIN;
    if($admin->logged) return true;

    return false;

}

function ACESiSAdminisAdminPrivilege($priv='') {

    $admin = new \ACES\ADMIN;
    if(!$admin->logged) return false;
    if(!$admin->adminIsPrivileged($priv)) return false;

    return true;

}

function ACESIsUserlogged() {
    $user = new \ACES\USER;
    if($user->logged) return true;
    return false;
}

function ACESUserLogout() {
    $USER = new \ACES\USER;
    $USER->userLogout();
}

function ACESgetAdminId() {

    $admin = new \ACES\ADMIN;
    return $admin->id;

}

function ACESgetSetting($setting_name) {

    $aces = new \ACES\MAIN;
    return $aces->get_setting($setting_name);

}

function ACESversion() { return ACES::VERSION ; }