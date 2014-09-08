<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['anime'] = isset($params['anime']) ? $params['anime'] : new AnimeGroup($this->app, []);
  $params['aliases'] = isset($params['aliases']) ? $params['aliases'] : [];
  $params['predictions'] = isset($params['predictions']) ? $params['predictions'] : [];
  $params['wilsons'] = isset($params['wilsons']) ? $params['wilsons'] : [];
?>
<ul class='item-grid recommendations'>
<?php
  if ($params['anime'] instanceof AnimeGroup) {
    $animeGroup = $params['anime'];
  }
  try {
    foreach ($animeGroup->load('info') as $anime) {
      if ($params['aliases']) {
        $title = $params['aliases'][$anime->id];
      } else {
        $title = $anime->title;
      }
?>
  <li>
<?php 
      try {
?>
    <?php echo $anime->link("show", "<h4>".escape_output($title)."</h4>", Null, True, ['title' => $title, 'data-toggle' => 'tooltip']); ?>
    <?php echo $anime->link("show", $anime->imageTag, Null, True, [/*'title' => $anime->description, 'data-toggle' => 'tooltip', 'data-placement' => 'right'*/]); ?>
    <?php if (isset($params['predictions'][$anime->id])) { ?><p><em>Predicted score: <?php echo round($params['predictions'][$anime->id], 1); ?></em></p><?php } ?>
    <?php if (isset($params['wilsons'][$anime->id])) { ?><p><em>Wilson score: <?php echo round($params['wilsons'][$anime->id], 1); ?></em></p><?php } ?>
<?php
      } catch (DbException $e) {
        $this->app->log_exception($e);
?>
        There's an invalid anime reference here; please notify an administrator to get this fixed!
<?php
      }
?>
  </li>
<?php
    }
  } catch (DbException $e) {
    $this->app->log_exception($e);
?>
  There's an invalid anime reference on this page; please notify an administrator to get this fixed!
<?php
  }
?>
</ul>