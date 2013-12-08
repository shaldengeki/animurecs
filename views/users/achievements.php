<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<ul class='item-grid achievements'>
<?php
  $noAchieves = True;
  foreach ($this->app->achievements as $id=>$achievement) {
    if ($achievement->alreadyAwarded($this)) {
      $noAchieves = False;
?>
  <li><?php echo $achievement->imageTag(); ?><p><em><?php echo escape_output($achievement->description); ?></em></p></li>
<?php
    }
  }
  if ($noAchieves) {
?>
  <blockquote>No achievements yet! Try adding some anime to your list, or filling out your profile!</blockquote>
<?php
  }
?>
</ul>