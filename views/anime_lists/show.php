<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $output = $this->view('section', array('status' => 1));
  $output .= $this->view('section', array('status' => 2));
  $output .= $this->view('section', array('status' => 3));
  $output .= $this->view('section', array('status' => 4));
  $output .= $this->view('section', array('status' => 6));
  echo $output;
?>