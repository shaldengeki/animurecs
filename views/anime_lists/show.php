<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $output = $this->view('sectionMenu');
  $output .= $this->view('section', ['status' => 1, 'dates' => $params['dates'][1]]);
  $output .= $this->view('section', ['status' => 2, 'dates' => $params['dates'][2]]);
  $output .= $this->view('section', ['status' => 3, 'dates' => $params['dates'][3]]);
  $output .= $this->view('section', ['status' => 4, 'dates' => $params['dates'][4]]);
  $output .= $this->view('section', ['status' => 6, 'dates' => $params['dates'][6]]);
  echo $output;
?>