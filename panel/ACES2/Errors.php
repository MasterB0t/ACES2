<?php

namespace ACES2;

class ERRORS
{

    const HTTP_USER_BAD_REQUEST = 400;
    const HTTP_NOT_LOGGED = 401;
    const HTTP_FORBIDEN = 403;
    const HTTP_SERVER_ERROR = 500;

    const SYSTEM_ERROR = 'System Error.';
    const SESSION_EXPIRED = "Session have expired.";
    const NO_PRIVILEGES = "You don't have permissions to perform this action.";
    const NO_PERMISSIONS = "You don't have permissions to perform this action.";
    const NOT_LOGGED = "You need to login first.";

    const NAME_EMPTY = 'The name is required.';
    const NAME_SHORT = 'Name is too short.';
    const NAME_ILLEGAL = 'The name have illegal characters.';

    const LASTNAME_EMPTY = 'The lastname is required.';
    const LASTNAME_SHORT = 'Lastname is too short.';
    const LASTNAME_ILLEGAL = 'The lastname have illegal characters.';

    const USERNAME_EMPTY = 'The username is required.';
    const USERNAME_ILLEGAL = 'The username have illegal characters.';
    const USERNAME_SHORT = 'The username required at least 5 characters long.';
    const USERNAME_IN_USED = 'This username is in used.';

    const EMAIL_EMPTY = 'The email address is required.';
    const EMAIL_ILLEGAL = 'Enter a valid email address.';
    const EMAIL_IN_USED = 'This email address is in used.';

    const PASSWORD_EMPTY = 'Enter a password.';
    const PASSWORD_WEAK = 'The password is too weak.';
    const PASSWORD_DO_NOT_MATCH = 'The password do not match.';


}