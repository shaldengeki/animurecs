<?php
include_once("global/includes.php");
if ($user->loggedIn()) {
	header("Location: /feed.php");
}
start_html($database, $user, "Animurecs", "Home", $_REQUEST['status'], $_REQUEST['class']);
?>
<div class="row-fluid">
  <div class="hero-unit">
    <h1>Welcome to Animurecs!</h1>
    <p>Animurecs is an anime and manga database, built around the idea that watching anime is more fun when you're with friends.</p>
    <p>
      <a href="/register.php" class="btn btn-success btn-large">
        Sign up
      </a>
    </p>
  </div>
</div>
<div class="row-fluid">
  <ul class="thumbnails">
    <li class="span4">
      <div class="thumbnail">
        <div class="caption">
          <h4>Organize your anime</h4>
          <p>
            Ever forgotten where you last left off watching a series? Animurecs can help you keep track of what you've watched and when you watched it.
          </p>
          <p>
            <a href='#' class='btn btn-primary'>Take a tour</a>
          </p>
        </div>
      </div>
    </li>
    <li class="span4">
      <div class="thumbnail">
        <div class="caption">
          <h4>Keep in touch with friends</h3>
          <p>
            Find out what your friends have been watching and what they thought of it. Join in on groupwatches with friends, or compete in contests to earn bragging rights!
          </p>
          <p>
            <a href='#' class='btn btn-info'>Find out more</a>
          </p>
        </div>
      </div>
    </li>
    <li class="span4">
      <div class="thumbnail">
        <div class="caption">
          <h4>Get great recommendations</h3>
          <p>
            Animurecs learns what you like from what you've watched, and tailors its recommendations to your individual tastes so you don't have to go digging yourself.
          </p>
          <p>
            <a href='#' class='btn btn-warning'>Try a demo</a>
          </p>
        </div>
      </div>
    </li>
  </ul>
</div>
<?php
display_footer();
?>