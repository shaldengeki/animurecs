<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);
  $output = $this->view('section', $currentUser, array('status' => 1));
  $output .= $this->view('section', $currentUser, array('status' => 2));
  $output .= $this->view('section', $currentUser, array('status' => 3));
  $output .= $this->view('section', $currentUser, array('status' => 4));
  $output .= $this->view('section', $currentUser, array('status' => 6));
  echo $output;
?>