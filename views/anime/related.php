<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstAnime = Anime::first($this->app);

  $params['anime'] = isset($params['anime']) ? $params['anime'] : [];
  $params['numPerPage'] = isset($params['numPerPage']) ? intval($params['numPerPage']) : 8;
  $params['page'] = isset($params['page']) && intval($params['page']) > 0 ? intval($params['page']) : 1;
?>

<div id='related-content'>
  <?php echo paginate($this->url('related', Null, ['page' => '']), $params['page'], Null, '#related-content'); ?>
  <?php echo $firstAnime->view('grid', ['anime' => $params['anime']]); ?>
  <?php echo paginate($this->url('related', Null, ['page' => '']), $params['page'], Null, '#related-content'); ?>
</div>