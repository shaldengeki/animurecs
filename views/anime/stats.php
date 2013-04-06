<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  echo $this->view('averageRatingTimeline');
  echo $this->view('wilsonScoreTimeline');
?>