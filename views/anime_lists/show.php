<?php
  if ($_SERVER['DOCUMENT_URI'] === $_SERVER['REQUEST_URI']) {
    echo "This partial cannot be viewed on its own.";
    exit;
  }
  $output = $this->view('section', $currentUser, array('status' => 1));
  $output .= $this->view('section', $currentUser, array('status' => 2));
  $output .= $this->view('section', $currentUser, array('status' => 3));
  $output .= $this->view('section', $currentUser, array('status' => 4));
  $output .= $this->view('section', $currentUser, array('status' => 6));
  echo $output;
?>