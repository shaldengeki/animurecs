<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstAnime = Anime::first($this->app);
  $numAnimePerPage = 8;
  $params['page'] = (intval($params['page']) > 0) ? intval($params['page']) : 1;
?>

<div id='related-content'>
  <div class='page-header'>
    <h2>Related series:</h2>
  </div>
  <?php echo paginate($this->url('related', Null, ['page' => '']), $params['page'], Null, '#related-content'); ?>
  <?php echo $firstAnime->view('grid', ['anime' => $this->similar(($params['page']- 1) * $numAnimePerPage, $numAnimePerPage)]); ?>
  <?php echo paginate($this->url('related', Null, ['page' => '']), $params['page'], Null, '#related-content'); ?>
</div>