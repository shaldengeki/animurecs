<?php
require_once("global/includes.php");

if (isset($_POST['username'])) {
	$username=$_POST['username']; 
	$password = $_POST['password'];

	$loginResult = $app->user->logIn($username, $password);
	if (isset($_REQUEST['redirect_to']) && $_REQUEST['redirect_to']) {
		$loginResult['location'] = rawurldecode($_REQUEST['redirect_to']);
	}
  if (isset($loginResult['status'])) {
    $app->delayedMessage($loginResult['status'], isset($loginResult['class']) ? $loginResult['class'] : Null);
  }
	$app->redirect($loginResult['location']);
}
?>