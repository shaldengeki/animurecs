<?php
require_once("global/includes.php");

if (!$app->user->loggedIn()) {
	redirect_to('/index.php', array('status' => 'You must be logged in to view recommendations.', 'redirect_to' => '/discover/'));
}
$app->render($app->user->view('recommendations'), array('title' => 'Your Recs'));
?>