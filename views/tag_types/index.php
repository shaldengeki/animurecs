<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all tags.
  $resultsPerPage = 25;
  $firstTagType = TagType::first($this->app);
  if ($this->app->user->isAdmin()) {
    $tagTypePages = ceil(TagType::count($this->app)/$resultsPerPage);
    $tagType = $this->dbConn->table('tag_types')->fields('id')->order('name')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage)->query();
  } else {
    $tagTypePages = ceil(TagType::count($this->app, ['approved_on != ""'])/$resultsPerPage);
    $tagType = $this->dbConn->table('tag_types')->fields('id')->where(['approved_on != ""'])->order('name ASC')->offset((intval($this->app->page)-1)*$resultsPerPage)->limit($resultsPerPage)->query();
  }
?>
<h1>All Tag Types</h1>
<?php echo paginate($firstTagType->url("index", Null, ["page" => ""]), intval($this->app->page), $tagTypePages); ?>
<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
<?php
  while ($thisID = $tagType->fetch()) {
    $thisTagType = new TagType($this->app, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisTagType->link("show", $thisTagType->name()); ?></td>
      <td><?php echo escape_output($thisTagType->description()); ?></td>
      <td><?php echo $thisTagType->allow($this->app->user, "edit") ? $thisTagType->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $thisTagType->allow($this->app->user, "delete") ? $thisTagType->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo paginate($firstTagType->url("index", Null, ["page" => ""]), intval($this->app->page), $tagTypePages); ?>
<?php echo $firstTagType->link("new", "Add a tag type"); ?>