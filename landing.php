<?php
  require_once("global/includes.php");
  echo $app->render($app->view('landing'), ['redirect_to' => '', 'container' => False]);
?>