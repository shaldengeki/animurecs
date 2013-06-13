<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
  $firstUser = User::first($this);
?>
    <div class='landing'>
      <div class="row-fluid landing-banner">
        <div class="landing-container">
          <h1>Intelligent anime recommendations.</h1>
          <h2>Find great shows without the hassle.</h2>
        </div>
      </div>
      <div class="row-fluid landing-container">
        <div class="landing-box">
          <p class='lead'>Track your anime progress. Never have to worry about forgetting what episode you're on.</p>
          <?php echo $firstUser->link('show', "<img src='img/animurecs-feed-comment.png' alt='Activity feed with comments' />", Null, True); ?>
        </div>
        <div class="landing-box">
          <p class='lead'>Get great recommendations. Without having to dig through mountains of shows you won't like.</p>
          <?php echo $firstUser->link('discover', "<img src='img/animurecs-anime-recommendations.png' alt='Recommendations based on your tastes' />", Null, True); ?>
        </div>
        <div class="landing-box">
          <p class='lead'>Stay current with friends. Find and join groupwatches effortlessly.</p>
          <a href='/users/shaldengeki/discover#groupwatches' ><img src='img/animurecs-groupwatches.png' alt='List of potential groupwatches with friends' /></a>
        </div>
      </div>
      <div class='row-fluid landing-container'>
        <p class='lead'>Sign up for Animurecs for free: <a class='btn btn-large btn-success' href='/register.php'>Sign up</a></p>
      </div>
    </div>