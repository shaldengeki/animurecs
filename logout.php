<?php
include_once("global/includes.php");
if (!$user->loggedIn()) {
header("Location: index.php");
}
session_destroy();
redirect_to(array('location' => "index.php", 'status' => "Logged out successfully.", 'class' => 'success'));

?>