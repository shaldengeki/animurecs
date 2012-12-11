<?php
  if ($_SERVER['DOCUMENT_URI'] === $_SERVER['REQUEST_URI']) {
    echo "This partial cannot be viewed on its own.";
    exit;
  }

  echo $this->feed(array(new CommentEntry($this->dbConn, intval($this->id))), $currentUser);
?>