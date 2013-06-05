<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  if (!isset($params['user'])) {
    throw new AppException($this->app, 'No user parameter provided for view: anime/tagCloud');
  }

?>
<ul class='tagCloud'>
<?php
  foreach ($this->tags()->load('info') as $tag) {
?>
  <li class='<?php echo escape_output($tag->type->name); ?>'><p><?php echo $tag->link("show", $tag->name, Null, False, ['title' => "(".$tag->numAnime.")"]); ?></p><?php echo $tag->allow($currentUser, "edit") ? "<span>".$this->link("remove_tag", "×", Null, False, Null, ['tag_id' => $tag->id])."</span>" : ""; ?></li>
<?php
  }
?>
</ul>