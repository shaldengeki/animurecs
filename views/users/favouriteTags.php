<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  $params['favoriteTags'] = isset($params['favoriteTags']) ? $params['favoriteTags'] : [];

  $tagTypes = TagType::GetList($this->app);
  foreach ($params['favoriteTags'] as $tagTypeID => $tagLists) {
?>
<div class='row'>
  <div class='col-md-6'>
    <div class='page-header'>
      <h3>Favourite <?php echo $tagTypes[$tagTypeID]->pluralName(); ?>:</h3>
    </div>
    <div>
      <ol>
<?php
    foreach ($tagLists['liked'] as $tagInfo) {
?>
    <li><?php echo $tagInfo['tag']->link('show', $tagInfo['tag']->name, Null, False, ['title' => 'Score: '.round($tagInfo['rating'], 2).', '.$tagInfo['count'].' anime']); ?></li>
<?php
    }
?>
      </ol>
    </div>
  </div>
    <div class='page-header'>
      <h3>Least-favourite <?php echo $tagTypes[$tagTypeID]->pluralName(); ?>:</h3>
    </div>
    <div>
      <ol>
<?php
    foreach ($tagLists['hated'] as $tagInfo) {
?>
    <li><?php echo $tagInfo['tag']->link('show', $tagInfo['tag']->name, Null, False, ['title' => 'Score: '.round($tagInfo['rating'], 2).', '.$tagInfo['count'].' anime']); ?></li>
<?php
    }
?>
      </ol>
    </div>
  </div>
</div>
<?php
  }
?>
