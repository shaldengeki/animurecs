<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  /*
    TODO: Refactor this logic into the controller.
  */
  $animePerPage = 20;
  $page = (intval($params['page']) > 0) ? intval($params['page']) : 1;

  $recs = [];
  $animeGroup = new AnimeGroup($this->app, []);
  $predictions = [];
  try {
    $recs = $this->app->recsEngine->recommend($this, $animePerPage * ($page - 1), $animePerPage);
  } catch (CurlException $e) {
    $this->app->log_exception($e);
    $recs = False;
  }
  if ($recs) {
    $animeGroup = new AnimeGroup($this->app, array_map(function($a) {
      return $a['id'];
    }, $recs));
    foreach ($recs as $rec) {
      $predictions[$rec['id']] = $rec['predicted_score'];
    }
  }
  $firstAnime = Anime::Get($this->app);
    
?>
<div id='recommendation-content'>
  <div class='page-header'>
    <h1><?php echo $this->currentUser() ? "Recommended for you <small>Some series we think you'll like</small>" : escape_output($this->username)."'s recs <small>Some series we think they'll like</small>"; ?></h1>
  </div>
<?php
  if ($animeGroup->length() < 1) {
?>
    Aww, there are no recommendations for you at the moment. We cook em up every half-hour, so please check back then!
<?php
  } else {
    echo paginate($this->url('recommendations', Null, ['page' => '']), $page, Null, '#recommendation-content');
    echo $firstAnime->view('grid', [
      'group' => $animeGroup, 
      'predictions' => $predictions, 
      'dismiss-url' => $this->url('dismiss_recommendation')
    ]);
    echo paginate($this->url('recommendations', Null, ['page' => '']), $page, Null, '#recommendation-content');
  }
?>
</div>