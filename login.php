<?php
include_once("global/includes.php");

if (isset($_POST['username'])) {
	// username and password sent from form 
	$username=$_POST['username']; 
	$password = $_POST['password'];

	$loginResult = $user->logIn($username, $password);
	if (isset($_REQUEST['redirect_to'])) {
		$loginResult[0] = urldecode($_REQUEST['redirect_to']);
	}
	redirect_to($loginResult[0], $loginResult[1]);
}

start_html($database, $user, "Animurecs", "Sign In", $_REQUEST['status'], $_REQUEST['class']);
echo "<div class='row-fluid' style='text-align: center;'>
	<div class='span12'>
		<h1>Sign In</h1>
";
display_login_form();
echo "	</div>
</div>
";
display_footer();
?>