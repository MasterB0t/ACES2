<?php

//error_reporting(0);
//sdate_default_timezone_set('UTC');


if(!file_exists('/home/aces/panel/includes/config.php')) DIE('Please run the installer first.');

require '/home/aces/panel/includes/config.php';


if(empty($config['aces']['root']))
    $config['aces']['root'] = '/home/aces/';


if(!defined('ACES_ROOT')) define('ACES_ROOT', $config['aces']['root']);
define('DOC_ROOT', $config['aces']['root']);
if(!defined('ACES_DOC_ROOT')) define('ACES_DOC_ROOT', $config['aces']['root'] . "/panel/" );


if(!defined('ACES_DB_NAME')) define('ACES_DB_NAME', $config['database']['name'] );
if(!defined('ACES_DB_HOST')) define('ACES_DB_HOST', $config['database']['host'] );
if(!defined('ACES_DB_USER')) define('ACES_DB_USER', $config['database']['user'] );
if(!defined('ACES_DB_PASS')) define('ACES_DB_PASS', $config['database']['pass'] );


if(!isset($config['aces']['log']) || !is_numeric($config['aces']['log'])) $config['aces']['log'] = 1;
if(!defined('ACES_LOG'))
    define('ACES_LOG', $config['aces']['log']);

if(!empty($config['aces']['log_file'])) $config['aces']['log_file'] = '/home/aces/logs/php-aces.log';
if(!defined('ACES_LOG_FILE')) define('ACES_LOG_FILE', @$config['aces']['log_file']);

ini_set("error_log", ACES_LOG_FILE);

$site_name = @$config['aces']['site_name'] ? $config['aces']['site_name'] : 'ACES IPTV';
define('SITE_NAME', $site_name);



ini_set('log_errors', TRUE);
if($config['aces']['log'] >= 7 ) error_reporting(E_ALL ^ E_DEPRECATED);
else if($config['aces']['log'] >= 5 ) error_reporting( E_ERROR | E_WARNING | E_NOTICE );
else if($config['aces']['log'] >= 3 ) error_reporting( E_ERROR | E_WARNING );
else if($config['aces']['log'] >= 1 ) error_reporting( E_ERROR );      
else if($config['aces']['log'] == 0 ) ini_set('log_errors', false); 



if(empty($ACES_IN_ADMIN)) $ACES_IN_ADMIN = FALSE;
if(!defined('ACES_IN_ADMIN')) define('ACES_IN_ADMIN', $ACES_IN_ADMIN); 

if(!defined('IP')) define('IP', $_SERVER['REMOTE_ADDR'] );
if(!defined('ACES_HTTPS')) define('ACES_HTTPS', 1 );
//if(!defined('ACES_URL')) define('ACES_URL', 'http://'.ACES_HOST ) ;


foreach (glob(ACES_DOC_ROOT."/functions/*.php") as $filename)  include_once $filename;
foreach (glob(ACES_DOC_ROOT."/class/*.php") as $filename)  include_once $filename;


include_once ACES_DOC_ROOT.'/framework/structure.php';
include_once ACES_DOC_ROOT.'/framework/database.php';
include_once ACES_DOC_ROOT.'/framework/security.php';
include_once ACES_DOC_ROOT.'/framework/admin.php';
include_once ACES_DOC_ROOT.'/framework/user.php';
//include_once ACES_DOC_ROOT.'/framework/logs.php';
include_once ACES_DOC_ROOT.'/framework/aces.php';


include_once ACES_DOC_ROOT.'/ACES/ARMOR.php';
include_once ACES_DOC_ROOT.'/ACES/MAIN.php';
include_once ACES_DOC_ROOT.'/ACES/DB.php';
include_once ACES_DOC_ROOT.'/ACES/EXP.php';
include_once ACES_DOC_ROOT.'/ACES/USER.php';
include_once ACES_DOC_ROOT.'/ACES/ADMIN.php';
include_once ACES_DOC_ROOT.'/ACES/FILE.php';
include_once ACES_DOC_ROOT.'/ACES/ERRORS.php';
include_once ACES_DOC_ROOT.'/ACES/ARMOR.php';
require_once ACES_DOC_ROOT.'/ACES/AdminPermissions.php';

include_once ACES_DOC_ROOT.'/ACES/addons/IPTV/init.php';

//INIT ADDONS
foreach(glob(ACES_DOC_ROOT."/addons/*",GLOB_ONLYDIR) as $filename)
        include_once $filename."/init.php";


if(session_status() == PHP_SESSION_NONE) session_start();

