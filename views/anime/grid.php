<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['anime'] = isset($params['anime']) ? $params['anime'] : new AnimeGroup($this->app, []);
  $params['predictions'] = isset($params['predictions']) ? $params['predictions'] : [];
?>
<ul class='item-grid recommendations'>
<?php
  if ($params['anime'] instanceof AnimeGroup) {
    $animeGroup = $params['anime'];
  }
  try {
    foreach ($animeGroup->load('info') as $anime) {
?>
  <li>
    <?php echo $anime->link("show", "<h4>".escape_output($anime->title)."</h4>", Null, True, ['title' => $anime->title, 'data-toggle' => 'tooltip']); ?>
    <?php echo $anime->link("show", $anime->imageTag, Null, True, ['title' => $anime->description, 'data-toggle' => 'tooltip', 'data-placement' => 'right']); ?>
    <?php if (isset($params['predictions'][$anime->id])) { ?><p><em>Predicted score: <?php echo round($params['predictions'][$anime->id], 1); ?></em></p><?php } ?>
  </li>
<?php
    }
  } catch (DbException $e) {
    $this->app->logger->err($e->__toString());
?>
  There's an invalid anime reference on this anime's page; please notify an administrator to get this fixed!
<?php
  }
?>
</ul>