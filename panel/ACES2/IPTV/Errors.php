<?php

namespace ACES2\IPTV;

class Errors {

    const IPTV_CREDITS_ILLEGAL                          = 'Enter a numeric value for credits.';

    const IPTV_ACCOUNT_NAME_REQUIRED                    = 'The account name is required.';
    const IPTV_ACCOUNT_NAME_TOO_SHORT                   = 'Account name required at least 3 characters long.';
    const IPTV_ACCOUNT_NAME_TOO_LONG                    = "Account name can't be more than 99 characters long.";

    const IPTV_ACCOUNT_USERNAME_ILLEGAL                 = 'The username have illegal characters.';
    const IPTV_ACCOUNT_USERNAME_TOO_SHORT               = 'Username must be at least 2 characters long.';
    const IPTV_ACCOUNT_USERNAME_TOO_LONG                = "Account username can't be more than 25 characters long";

    const IPTV_ACCOUNT_PASSWORD_ILLEGAL                 = 'The account password  have illegal characters.';
    const IPTV_ACCOUNT_PASSWORD_TOO_SHORT               = 'Account password must be at least 5 characters long.';
    const IPTV_ACCOUNT_PASSWORD_TOO_LONG                = "Account password can't be more than 25 characters long";

    const IPTV_ACCOUNT_EXIST                            = 'The username and password its in used.';
    const IPTV_ACCOUNT_NOT_EXIST                        = 'The iptv account do not exist.';

    const IPTV_ACCOUNT_PACKAGE_REQUIRED                 = "The account package is required.";
    const IPTV_ACCOUNT_PACKAGE_NOT_ALLOW_TRIAL          = 'Trials accounts are not allowed on this package.';
    const IPTV_ACCOUNT_PACKAGE_ONLY_ALLOWED             = 'Only Trial account are allowed in this package.';

    const IPTV_ACCOUNT_MAC_ILLEGAL                      = 'Set a valid mac address.';
    const IPTV_ACCOUNT_MAC_IN_USED                      = 'This mac address is in used by another user.';
    const IPTV_ACCOUNT_MAC_NOT_ALLOWED                  = 'Stb/Mag devices are not allowed on this package.';
    const IPTV_ACCOUNT_MAC_ONLY                         = 'Only Stb/Mag devices are allowed on this package';

    const IPTV_ACCOUNT_OWNER_INVALID                    = 'Select an exist reseller as owner.';

    const IPTV_CREDITS_NOT_ENOUGH                       = 'You do not have enough credits.';
    const IPTV_CREDITS_REQUIRED                         = 'At least one credit is required to create reseller.';
    const IPTV_CREDITS_WRONG_VALUE                      = 'The credit must be a numeric value.';

    const IPTV_ACCOUNT_WRONG_PIN                        = 'Account pin must be a numeric value with four digits.';

    const IPTV_SUBRESELLER_DO_NOT_EXIST                 = 'The selected reseller do not exist.';


    const IPTV_EPISODE_NUMBER_REQUIRED                  = 'Episode Number is required.';
    const IPTV_EPISODE_EXIST                            = 'This episode already exist.';
    const IPTV_EPISODE_TITLE_REQUIRED                   = 'Episode Title is required.';


    const IPTV_SERVER_REQUIRED                          = 'Select a server.';

    const IPTV_SERVER_NOT_EXIST                         = 'The server does not exist.';

    const IPTV_VIDEO_FILE_EMPTY                         = 'Please select a video file.';


    const IPTV_VOD_NAME_EMPTY                           = 'The name is required.';
    const IPTV_VOD_CATEGORY_EMPTY                       = 'At least one category is required.';
    const IPTV_VOD_CATEGORY_NO_EXIST                    = 'One or more category do not exist.';
    const IPTV_VOD_TRAILER_ILLEGAL                      = 'Enter a valid youtube link for trailer.';
    const IPTV_VOD_LOGO_SIZE_LIMIT                      = 'One or more image have exceed the size limit. ';
    const IPTV_VOD_LOG_NOT_SUPORTED                     = 'One ore more image is not support';
    const IPTV_VOD_FILE_EMPTY                           = 'Select a file for the movie';


    const IPTV_STREAM_PROFILE_NAME_EMPTY                = 'The stream profile name is required.';


    const IPTV_STREAM_NAME_REQUIRED                      = 'Enter a stream name.';
    const IPTV_STREAM_CATEGORY_REQUIRED                  = 'Select a category for the stream.';
    const IPTV_STREAM_SERVER_REQUIRED                    = 'Select a stream server for the stream.';
    const IPTV_STREAM_LOGO_NOT_SUPPORTED                 = 'The logo format is not supported.';


    const IPTV_CATCHUP_EXP_DAYS_REQUIRED                 = 'Select for how long recordings will be archived.';

}