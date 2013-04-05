<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $animePerPage = 20;
  $page = (intval($params['page']) > 0) ? intval($params['page']) : 1;

  $recs = $this->app->recsEngine->recommend($this, $animePerPage * ($page - 1), $animePerPage);
  $animeGroup = new AnimeGroup($this->app, array_map(function($a) {
    return $a['id'];
  }, $recs));
  $predictions = [];
  foreach ($recs as $rec) {
    $predictions[$rec['id']] = $rec['predicted_score'];
  }

  $firstAnime = Anime::first($this->app);
?>
<div class='page-header'>
  <h1>Recommended for you <small>Some series we think you'll like</small></h1>
</div>
<?php echo $firstAnime->view('grid', array('anime' => $animeGroup, 'predictions' => $predictions)); ?>