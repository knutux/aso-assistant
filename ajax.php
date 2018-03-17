<?php
define ("PATH", ".");
define ("SESSION_CHECK_TYPE", 'json');
require_once (__DIR__.'/common.php');
require_once (__DIR__.'/checkSession.php');
require_once (__DIR__.'/classes/PageRouter.php');

ini_set('memory_limit',(1024*4).'M');

$class = PageRouter::route (empty ($_REQUEST['cl']) ? NULL : $_REQUEST['cl']);
$method = empty ($_REQUEST['action']) ? 'getAjaxModel' : $_REQUEST['action'];
if (empty ($class))
    {
    header("HTTP/1.0 404 Not Found");
    exit;
    }

include (__DIR__."/ui/$class.php");
$page = new $class ($accessManager);
if (empty ($page))
    {
    header("HTTP/1.0 404 Not Found");
    exit;
    }

$model = $page->$method ($_REQUEST);

if (!empty ($_REQUEST['accessor']))
    {
    if (empty ($model->errors))
        $model = $model->{$_REQUEST['accessor']};
    else if (!empty ($model->errors))
        header("HTTP/1.0 500 Server Error");
    }

if (empty ($_REQUEST['debug']))
    header('Content-Type: application/json');
$encoded = json_encode($model, JSON_PRETTY_PRINT);
if (false === $encoded)
    {
    echo "ERROR (".json_last_error()."): ".json_last_error_msg ();
    exit;
    }

echo $encoded;