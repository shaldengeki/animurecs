<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $app->check_partial_include(__FILE__);

  $predictedRatings = $app->recsEngine->predict($app->user, $this->anime(), 0, count($this->anime()));
  if (is_array($predictedRatings)) {
    arsort($predictedRatings);
  } else {
    $predictedRatings = $this->anime();
  }

  $resultsPerPage = 24;
  if ($app->user->isAdmin()) {
    $animePage = array_slice($predictedRatings, (intval($app->page)-1)*$resultsPerPage, intval($resultsPerPage), True);
    $animePages = ceil(count($predictedRatings)/$resultsPerPage);
  } else {
    $animePage = array_slice($predictedRatings, (intval($app->page)-1)*$resultsPerPage, intval($resultsPerPage), True);
    $animePages = ceil(count($predictedRatings)/$resultsPerPage);
  }
?>
<h1><?php echo escape_output($this->name()).($this->allow($app->user, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>
<?php echo paginate($this->url("show", array("page" => "")), intval($app->page), $animePages); ?>
  <ul class='recommendations'>
<?php
  foreach ($animePage as $animeID=>$prediction) {
?>
    <li><?php echo $this->anime()[$animeID]->link("show", "<h4>".escape_output($this->anime()[$animeID]->title())."</h4><img src='".joinPaths(Config::ROOT_URL, escape_output($this->anime()[$animeID]->imagePath()))."' />", True, array('title' => $this->anime()[$animeID]->description(True))); echo ($prediction instanceof Anime) ? "" : "<p><em>Predicted score: ".round($prediction, 1)."</em></p>"; ?></li>
<?php
  }
?>
  </ul>
<?php echo paginate($this->url("show", array("page" => "")), intval($app->page), $animePages); ?>
<?php echo tag_list($this->anime()); ?>