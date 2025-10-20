<?php

namespace ACES2;

class Validator {

    static public function isStrongPassword(string $password):bool {

        if(strlen($password) < 6)
            return false;

        return true;
    }

    static public function isUsername(string $username):bool {

        if(preg_match('/^[a-zA-Z0-9-]+$/', $username))
            return true;

        return false;
    }

    static public function isPin(string $pin):bool {

        if(preg_match('/^[0-9]{6}+$/', $pin))
            return true;

        return false;
    }

}