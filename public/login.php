<?php
require_once("../includes.php");

if (isset($_POST['username'])) {
  $username = rawurldecode($_POST['username']);
  $password = rawurldecode($_POST['password']);

  $loginResult = $app->user->logIn($username, $password);

  $app->redirect();
}
?>