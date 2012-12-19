<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $predictedRatings = $this->app->recsEngine->predict($this->app->user, $this->anime(), 0, count($this->anime()));
  if (is_array($predictedRatings)) {
    arsort($predictedRatings);
  } else {
    $predictedRatings = $this->anime();
  }

  $resultsPerPage = 24;
  $animePredictions = array_slice($predictedRatings, (intval($this->app->page)-1)*$resultsPerPage, intval($resultsPerPage), True);
  $animeGroup = new AnimeGroup($this->app, array_keys($animePredictions));
  $animeGroup->info();
  $animePages = ceil(count($predictedRatings)/$resultsPerPage);
?>
<h1><?php echo escape_output(($this->type()->id != 1 ? $this->type()->name().":" : "").$this->name()).($this->allow($this->app->user, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>
<?php echo $this->description() ? "<p class='lead'>".escape_output($this->description())."</p>" : "" ?>
<?php echo paginate($this->url("show", array("page" => "")), intval($this->app->page), $animePages); ?>
  <ul class='recommendations'>
<?php
  foreach ($animeGroup->anime() as $anime) {
?>
    <li><?php echo $anime->link("show", "<h4>".escape_output($anime->title())."</h4>".$anime->imageTag(), True, array('title' => $anime->description(True))); echo ($animePredictions[$anime->id] instanceof Anime) ? "" : "<p><em>Predicted score: ".round($animePredictions[$anime->id], 1)."</em></p>"; ?></li>
<?php
  }
?>
  </ul>
<?php echo paginate($this->url("show", array("page" => "")), intval($this->app->page), $animePages); ?>
<?php echo tag_list($animeGroup); ?>