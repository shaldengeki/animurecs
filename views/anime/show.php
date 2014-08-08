<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $newEntry = new AnimeEntry($this->app, Null, ['user' => $this->app->user]);
?>
     <div class='row'>
        <div class='col-md-3 userProfileColumn leftColumn'>
          <ul class='thumbnails avatarContainer'>
            <li class='col-md-12'>
              <div class='img-thumbnail profileAvatar'>
<?php
  if ($this->imagePath() != '') {
?>                <?php echo $this->imageTag(['class' => 'img-rounded', 'alt' => '']); ?>
<?php
  } else {
?>                <img src='/img/anime/blank.png' class='img-rounded' alt=''>
<?php
  }
?>          </div>
            </li>
          </ul>
          <div>
            <h2>Tags:</h2>
            <?php echo $this->view('tagList'); ?>
          </div>
        </div>
        <div class='col-md-9 userProfileColumn rightColumn'>
          <div class='profileUserInfo'>
            <h1>
              <?php echo $this->link('show', $this->title); ?>
              <?php echo $this->allow($this->app->user, "edit") ? "<small>(".$this->link("edit", "edit").")</small>" : ""; ?>
            </h1>

            <ul class="nav nav-tabs">
              <li class="active">
                <a href="#generalInfo" data-toggle="tab">General</a>
              </li>
              <li class="ajaxTab" data-url="<?php echo $this->url('related'); ?>">
                <a href="#relatedAnime" data-toggle="tab">Related</a>
              </li>
              <li class="ajaxTab" data-url="<?php echo $this->url('stats'); ?>">
                <a href="#stats" data-toggle="tab">Stats</a>
              </li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane active' id='generalInfo'>
                <p>
                  <?php echo escape_output($this->description); ?>
                </p>
                <ul class='thumbnails'>
                  <li class='col-md-4'>
                    <p class='lead'>Global Average:</p>
                    <?php echo $this->view('scoreBar', ['score' => $this->ratingAvg()]); ?>
                  </li>
<?php
  if ($this->app->user->loggedIn()) {
?>
                  <li class='col-md-4'>
<?php
    if (!isset($this->app->user->animeList()->uniqueList()[$this->id]) || $this->app->user->animeList()->uniqueList()[$this->id]['score'] == 0) {
      try {
        $userRating = $this->app->recsEngine->predict($this->app->user, $this)[$this->id];
      } catch (CurlException $e) {
        $this->app->logger->err($e->__toString());
        $userRating = 0;
      }
?>
                    <p class='lead'>Predicted score:</p>
                    <?php echo $this->view('scoreBar', ['score' => $userRating]); ?>
<?php
    } else {
      $userRating = $this->app->user->animeList()->uniqueList()[$this->id]['score'];
?>
                    <p class='lead'>You rated this:</p>
                    <?php echo $this->view('scoreBar', ['score' => $userRating]); ?>
<?php
    }
    if ($userRating != 0) {
?>
<p><small>(<?php echo abs(round($userRating - $this->app->user->animeList()->uniqueListAvg(), 2))." points ".($userRating > $this->app->user->animeList()->uniqueListAvg() ? "higher" : "lower")." than your average)"; ?></small></p>
<?php
    }
  } else {
?>
                  <li class='col-md-4'>
                    <p class='lead'>Predicted score:</p>
                    <p>Sign in to view your predicted score!</p>
<?php
  }
?>
                  </li>
<?php /*
                  <li class='col-md-8'>
                    <p class='lead'>Tags:</p>
                    <?php echo $this->view('tagCloud', ['user' => $this->app->user]); ?>
                  </li>
*/ ?>
                </ul>
              </div>
              <div class='tab-pane' id='relatedAnime'>
                <p>Loading...</p>
              </div>
              <div class='tab-pane' id='stats'>
                <p>Loading...</p>
              </div>
            </div>
            <div id='userFeed'>
              <?php echo $this->app->user->view('addEntryInlineForm', ['anime' => $this]); ?>
              <?php echo $this->app->user->view('feed', $params); ?>
          </div>
        </div>
      </div>
    </div>