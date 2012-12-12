<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);
?>
<h1><?php echo escape_output($this->name()).($this->allow($currentUser, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>
  <ul class='recommendations'>
<?php
  $predictedRatings = $params['recsEngine']->predict($currentUser, $this->anime());
  if (is_array($predictedRatings)) {
    echo "SIP";
    arsort($predictedRatings);
    foreach ($predictedRatings as $animeID=>$prediction) {
?>
    <li><?php echo $this->anime()[$animeID]->link("show", "<h4>".escape_output($this->anime()[$animeID]->title)."</h4><img src='".joinPaths(Config::ROOT_URL, escape_output($this->anime()[$animeID]->imagePath))."' />", True, array('title' => $this->anime()[$animeID]->description(True)))."<p><em>Predicted score: ".round($prediction, 1); ?></em></p></li>
<?php
    }
  } else {
    $i = 0;
    foreach ($this->anime() as $anime) {
?>
    <li><?php echo $anime->link("show", "<h4>".escape_output($anime->title())."</h4><img src='".joinPaths(Config::ROOT_URL, escape_output($anime->imagePath()))."' />", True, array('title' => $anime->description(True))); ?></li>
<?php
      $i++;
      if ($i >= 20) {
        break;
      }
    }
  }
?>
  </ul>
<?php echo tag_list($this->anime()); ?>