<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
	header("Location: /feed.php");
} else {
  echo $app->render($app->view('index'));
}
?>