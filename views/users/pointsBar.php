<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<p>Points:</p>
<?php
  if ($this->points() === Null || $this->points() == 0) {
?>
<div class='progress progress-info'><div class='bar' style='width: 0%'></div>0</div>
<?php
  } else {
    if ($this->app->totalPoints() == 0) {
      $totalPoints = $this->app->totalPoints() ? $this->app->totalPoints() : 1;
    } else {
      $totalPoints = $this->app->totalPoints();
    }
    $pointRatio = $this->points() * 1.0 / $totalPoints;
    if ($pointRatio >= .75) {
      $barClass = "danger";
    } elseif ($pointRatio >= .5) {
      $barClass = "warning";
    } elseif ($pointRatio >= .25) {
      $barClass = "success";
    } else {
      $barClass = "info";
    }
?>
<div class='progress progress-<?php echo $barClass; ?>'><div class='bar' style='width: <?php echo round($pointRatio*100.0); ?>%'><?php echo $this->points(); ?></div></div>
<?php
  }
?>
