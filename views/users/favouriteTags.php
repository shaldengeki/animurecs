<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  // get list of user's favourite tags, ordered by regularized average
  // regularize by mean tag mean, weighted by mean number of ratings per tag.
  $tagRatings = [];
  $tags = [];
  $tagTypes = TagType::GetList($this->app);
  foreach ($this->animeList()->uniqueList() as $entry) {
    if (round(floatval($entry['score']), 2) != 0) {
      foreach ($entry['anime']->tags as $tag) {
        if (!isset($tagRatings[$tag->type->id])) {
          $tagRatings[$tag->type->id] = [];
        }
        if (isset($tagRatings[$tag->type->id][$tag->id])) {
          $tagRatings[$tag->type->id][$tag->id][] = round(floatval($entry['score']), 2);
        } else {
          $tagRatings[$tag->type->id][$tag->id] = [round(floatval($entry['score']), 2)];
          $tags[$tag->id] = $tag;
        }
      }
    }
  }
  $tagAverages = [];
  $ratingCounts = [];
  $tagGlobalAverageSum = 0;
  $tagGlobalAverageCount = 0;
  foreach ($tagRatings as $tagTypeID => $typeTags) {
    $tagAverages[$tagTypeID] = [];
    foreach ($typeTags as $tagID => $ratings) {
      $tagMean = array_mean($ratings);
      $tagCount = count($ratings);
      $tagAverages[$tagTypeID][$tagID] = $tagMean;
      $ratingCounts[] = $tagCount;
      $tagGlobalAverageSum += $tagMean;
      $tagGlobalAverageCount += $tagCount;
    }
  }
  $globalAverageWeight = array_mean($ratingCounts);
  $tagGlobalAverage = $tagGlobalAverageCount > 0 ? $tagGlobalAverageSum / $tagGlobalAverageCount : 0;

  foreach ($tagRatings as $tagTypeID => $typeTags) {
    $regAverages = [];
    $numTags = 0;
    foreach ($typeTags as $tagID => $ratings) {
      $numRatings = count($ratings);
      if ($numRatings >= $globalAverageWeight) {
        $regAverages[$tagID] = [(array_sum($ratings) + ($globalAverageWeight * $tagGlobalAverage)) / ($globalAverageWeight + count($ratings)), count($ratings)];
        $numTags++;
      }
    }
    arsort($regAverages);
    $likedTags = array_slice($regAverages, 0, $numTags >= 10 ? 10 : floor($numTags/2), True);
    $hatedTags = array_slice($regAverages, $numTags >= 10 ? -10 : -1 * floor($numTags/2), Null, True);
    asort($hatedTags);
?>
<div class='row'>
  <div class='col-md-6'>
    <div class='page-header'>
      <h3>Favourite <?php echo $tagTypes[$tagTypeID]->pluralName(); ?>:</h3>
    </div>
    <div>
      <ol>
<?php
    foreach ($likedTags as $tagID=>$rating) {
?>
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name, Null, False, ['title' => 'Score: '.round($rating[0], 2).', '.$rating[1].' anime']); ?></li>
<?php
    }
?>
      </ol>
    </div>
  </div>
    <div class='page-header'>
      <h3>Least-favourite <?php echo $tagTypes[$tagTypeID]->pluralName(); ?>:</h3>
    </div>
    <div>
      <ol>
<?php
    foreach ($hatedTags as $tagID=>$rating) {
?>
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name, Null, False, ['title' => 'Score: '.round($rating[0], 2).', '.$rating[1].' anime']); ?></li>
<?php
    }
?>
      </ol>
    </div>
  </div>
</div>
<?php
  }
?>
