<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['anime'] = new Anime($this->app, 0);
?>
<h1>Add an anime</h1>
<?php echo $this->view("form", $params); ?>