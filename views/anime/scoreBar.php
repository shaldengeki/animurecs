<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['score'] = isset($params['score']) ? $params['score'] : 0;

  // returns markup for a score bar for a score given to this anime.
  if ($params['score'] === Null) {
    $params['score'] = 0;
  }
  if ($params['score'] >= 7.5) {
    $barClass = "danger";
  } elseif ($params['score'] >= 5.0) {
    $barClass = "warning";
  } elseif ($params['score'] >= 2.5) {
    $barClass = "success";
  } else {
    $barClass = "info";
  }
?>
<div class='progress progress-<?php echo $barClass; ?>'>
  <div class='bar' style='width: <?php echo round($params['score']*10.0); ?>%'><?php echo round($params['score'], 1); ?>/10</div>
</div>