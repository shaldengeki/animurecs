<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
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
    foreach ($catGroupwatches as $animeID => $groupwatch) {
      usort($groupwatch['users'], function($a, $b) use ($animeID) {
        return ($a->animeList()->uniqueList()[$animeID]['episode'] < $b->animeList()->uniqueList()[$animeID]['episode']) ? 1 : -1;
      });
      $catGroupwatches[$animeID] = $groupwatch; 
    }
    $groupwatches[$category] = $catGroupwatches;
  }
  if ($nonZeroGroupwatches) {
    try {
      $predictedRatings = $this->app->recsEngine->predict($this, $anime, 0, count($anime));
    } catch (CurlException $e) {
      $this->app->logger->err($e->__toString());
      $predictedRatings = [];
    }
  }
  foreach ($groupwatches as $category=>$groupwatchList) {
    usort($groupwatchList, function($a, $b) use ($predictedRatings) {
      if (!isset($predictedRatings[$a['anime']->id])) {
        if (!isset($predictedRatings[$b['anime']->id])) {
          return 0;
        } else {
          return 1;
        }
      } elseif (!isset($predictedRatings[$b['anime']->id])) {
        return -1;
      } else {
        return ($predictedRatings[$a['anime']->id] < $predictedRatings[$b['anime']->id]) ? 1 : -1;
      }
    });
    $groupwatches[$category] = $groupwatchList;
  }
?>
<div class='page-header'>
  <h1>Groupwatches <small>Shows <?php echo $this->currentUser() ? "you have" : escape_output($this->username)." has"; ?> in common with friends</small></h1>
</div>

<div class='center-horizontal'><?php echo $this->animeList()->view('sectionMenu', ['sections' => $groupwatchCategories]); ?></div>

<?php
  if ($nonZeroGroupwatches) {
    foreach ($groupwatchCategories as $category) {
      if ($groupwatches[$category]) {
?>
<div class='groupwatch-category'>
  <h2 id='<?php echo escape_output(camelCase(statusArray()[$category])); ?>'><?php echo escape_output(statusArray()[$category]); ?></h2>
<?php

        foreach ($groupwatches[$category] as $groupwatch) {
?>
  <ul class='media-list'>
    <li class='media groupwatch-entry'>
      <div class='pull-left'>
        <?php echo $groupwatch['anime']->link("show", "<h4>".escape_output($groupwatch['anime']->title)."</h4>", Null, True, ['title' => $groupwatch['anime']->title, 'data-toggle' => 'tooltip', 'data-placement' => 'top']); ?>
        <?php echo $groupwatch['anime']->link("show", $groupwatch['anime']->imageTag(['class' => 'media-object col-md-3']), Null, True, ['title' => $groupwatch['anime']->description, 'data-toggle' => 'tooltip', 'data-placement' => 'right']); ?>
        <p><em>Predicted rating: <?php echo round($predictedRatings[$groupwatch['anime']->id], 2); ?></em></p>
      </div>
      <div class="media-body">
        <ul class='item-grid'>
<?php
          foreach ($groupwatch['users'] as $friend) {
?>
          <li>
            <?php echo $friend->link("show", "<h5>".escape_output($friend->username)."</h5>".$friend->thumbImage(), Null, True); ?>
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