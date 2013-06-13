<?php
require_once("global/includes.php");
if ($app->user->loggedIn()) {
  header("Location: index.php");
}
if ($app->user->allow($app->user, 'new') && isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_confirmation'])) {
  $registerUser = $app->user->register($_POST['username'], $_POST['email'], $_POST['password'], $_POST['password_confirmation']);
  $location = $registerUser['location'];
  unset($registerUser['location']);
  if (isset($registerUser['status'])) {
    $app->delayedMessage($registerUser['status'], isset($registerUser['class']) ? $registerUser['class'] : Null);
  }
  $app->redirect($location);
} else {
  echo $app->render('<div class="row-fluid">
  <div class="span4">&nbsp;</div>
  <div class="span4">'.$app->user->view('register').'</div>
  <div class="span4">&nbsp;</div></div>');
}
?>