<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  $flippedStatuses = array_flip(statusArray());
  $sections = array_flip(isset($params['sections']) ? $params['sections'] : array_filter($flippedStatuses, function ($a) {
    return $a != 0;
  }));

  $statuses = array_filter($flippedStatuses, function ($a) use ($sections) {
    return isset($sections[$a]);
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