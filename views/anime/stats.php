<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class='row-fluid'>
  <div class='span12'>
    <?php echo $this->view('statusDist'); ?>
  </div>
</div>
<div class='row-fluid'>
  <div class='span6'>
    <?php echo $this->view('averageRatingTimeline'); ?>
  </div>
  <div class='span6'>
    <?php echo $this->view('wilsonScoreTimeline'); ?>
  </div>
</div>