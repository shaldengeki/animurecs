<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<p>Points:</p>
<?php
  if ($this->points === Null || $this->points == 0) {
    $points = intval($this->points);
  } else {
    $points = $this->points;
  }

  if ($this->app->totalPoints() == 0) {
    $totalPoints = $this->app->totalPoints() ? $this->app->totalPoints() : 1;
  } else {
    $totalPoints = $this->app->totalPoints();
  }
  $pointRatio = $points * 1.0 / $totalPoints;
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
<div class='progress'>
  <div class='progress-bar progress-bar-<?php echo $barClass; ?>' role='progressbar' aria-valuenow="<?php echo round($pointRatio*100); ?>" aria-valuemin="0" aria-valuenow="100" style='width: <?php echo round($pointRatio*100); ?>%'>
    <span><?php echo $points; ?></span>
  </div>
</div>