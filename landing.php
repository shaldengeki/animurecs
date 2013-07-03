<?php
  require_once("global/includes.php");
  echo $app->render($app->view('landing'), ['container' => False]);
?>