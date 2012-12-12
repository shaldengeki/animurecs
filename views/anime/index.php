<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $app->check_partial_include(__FILE__);

  // lists all anime.
  $resultsPerPage = 25;
  $newAnime = new Anime($this->dbConn, 0);
  if ($app->user->isAdmin()) {
    $anime = $this->dbConn->stdQuery("SELECT `anime`.`id` FROM `anime` ORDER BY `anime`.`title` ASC LIMIT ".((intval($app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
    $animePages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `anime`")/$resultsPerPage);
  } else {
    $anime = $this->dbConn->stdQuery("SELECT `anime`.`id` FROM `anime` WHERE `approved_on` != '' ORDER BY `anime`.`title` ASC LIMIT ".((intval($app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
    $animePages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `anime` WHERE `approved_on` != ''")/$resultsPerPage);
  }
?>
<h1>All Anime</h1>
<?php echo paginate($newAnime->url("index", array('page' => '')), intval($app->page), $animePages); ?>
<table class='table table-striped table-bordered dataTable' data-recordsPerPage='<?php echo $resultsPerPage; ?>'>
  <thead>
    <tr>
      <th>Title</th>
      <th>Description</th>
      <th>Length</th>
<?php
  if ($newAnime->allow($app->user, 'edit')) {
?>
      <th></th>
<?php
  }
  if ($newAnime->allow($app->user, 'delete')) {
?>
      <th></th>
<?php
  }
?>
    </tr>
  </thead>
  <tbody>
<?php
  while ($thisID = $anime->fetch_assoc()) {
    $thisAnime = new Anime($this->dbConn, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisAnime->link("show", $thisAnime->title()); ?></td>
      <td><?php echo escape_output($thisAnime->description()); ?></td>
      <td><?php echo intval($thisAnime->episodeCount() * $thisAnime->episodeLength()); ?> minutes</td>
<?php
    if ($newAnime->allow($app->user, 'edit')) { 
?>
      <td><?php echo $thisAnime->link("edit", "Edit"); ?></td>
<?php
    }
    if ($newAnime->allow($app->user, 'delete')) { 
?>
      <td><?php echo $thisAnime->link("delete", "Delete"); ?></td>
<?php
    }
?>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo paginate($newAnime->url("index", array('page' => '')), intval($app->page), $animePages); ?>
<?php echo $newAnime->allow($app->user, 'new') ? $newAnime->link("new", "Add an anime") : ""; ?>