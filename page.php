<?php
define ("PATH", ".");
require_once (__DIR__.'/common.php');
require_once (__DIR__.'/checkSession.php');
require_once (__DIR__.'/classes/PageRouter.php');

$class = PageRouter::route (empty ($defaultClass) ? filter_input (INPUT_GET, 'cl') : $defaultClass);
if (empty ($class))
    {
    $header = "Page Not found";
    $class = $page = false;
    }
else
    {
    include (__DIR__."/ui/$class.php");
    $page = new $class ($accessManager);
    if (empty ($page))
        $header = "Class Not found";
    else
        $header = $page->getHeader ();
    }

$title = $header;
$showHomeLink = true;

/*
?>
<script type="text/javascript">
<!--
    $(function ()
        {
        initializeCommon ($('body'));
        });
-->
</script> 

<?php
*/
if ($page)
    {
    $page->writeHeader ($title);
    $page->render ();
    }
else
    writeHeader ($header);


writeFooter ();
