<?php
include_once("global/includes.php");
if ($user->loggedIn()) {
	header("Location: /feed.php");
}
start_html($database, $user, "Animurecs", "Home", $_REQUEST['status'], $_REQUEST['class']);
?>
<div class="hero-unit">
  <h1>Welcome to Animurecs!</h1>
  <p>This is the rewrite of Animurecs, an anime and manga social networking site.</p>
  <p>
    <a href="/register.php" class="btn btn-info btn-large">
      Sign up
    </a> or <a href="/login.php" class="btn btn-primary btn-large">
      Sign in
    </a>
  </p>
</div>
<?php
display_footer();
?>