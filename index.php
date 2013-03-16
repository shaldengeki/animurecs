<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
	redirect_to($app->user->url("globalFeed"), $_REQUEST);
} else {
  echo $app->render($app->view('index'));
}
?>