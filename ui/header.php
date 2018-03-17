<?php

function writeHeader ($pageTitle, $htmlTitle = false, $refreshLength = false, $model = false, $view = false)
    {
    if (empty ($htmlTitle))
        $htmlTitle = $pageTitle;
    $refresh = false !== $refreshLength;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<HTML>
<HEAD>
    <TITLE><?=$htmlTitle?></TITLE>
    <?php
        if ($refresh)
            echo '<meta http-equiv="refresh" content="'.$refreshLength.'">';
    ?>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
    <META NAME="Description" CONTENT="Sample web scrapper project">
    <META NAME="Author" CONTENT="knutux@knutux.com">
    <META NAME="ROBOTS" CONTENT="INDEX, FOLLOW">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <LINK rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
    <LINK rel="stylesheet" href="css/common.css?ver=1.0.5" type="text/css">
    <!-- Latest compiled and minified CSS -->
    <!--link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"-->
    <!-- Optional theme -->
    <!--link rel="stylesheet" href="http://bootswatch.com/simplex/bootstrap.min.css"-->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/dataTables.bootstrap.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="css/bootstrap-editable.css">

    <script language="JavaScript" src="js/require.js" type="text/javascript"></script>
    <!--  <link rel="SHORTCUT ICON" href="/favicon.ico"/> -->
</HEAD>
<BODY>
  <div class="ui-helper-reset">
    <h1 class="sitetitle  ui-corner-all ui-state-highlight ui-widget-shadow"><?=$pageTitle?></h1>

<?php
    if ($model)
        {
?>
    <div class="ui-widget loading-init" id="loading_init">
            <h3>Loading...</h3>
            <div class="progress progress-striped active">
                <div class="progress-bar" style="width: 50%"></div>
            </div>
    </div>
<?php
        }
?>
    <div class="description ui-widget" id="content" <?= empty ($model) ? '' : 'style="display:none"'?>>

<?php
    if (true || $model)
        {
        $jsonModel = json_encode($model);
        $viewModelName = $view ? $view : "ViewModel";

        $files = glob(__DIR__."/../js/*.js");
        $files = array_combine($files, array_map("filemtime", $files));
        arsort($files);

        $latest_file = array_shift ($files);
?>
<script type="text/javascript">
<!--
require.config ({    urlArgs: "bust=v1.0.7.<?=$latest_file?>" });
require(['js/init'], function () {
    require( [
        'knockout',
        'knockout.mapping',
        'jquery',
        'jqueryui',
        'bootstrap',
        'datatables',
        "datatables-bootstrap",
        "timeago",
        "datepicker",
        "ko.command",
        <?=empty ($view) ? '' : "'js/$viewModelName',"?>

    ], function (ko, komapping, jquery, jqueryui, bootstrap, dt, dtB, timeago, datepicker, kocmd <?=empty ($view) ? '' : ", $viewModelName" ?>) {
        function ViewModel (data){
            self = this;
            self.model = komapping.fromJS(data);
        }
        ViewModel.prototype.initialize = function ()
            {
            }

        $(function ()
            {
            try
                {
                var data = <?=$jsonModel?>;
                var vm = new <?=$viewModelName?>(data);
                $('#loading_init').hide();
                ko.applyBindings(vm.model);
                $('#content').show();
                vm.initialize (data);
                }
            catch (err)
                {
                $('#content').show().html (err.toString());
                }
            });
        });
});
-->
</script> 
<?php
        }
    }