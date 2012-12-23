<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all tags.
  $resultsPerPage = 25;
  $newTag = new Tag($this->app, 0);
  if ($this->app->user->isAdmin()) {
    $tag = $this->dbConn->stdQuery("SELECT `tags`.`id` FROM `tags` ORDER BY `tags`.`name` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `tags`")/$resultsPerPage);
  } else {
    $tag = $this->dbConn->stdQuery("SELECT `tags`.`id` FROM `tags` WHERE `approved_on` != '' ORDER BY `tags`.`name` ASC LIMIT ".((intval($this->app->page)-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagPages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `tags` WHERE `approved_on` != ''")/$resultsPerPage);
  }
?>
<h1>All Tags</h1>
<?php echo paginate($newTag->url("index", array("page" => "")), intval($this->app->page), $tagPages); ?>
<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Type</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
<?php
  while ($thisID = $tag->fetch_assoc()) {
    $thisTag = new Tag($this->app, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisTag->link("show", $thisTag->name()); ?></td>
      <td><?php echo escape_output($thisTag->description()); ?></td>
      <td><?php echo $thisTag->type->link("show", $thisTag->type()->name); ?></td>
      <td><?php echo $this->app->user->isAdmin() ? $thisTag->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $this->app->user->isAdmin() ? $thisTag->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo $newTag->link("new", "Add a tag"); ?>
<?php echo paginate($newTag->url("index", array("page" => "")), intval($this->app->page), $tagPages); ?>