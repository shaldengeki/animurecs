<?php
include_once("global/includes.php");

if (!$user->loggedIn()) {
	redirect_to(array('location' => '/index.php', 'status' => 'You must be logged in to view recommendations.'));
}

start_html($database, $user, "Animurecs", "Discover", $_REQUEST['status'], $_REQUEST['class']);

echo display_recommendations($recsEngine, $user);

display_footer();
?>