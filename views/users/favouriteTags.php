<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // get list of user's favourite tags, ordered by regularized average
  // regularize by mean tag mean, weighted by mean number of ratings per tag.
  $tagRatings = [];
  $tags = [];
  foreach ($this->animeList()->uniqueList() as $entry) {
    if (round(floatval($entry['score']), 2) != 0) {
      foreach ($entry['anime']->tags() as $tag) {
        if (isset($tagRatings[$tag->id])) {
          $tagRatings[$tag->id][] = round(floatval($entry['score']), 2);
        } else {
          $tagRatings[$tag->id] = [round(floatval($entry['score']), 2)];
          $tags[$tag->id] = $tag;
        }
      }
    }
  }
  $tagAverages = [];
  $ratingCounts = [];
  foreach ($tagRatings as $tagID=>$ratings) {
    $tagAverages[$tagID] = array_mean($ratings);
    $ratingCounts[] = count($ratings);
  }
  $globalAverageWeight = array_mean($ratingCounts);
  $tagGlobalAverage = array_mean($tagAverages);
  $tagRegularizedAverages = [];
  foreach ($tagRatings as $tagID=>$ratings) {
    $numRatings = count($ratings);
    if ($numRatings >= $globalAverageWeight) {
      $tagRegularizedAverages[$tagID] = [(array_sum($ratings) + ($globalAverageWeight * $tagGlobalAverage)) / ($globalAverageWeight + count($ratings)), count($ratings)];
    }
  }
  arsort($tagRegularizedAverages);
  $numTags = count($tagRegularizedAverages);
  $likedTags = array_slice($tagRegularizedAverages, 0, $numTags >= 10 ? 10 : floor($numTags/2), True);
  $hatedTags = array_slice($tagRegularizedAverages, $numTags >= 10 ? -10 : -1 * floor($numTags/2), Null, True);
  asort($hatedTags);
?>
<div class='row-fluid'>
  <div class='span6'>
    <div class='page-header'>
      <h3>Favourite Tags:</h3>
    </div>
    <div>
      <ol>
<?php
  foreach ($likedTags as $tagID=>$rating) {
?>
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name(), Null, False, ['title' => 'Score: '.round($rating[0], 2).', '.$rating[1].' anime']); ?></li>
<?php
  }
?>
      </ol>
    </div>
  </div>
    <div class='page-header'>
      <h3>Least-favourite Tags:</h3>
    </div>
    <div>
      <ol>
<?php
  foreach ($hatedTags as $tagID=>$rating) {
?>
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name(), Null, False, ['title' => 'Score: '.round($rating[0], 2).', '.$rating[1].' anime']); ?></li>
<?php
  }
?>
      </ol>
    </div>
  </div>
</div>