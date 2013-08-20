<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class='row'>
  <div class='col-md-12'>
    <?php echo $this->view('statusDist'); ?>
  </div>
</div>
<div class='row'>
  <div class='col-md-6'>
    <?php echo $this->view('averageRatingTimeline'); ?>
  </div>
  <div class='col-md-6'>
    <?php echo $this->view('wilsonScoreTimeline'); ?>
  </div>
</div>