<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: content-type, Access-Control-Expose-Headers, X-U');
header('Access-Control-Expose-Headers: X-U');

// respond to preflights
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    {
    exit;
    }

require_once (__DIR__.'/common.php');
require_once (__DIR__.'/classes/PageRouter.php');

$accessManager = new AccessManager ();
if (!$accessManager->setConsoleUser (API_USER))
    {
    print "User not recognized\n";
    exit ();
    }

$class = PageRouter::route ('api');
$method = 'getAjaxModel';
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

$model = $page->$method ();

//if (empty ($_REQUEST['debug']))
header('Content-Type: application/json');
$debug = filter_input (INPUT_GET, 'debug');
$encoded = json_encode($model, $debug ? 0 : JSON_PRETTY_PRINT);
if (false === $encoded)
    {
    echo "ERROR (".json_last_error()."): ".json_last_error_msg ();
    exit;
    }

if (!empty ($_REQUEST['uu']))
    {
    $uid = filter_input (INPUT_GET, 'uu');
    $unreadable = strtr($uid, ' !"#$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~',
                              'G?41o9WjhF(MLsmI6S#KfP`:!]AQ/%>nJ7E;8<5N&-Z,k="@gwy)l0eabpXq ctC3H.V~+Dd|_2xTi}YvBrO^*U[uR${z');
    header ('X-U: '.$unreadable);
    }


echo $encoded;
