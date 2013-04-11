<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['tag'] = new Tag($this->app, 0);
?>
<h1>Create a Tag</h1>
<?php echo $this->view("form", $params); ?>