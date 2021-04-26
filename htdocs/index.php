<?php

    require_once("init.php");

    /*  start session  */
    if (session_id() == '') {
        @session_start();
    }
    /*  parse request  */
    $q = $_GET["q"] ?? "";
    $a = array_filter(explode("/", $q), function ($v) {
        return $v != "..";
    });
    $packages = array_merge(["modules"], array_slice($a, 0, count($a) - 1));
    $name = end($a);
    if ($name == "") {
        $name = "index";
    }
    $a = explode("_", $name);
    foreach ($a as $k => $v) {
        $a[$k] = ucfirst($v);
    }
    $class = implode("\\", array_merge($packages, [implode("", $a)]));
    if (class_exists($class)) {
        $c = new $class();
        exit;
    }
