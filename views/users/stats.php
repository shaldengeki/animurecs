<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class='row-fluid'>
  <div class='span4'>
    <?php echo $this->view('animeRatingDist'); ?>
  </div>
  <div class='span8'>
    <?php echo $this->view('animeStatusDist'); ?>
  </div>
</div>
<div class='row-fluid'>
  <div class='span6'>
    <?php echo $this->view('animeCompletionTimeline'); ?>
  </div>
  <div class='span6'>
    <?php echo $this->view('averageRatingTimeline'); ?>
  </div>
</div>