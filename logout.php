<?php
require_once("global/includes.php");
if (!$app->user->loggedIn()) {
header("Location: index.php");
}
$_SESSION = array();
session_destroy();
$app->redirect("index.php", array('status' => "You've been logged out. See you soon!", 'class' => 'success'));

?>