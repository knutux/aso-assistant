<?php

require_once (__DIR__.'/config.php');

spl_autoload_register(function ($class)
    {
    /*if (preg_match ('#Script#', $class))
        require_once __DIR__.'/classes/scripts/'.$class.'.php';
*/
    $path = __DIR__.'/ui/'.$class.'.php';
    if (is_file ($path))
        {
        require_once $path;
        return;
        }

    $path = __DIR__.'/classes/'.$class.'.php';
    if (is_file ($path))
        {
        require_once $path;
        return;
        }
    });

require_once (__DIR__.'/ui/header.php');
require_once (__DIR__.'/ui/footer.php');
require_once (__DIR__.'/utf8helpers.php');
