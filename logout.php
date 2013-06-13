<?php
require_once("global/includes.php");
if (!$app->user->loggedIn()) {
header("Location: index.php");
}
if ($app->user->logOut()) {
  $app->delayedMessage("You've been logged out. See you soon!", "success");
  $app->redirect("index.php");
} else {
  $app->delayedMessage("An error occurred while logging you out. Please try again!", "error");
  $app->redirect($app->user->url('globalFeed'));
}

?>