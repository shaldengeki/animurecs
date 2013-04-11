<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
	$app->redirect($app->user->url("globalFeed"), $_REQUEST);
} else {
  $app->redirect("/landing.php", $_REQUEST);
  echo $app->render($app->view('index'));
}
?>