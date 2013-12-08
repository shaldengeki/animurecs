<?php
  require_once("../includes.php");
  echo $app->render($app->view('landing'), ['container' => False]);
?>