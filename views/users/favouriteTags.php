<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // get list of user's favourite tags, ordered by regularized average
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
  foreach ($tagRatings as $tagID=>$ratings) {
    $tagAverages[$tagID] = array_mean($ratings);
  }
  $tagGlobalAverage = array_mean($tagAverages);
  $tagRegularizedAverages = [];
  foreach ($tagRatings as $tagID=>$ratings) {
    $tagRegularizedAverages[$tagID] = (array_sum($ratings) + (5 * $tagGlobalAverage)) / (5 + count($ratings));
  }
  arsort($tagRegularizedAverages);
  $likedTags = array_slice($tagRegularizedAverages, 0, 10, True);
  $hatedTags = array_slice($tagRegularizedAverages, -10, Null, True);
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
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name(), Null, False, ['title' => round($rating, 2)]); ?></li>
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
    <li><?php echo $tags[$tagID]->link('show', $tags[$tagID]->name(), Null, False, ['title' => round($rating, 2)]); ?></li>
<?php
  }
?>
      </ol>
    </div>
  </div>
</div>