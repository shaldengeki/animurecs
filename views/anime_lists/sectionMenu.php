<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $flippedStatuses = array_flip(statusArray());
  $params['sections'] = array_flip(isset($params['sections']) ? $params['sections'] : array_filter($flippedStatuses, function ($a) {
    return $a != 0;
  }));

  $statuses = array_filter($flippedStatuses, function ($a) use ($params['sections']) {
    return isset($params['sections'][$a]);
  });
?>
<ul id="sectionMenu" class="nav nav-pills">
<?php
  foreach ($statuses as $text => $status) {
?>
  <li><a href='#<?php echo escape_output(camelCase($text)); ?>'><?php echo escape_output($text); ?></a></li>
<?php
  }
?>
</ul>