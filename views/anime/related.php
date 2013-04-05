<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $firstAnime = Anime::first($this->app);
  $numAnimePerPage = 8;
  $params['page'] = (intval($params['page']) > 0) ? intval($params['page']) : 1;
?>
<h2>Related series:</h2>
<?php echo $firstAnime->view('grid', ['anime' => $this->similar(($params['page']- 1) * $numAnimePerPage, $numAnimePerPage)]); ?>