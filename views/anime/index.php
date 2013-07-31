<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all anime.
  $firstAnime = Anime::first($this->app);
  $paginationArray = [];
  if (isset($_REQUEST['search'])) {
    $paginationArray['search'] = $_REQUEST['search'];
  }
  $paginationArray['page'] = '';

  $params['title'] = isset($params['title']) ? $params['title'] : "Browse Anime";

  $animeGroup = new AnimeGroup($this->app, $params['anime']);

  $params['wilsons'] = isset($params['wilsons']) ? $params['wilsons'] : [];
?>
<div class='row-fluid'>
  <h1><?php echo escape_output($params['title']); ?></h1>
  <?php echo $firstAnime->view('searchForm', ['form' => ['class' => 'form-inline pull-right']]); ?>
</div>
<?php echo $params['numPages'] > 1 ? paginate($firstAnime->url("index", Null, $paginationArray), intval($this->app->page), $params['numPages']) : ""; ?>

<div class='row-fluid'>
  <?php echo $this->view('grid', ['anime' => $animeGroup, 'wilsons' => $params['wilsons']]); ?>
</div>

<?php echo $params['numPages'] > 1 ? paginate($firstAnime->url("index", Null, $paginationArray), intval($this->app->page), $params['numPages']) : ""; ?>
<?php echo $firstAnime->allow($this->app->user, 'new') ? $firstAnime->link("new", "Add an anime") : ""; ?>