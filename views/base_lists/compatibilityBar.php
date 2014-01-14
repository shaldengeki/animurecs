<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['compatibility'] = isset($params['compatibility']) ? $params['compatibility'] : 0;

  if (!isset($params['compatibility'])) {
    $params['compatibility'] = 0;
    $barText = "Unknown";
  } else {
    $params['compatibility'] = round($params['compatibility']);
    $barText = $params['compatibility']."%";
  }

  if ($params['compatibility'] >= 75) {
    $barClass = "danger";
  } elseif ($params['compatibility'] >= 50) {
    $barClass = "warning";
  } elseif ($params['compatibility'] >= 25) {
    $barClass = "success";
  } else {
    $barClass = "info";
  }
?>
<div class='progress'>
  <div class='progress-bar progress-bar-<?php echo $barClass; ?>' role='progressbar' aria-valuenow="<?php echo $params['compatibility']; ?>" aria-valuemin="0" aria-valuenow="100" style='width: <?php echo $params['compatibility']; ?>%'>
    <span><?php echo $barText; ?></span>
  </div>
</div>