<?php

    $root_path = realpath(__DIR__."/..")."/";
    /**/
    $cfg = [
        /*  paths  */
        "path.root" => $root_path,
        "path.config" => $root_path."config/",
        "path.classes" => $root_path."classes/",
        "path.classes.core" => $root_path."classes/core/",
        "path.classes.modules" => $root_path."classes/modules/",
        "path.htdocs" => $root_path."htdocs/",
        "path.logs" => $root_path."logs/",
        "path.templates" => $root_path."templates/",
        /**/
        "system.name" => "Proxy-checker",
        "page.size" => 25,
        "page.pages" => 9,
        "timezone" => "Europe/Moscow"
    ];

?>
