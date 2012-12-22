<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $recs = $this->app->recsEngine->recommend($this);
?>

<h1>Your Recs</h1>
<ul class='item-grid recommendations'>
<?php
  if (is_array($recs)) {
    $animeIDs = [];
    $recScores = [];
    foreach ($recs as $rec) {
      $recScores[intval($rec['id'])] = $rec['predicted_score'];
      $animeIDs[] = $rec['id'];
    }
    $animeGroup = new AnimeGroup($this->app, $animeIDs);
    foreach ($animeGroup->load('info') as $anime) {
?>
  <li><?php echo $anime->link("show", "<h4>".escape_output($anime->title)."</h4>".$anime->imageTag, True, array('title' => $anime->description(True))); ?><p><em>Predicted score: <?php echo round($recScores[$anime->id], 1); ?></em></p></li>
<?php
    }
  }
?>
</ul>
<?php
  if (is_array($recs)) {
    echo tag_list($animeGroup);
  }
?>