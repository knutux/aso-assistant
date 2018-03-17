<?php

require_once (__DIR__.'/common.php');
$debug = false;

$accessManager = new AccessManager ();

$model = new StdClass;
$model->error = NULL;
$sessionCheck = $accessManager->checkSession (false, $debug);
if ($debug) var_dump ("isActive='$sessionCheck'", $_POST);

if (!$sessionCheck)
    {
    if (isset ($_POST["email"]) && isset ($_POST["password"]))
        {
        $sessionCheck = $accessManager->login ($_POST["email"], $_POST["password"], $model->error, $debug);
        if ($debug) var_dump ("login result='$sessionCheck'; error={$model->error}");
        }
    else if ($debug) var_dump ("no email or password");
    }

if ($sessionCheck)
    {
    // already logged in
    $returnTo = empty ($_REQUEST['returnTo']) ? "index.php" : $_REQUEST['returnTo'];
    if ($debug)
        echo "return to - $returnTo";
    else
        header("Location: $returnTo");
    exit ();
    }

$model->email = empty ($_POST["email"]) ? "" : $_POST["email"];
$model->formUrl = $_SERVER["REQUEST_URI"];

$header = $title = "Login";
$hideLogout = true;
writeHeader (false, false, false, $model);

?>
<div class="container">
    <form class="form-signin" data-bind="attr: { action: formUrl }" method="POST" enctype="application/x-www-form-urlencoded">
        <h2 class="form-signin-heading">Please sign in</h2> 
    <div data-bind="if: error">
        <div class="alert alert-dismissible alert-danger" data-bind="text: error"></div>
    </div>
        <label for="inputEmail" class="sr-only">Email address</label>
        <input type="text" name="email" id="inputEmail" data-bind="value:email" class="form-control" placeholder="Email address" required autofocus>
        <label for="inputPassword" class="sr-only">Password</label>
        <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
        <!--div class="checkbox">
          <label>
            <input type="checkbox" value="remember-me"> Remember me
          </label>
        </div-->
        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
    </form>
</div>
<?php
writeFooter ();
