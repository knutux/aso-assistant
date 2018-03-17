<?php

define ("VERSION", "0.5.1");

$userConfig = __DIR__.'/user.config.php';

if (!file_exists ($userConfig))
    {
    error_reporting (E_ALL);
    trigger_error ("$userConfig does not exists, using default values. Database connect will probably fail");
    $userConfig .= '.default';
    }

require_once ($userConfig);

function define_default ($const, $val)
    {
    if (!defined ($const))
        define ($const, $val);
    }

define_default ("VERBOSE", false);
define_default ("VERBOSE_SQL", false);

define_default ("SERVER_TIME_ZONE", 'UTC');
define_default ("UI_TIME_ZONE", 'UTC');

define_default ("FIRST_ADMIN", 'admin');
define_default ("FIRST_ADMIN_PWD", false);
