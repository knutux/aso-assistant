<?php

if (!defined ("PATH"))
    define ("PATH", ".");

$startTime = microtime (true);

require_once (__DIR__.'/classes/AccessManager.php');

$accessManager = new AccessManager ();

$redirectTo = $accessManager->checkSession ();

if (!empty ($redirectTo))
    {
    $returnType = defined ("SESSION_CHECK_TYPE") ? SESSION_CHECK_TYPE : "";
    switch ($returnType)
        {
        case "json":
            $data = new StdClass ();
            $data->error = "Session timeout";
            $data->errors = array ($data->error);
            header('Content-type: application/json');
            echo json_encode ($data);
            break;
        case "html":
            echo '<div class="ui-state-error">Session timeout</div>';
            break;
        default:
            $returnTo = empty ($_SERVER["REQUEST_URI"]) ? '' : urlencode ($_SERVER["REQUEST_URI"]);
            header("Location: ".PATH."/$redirectTo?returnTo=$returnTo");
            break;
        }

    exit ();
    }
