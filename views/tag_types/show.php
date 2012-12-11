<?php
  if ($_SERVER['DOCUMENT_URI'] === $_SERVER['REQUEST_URI']) {
    echo "This partial cannot be viewed on its own.";
    exit;
  }
?>
<h1><?php echo escape_output($this->name()).($this->allow($currentUser, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : ""); ?></h1>