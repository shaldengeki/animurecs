<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $resultsPerPage = 24;
  $firstAnime = Anime::first($this->app);

  if ($this->app->user->loggedIn()) {
    $predictedRatings = $this->app->recsEngine->predict($this->app->user, $this->anime()->anime(), 0, count($this->anime()->anime()));
    if (is_array($predictedRatings)) {
      arsort($predictedRatings);
    } else {
      $predictedRatings = $this->anime()->anime();
    }    
    $animePredictions = array_slice($predictedRatings, (intval($this->app->page)-1)*$resultsPerPage, intval($resultsPerPage), True);
    $animeGroup = new AnimeGroup($this->app, array_keys($animePredictions));
  } else {
    $animeGroup = new AnimeGroup($this->app, array_keys(array_slice($this->anime()->anime(), (intval($this->app->page)-1)*$resultsPerPage, intval($resultsPerPage), True)));
    $animePredictions = array();
  }

  $animePages = ceil(count($predictedRatings)/$resultsPerPage);
?>
<h1><?php echo $this->link('show', ($this->type()->id != 1 ? $this->type()->name().":" : "").$this->name()).($this->allow($this->app->user, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>
<?php echo $this->description() ? "<p class='lead'>".escape_output($this->description())."</p>" : "" ?>
<div class='row-fluid'>
  <div class='span2'>
    <h2>Tags:</h2>
    <?php echo $animeGroup->tag_list(); ?>
  </div>
  <div class='span10'>
    <?php echo paginate($this->url("show", Null, array("page" => "")), intval($this->app->page), $animePages); ?>
    <?php echo $firstAnime->view('grid', array('anime' => $animeGroup, 'predictions' => $animePredictions)); ?>
    <?php echo paginate($this->url("show", Null, array("page" => "")), intval($this->app->page), $animePages); ?>
  </div>
</div>