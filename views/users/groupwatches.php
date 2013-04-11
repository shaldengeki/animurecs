<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // get list of anime that this user and at least one other friend have on their list in the same category.
  $groupwatchCategories = [1, 6];
  $groupwatches = [];
  $nonZeroGroupwatches = False;
  $anime = [];
  foreach ($groupwatchCategories as $category) {
    $catGroupwatches = [];
    $ourSeries = array_keys($this->animeList()->listSection($category));
    foreach ($this->friends() as $friend) {
      $friendSeries = array_keys($friend['user']->animeList()->listSection($category));
      $intersect = array_intersect($ourSeries, $friendSeries);
      if ($intersect) {
        foreach ($intersect as $animeID) {
          if (!isset($catGroupwatches[$animeID])) {
            $anime[$animeID] = new Anime($this->app, $animeID);
            $catGroupwatches[$animeID] = ['anime' => $anime[$animeID], 'users' => [$friend['user']]];
          } else {
            $catGroupwatches[$animeID]['users'][] = $friend['user'];
          }
        }
      }
    }
    $nonZeroGroupwatches = $nonZeroGroupwatches || $catGroupwatches;
    $groupwatches[$category] = $catGroupwatches;
  }
  if ($nonZeroGroupwatches) {
    $predictedRatings = $this->app->recsEngine->predict($this, $anime, 0, count($anime));
  }
?>
<div class='page-header'>
  <h1>Groupwatches <small>Shows you have in common with friends</small></h1>
</div>

<?php
  if ($nonZeroGroupwatches) {
    foreach ($groupwatchCategories as $category) {
      if ($groupwatches[$category]) {
?>
<div class='groupwatch-category'>
  <h2><?php echo escape_output(statusArray()[$category]); ?></h2>
<?php

        foreach ($groupwatches[$category] as $groupwatch) {
?>
  <ul class='media-list'>
    <li class='media groupwatch-entry'>
      <?php echo $groupwatch['anime']->link("show", "<h4>".escape_output($groupwatch['anime']->title())."</h4>".$groupwatch['anime']->imageTag(['class' => 'media-object span3'])."<p><em>Predicted rating: ".round($predictedRatings[$groupwatch['anime']->id], 2)."</em></p>", Null, True, ['class' => 'pull-left', 'title' => $groupwatch['anime']->description, 'data-toggle' => 'tooltip', 'data-placement' => 'right']); ?>
      <div class="media-body">
        <ul class='item-grid'>
<?php
          foreach ($groupwatch['users'] as $friend) {
?>
          <li>
            <?php echo $friend->link("show", "<h5>".escape_output($friend->username())."</h5>".$friend->avatarImage(), Null, True); ?>
            <?php echo "<p><em>".($friend->animeList()->uniqueList()[$groupwatch['anime']->id]['episode'] ? "On episode ".intval($friend->animeList()->uniqueList()[$groupwatch['anime']->id]['episode'])."" : "Hasn't started")."</em></p>"; ?>
          </li>
<?php        
          }
?>
        </ul>
      </div>
    </li>
  </ul>
<?php
        }
?>
</div>
<?php
      }
    }
  } else {
?>
  We couldn't find any potential groupwatches for you. Maybe it's time to meet some new people or add some anime to your list!
<?php
  }
?>