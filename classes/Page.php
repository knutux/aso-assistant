<?php
require_once (__DIR__.'/../common.php');

/*
abstract class Page
    {
    protected $accessManager;
    protected $processor;

    public function __construct ($accessManager)
        {
        $this->accessManager = $accessManager;
        $this->processor = new DBTable ($accessManager);
        }

    function returnError ($error, $cssClass = "error")
        {
        echo '<div class="ui-state-'.$cssClass.' ui-corner-all"><p>'.$error.'</p></div>';
        return false;
        }

    private $actionStartTimes = array ();
    function logActionStart ($action)
        {
        $this->actionStartTimes[$action] = microtime (true);
        }

    function logActionEnd ($action)
        {
        $endTime = microtime (true);
        if (!empty ($this->actionStartTimes[$action]))
            {
            $diff = $endTime - $this->actionStartTimes[$action];
            //print "<pre>Action '$action' took $diff s. (started at {$this->actionStartTimes[$action]})</pre>";
            }
        }
    }
*/