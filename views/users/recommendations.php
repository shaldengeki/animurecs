<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $recs = $this->app->recsEngine->recommend($this);
  $animeGroup = new AnimeGroup($this->app, array_map(function($a) {
    return $a['id'];
  }, $recs));
  $predictions = [];
  foreach ($recs as $rec) {
    $predictions[$rec['id']] = $rec['predicted_score'];
  }

  $blankAnime = new Anime($this->app, 0);
?>
<div class='page-header'>
  <h1>Recommended for you <small>Some series we think you'll like</small></h1>
</div>
<?php echo $blankAnime->view('grid', array('anime' => $animeGroup, 'predictions' => $predictions)); ?>