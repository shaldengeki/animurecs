<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstAnime = Anime::first($this->app);
?>
<div class='tabbable tabs-left'>
  <ul class='nav nav-tabs'>
    <li class='active ajaxTab' data-url="<?php echo $this->url('recommendations'); ?>"><a href='#yourRecs' data-toggle='tab'>Your Recs</a></li>
    <li class='ajaxTab' data-url="<?php echo $this->url('friendRecs'); ?>"><a href='#friendRecs' data-toggle='tab'>Friends</a></li>
    <li><?php echo $firstAnime->link("index", "Browse"); ?></li>
  </ul>
  <div class='tab-content'>
    <div class='tab-pane active' id='yourRecs'>
      <?php echo $this->view('recommendations', ['page' => $params['page']]); ?>
    </div>
    <div class='tab-pane' id='friendRecs'>
      Loading...
    </div>
  </div>
</div>