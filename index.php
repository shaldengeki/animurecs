<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
	redirect_to("feed.php", $_REQUEST);
} else {
  echo $app->render($app->view('index'));
}
?>