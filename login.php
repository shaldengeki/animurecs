<?php
require_once("global/includes.php");

if (isset($_POST['username'])) {
	$username=$_POST['username']; 
	$password = $_POST['password'];

	$loginResult = $app->user->logIn($username, $password);
  if (isset($loginResult['status'])) {
    $app->delayedMessage($loginResult['status'], isset($loginResult['class']) ? $loginResult['class'] : Null);
  }
	$app->redirect();
}
?>