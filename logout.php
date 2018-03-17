<?php
//start the session
session_start();

//check to make sure the session variable is registered
if (isset($_SESSION['email']))
    {

    //session variable is registered, the user is ready to logout
    session_unset();
    session_destroy();

    $returnTo = empty ($_REQUEST['returnTo']) ? "index.php" : $_REQUEST['returnTo'];
    header( "Location: ./login.php?returnTo=$returnTo" );
    echo "<p align='center'><strong>you are now logged out</strong></p>";
    echo "<p align='center'>Click <a href='login.php?returnTo=$returnTo'>here</a> to return to home and login again</p>";

    }
else
    {
        //the session variable isn't registered, the user shouldn't even be on this page
        header( "Location: ./index.php" );
    }
