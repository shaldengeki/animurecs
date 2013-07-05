<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all tags.
  $resultsPerPage = 25;
  $firstTag = Tag::first($this->app);
  if ($this->app->user->isAdmin()) {
    $tagPages = ceil(Tag::count($this->app)/$resultsPerPage);
    $tag = $this->app->dbConn->table(Tag::$MODEL_TABLE)->fields('id')->order('name ASC')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage);
  } else {
    $tagTypePages = ceil(Tag::count($this->app, ['approved_on != ""'])/$resultsPerPage);
    $tag = $this->app->dbConn->table(Tag::$MODEL_TABLE)->fields('id')->where(['approved_on != ""'])->order('name ASC')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage);
  }
?>
<h1>All Tags</h1>
<?php echo paginate($firstTag->url("index", Null, ["page" => ""]), intval($this->app->page), $tagPages); ?>
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
      <td><?php echo $thisTag->allow($this->app->user, "edit") ? $thisTag->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $thisTag->allow($this->app->user, "delete") ? $thisTag->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo $firstTag->link("new", "Add a tag"); ?>
<?php echo paginate($firstTag->url("index", Null, ["page" => ""]), intval($this->app->page), $tagPages); ?>