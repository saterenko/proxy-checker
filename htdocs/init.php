<?php

    require_once(__DIR__ . "/../config/config.php");

    date_default_timezone_set($cfg["timezone"]);
    mb_internal_encoding("UTF-8");
    /*  set class loader  */
    spl_autoload_register(function ($class) {
        global $cfg;
        $a = explode("\\", $class);
        $file = implode("/", array_merge(array_slice($a, 0, count($a) - 1), [strtolower(implode("_", array_filter(preg_split('/(?=[A-Z])/', end($a)), function($v) {return $v != "";}))).".php"]));
        if (file_exists($cfg["path.classes"].$file)) {
            require_once($cfg["path.classes"].$file);
        } else {
            trigger_error("class $class not found");
        }
    });
    /*  set error handler  */
    set_error_handler(function ($errno, $error, $file, $line) {
        global $cfg;
        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 8192);
        }
        if (!defined('E_USER_DEPRECATED')) {
            define('E_USER_DEPRECATED', 16384);
        }
        $message = date("[Y-m-d H:i:s] ");
        switch ($errno) {
            case E_USER_ERROR:
                $message .= "error ";
                break;
            case E_USER_WARNING:
                $message .= "warn ";
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $message .= "notice ";
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return;
                $message .= "deprecated ";
                break;
        }
        $message .= $error." in ".$file.":".$line."\n";
        error_log($message, 3, $cfg["path.logs"]."app.log");
        if (ini_get("display_errors") && defined("SHOW_ERRORS") && SHOW_ERRORS == 1) {
            print(nl2br($message));
        }
    });
    /**/
    session_start();

