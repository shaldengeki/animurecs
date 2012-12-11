<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);
?>
<h1><?php echo escape_output($this->name()).($this->allow($currentUser, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>