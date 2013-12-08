<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<h1>Editing <?php echo escape_output($this->title); ?></h1>
<?php echo $this->view("form", $params); ?>