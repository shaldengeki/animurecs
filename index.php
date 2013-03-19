<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
	$app->redirect($app->user->url("globalFeed"), $_REQUEST);
} else {
  echo $app->render($app->view('index'));
}
?>