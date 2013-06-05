<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $animePerPage = 20;
  $page = (intval($params['page']) > 0) ? intval($params['page']) : 1;

  $recs = [];
  $animeGroup = new AnimeGroup($this->app, []);
  $predictions = [];
  $recs = $this->app->recsEngine->recommend($this, $animePerPage * ($page - 1), $animePerPage);
  if ($recs) {
    $animeGroup = new AnimeGroup($this->app, array_map(function($a) {
      return $a['id'];
    }, $recs));
    $predictions = [];
    foreach ($recs as $rec) {
      $predictions[$rec['id']] = $rec['predicted_score'];
    }
  }
  $firstAnime = Anime::first($this->app);
    
?>
<div id='recommendation-content'>
  <div class='page-header'>
    <h1>Recommended for you <small>Some series we think you'll like</small></h1>
  </div>
<?php
  if ($animeGroup->length() < 1) {
?>
    Aww, there's no recommendations for you at the moment. We're cook em up every hour, so please check back then!
<?php
  } else {
    echo paginate($this->url('recommendations', Null, ['page' => '']), $page, Null, '#recommendation-content');
    echo $firstAnime->view('grid', ['anime' => $animeGroup, 'predictions' => $predictions]);
    echo paginate($this->url('recommendations', Null, ['page' => '']), $page, Null, '#recommendation-content');
  }
?>
</div>