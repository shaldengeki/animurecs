<?php
require_once("global/includes.php");

if (isset($_POST['username'])) {
	// username and password sent from form 
	$username=$_POST['username']; 
	$password = $_POST['password'];

	$loginResult = $app->user->logIn($username, $password);
	if (isset($_REQUEST['redirect_to']) && $_REQUEST['redirect_to']) {
		$loginResult[0] = rawurldecode($_REQUEST['redirect_to']);
	}
	$app->redirect($loginResult[0], $loginResult[1]);
}
?>