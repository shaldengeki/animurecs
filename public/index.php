<?php
require_once("../includes.php");
if ($app->user->loggedIn()) {
	$app->redirect($app->user->url("globalFeed"));
} else {
  $app->redirect("./landing.php");
  // echo $app->render($app->view('index'));
}
?>