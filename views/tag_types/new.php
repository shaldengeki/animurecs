<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['tagType'] = new TagType($this->app, 0);
?>
<h1>Add a tag type</h1>
<?php echo $this->view("form", $params); ?>