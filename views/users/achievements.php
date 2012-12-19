<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<ul class='item-grid achievements'>
<?php
  foreach ($this->app->achievements as $id=>$achievement) {
    if ($achievement->alreadyAwarded($this)) {
?>
  <li><h4><?php echo escape_output($achievement->name()); ?></h4><?php echo $achievement->imageTag(); ?><p><em><?php echo escape_output($achievement->description()); ?></em></p></li>
<?php
    }
  }
?>
</ul>