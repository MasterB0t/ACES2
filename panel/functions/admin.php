<?php
function adminIsLogged($keep_session=true) {

    if(session_status() == PHP_SESSION_NONE)
        session_start();

    if(!$_SESSION['admin_id'] ||
        $_SESSION['admin_expiration'] < time())
        return 0;


    if($keep_session) {
        adminUpdateSession();
    }


    return $_SESSION['admin_id'];

}

function adminUpdateSession() {

    $_SESSION['admin_expiration'] = time() + $_SESSION['admin_expiration_in'];

}

function adminLogout() {
    if(session_status() == PHP_SESSION_NONE)
        session_start();

    session_destroy();
}

function userIsLogged($keep_session=true) {

    if(session_status() == PHP_SESSION_NONE)
        session_start();

    if(!$_SESSION['user_id'] ||
        $_SESSION['user_expiration'] < time())
        return 0;


    if($keep_session) {
        userUpdateSession();
    }


    return $_SESSION['user_id'];
}

function userUpdateSession() {
    $_SESSION['user_expiration'] = time() + $_SESSION['user_expiration_in'];
}

function userLogout() {
    if(session_status() == PHP_SESSION_NONE)
        session_start();
    
    session_destroy();
}

