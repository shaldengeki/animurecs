<?php
require_once("global/includes.php");

if (isset($_POST['username'])) {
	$username=$_POST['username']; 
	$password = $_POST['password'];

	$loginResult = $app->user->logIn($username, $password);
	$app->redirect();
}
?>