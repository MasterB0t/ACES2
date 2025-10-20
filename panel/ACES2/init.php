<?php

if(strpos($_SERVER['DOCUMENT_URI'], '/admin/') !== false  ||
    strpos($_SERVER['DOCUMENT_URI'], '/user/') !== false ||
    strpos($_SERVER['DOCUMENT_URI'], '/api/') !== false)
{

    define('ACES_ROOT', '/home/aces/');
    define('DOC_ROOT', '/home/aces/panel/');

    $host = isset($_SERVER['HTTPS']) ? "https://" . $_SERVER['HTTP_HOST'] : "http://" . $_SERVER['HTTP_HOST'];
    define('HOST', $host);


    include '/home/aces/panel' . '/includes/config.php';

    define('DB_NAME', $config['database']['name']);
    define('DB_USER', $config['database']['user']);
    define('DB_PASS', $config['database']['pass']);
    define('DB_HOST', $config['database']['host']);

    define('SITENAME', !empty($_CONFIG['SITENAME']) ? $_CONFIG['SITENAME'] : 'ACES IPTV');
    define('NO_CAPTCHA', 1);

    function include_dir($directory, $is_recursive = true)
    {
        if (is_dir($directory)) {
            $scan = glob("$directory/*");
            foreach ($scan as $file) {
                $filename = $directory . "/" . $file;
                if (is_dir($file) && $is_recursive) {
                    include_dir($file);
                } else {
                    if (strpos($file, '.php') !== false) {
                        include_once($file);
                    }
                }
            }
        }
    }

//    class LicException extends Exception {
//        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
//        {
//            $this->line = $code;
//            $this->file = "LicException.php";
//            parent::__construct($message, $code, $previous);
//        }
//    }

    include_dir(DOC_ROOT . "/functions");
    include_dir(DOC_ROOT . "/class");
    //include_once "/home/aces/panel/ACES2/IPTV/Stream.php";
    include_once DOC_ROOT . "/ACES2/User.php";
    include_once DOC_ROOT . "/ACES2/Process.php";
    include_once DOC_ROOT . "/ACES2/Settings.php";
    include_once DOC_ROOT . "/ACES2/IPTV/Stream.php";
    include_once DOC_ROOT . "/ACES2/IPTV/TMDB/TMDB.php";
    include_dir(DOC_ROOT . "/ACES2");

}