<?php
require_once("global/includes.php");
if (!$app->user->loggedIn()) {
header("Location: index.php");
}
if ($app->user->logOut()) {
  $app->redirect("index.php", ['status' => "You've been logged out. See you soon!", 'class' => 'success']);
} else {
  $app->redirect($app->user->url('globalFeed'), ['status' => "An error occurred while logging you out. Please try again!", 'class' => 'error']);
}

?>