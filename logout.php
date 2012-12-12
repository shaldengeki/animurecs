<?php
require_once("global/includes.php");
if (!$app->user->loggedIn()) {
header("Location: index.php");
}
session_destroy();
redirect_to("index.php", array('status' => "You've been logged out. See you soon!", 'class' => 'success'));

?>