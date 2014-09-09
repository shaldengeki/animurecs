<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all tags.
  $params['perPage'] = isset($params['perPage']) ? $params['perPage'] : 25;
  $params['tags'] = isset($params['tags']) ? $params['tags'] : [];
  $params['pages'] = isset($params['pages']) ? $params['pages'] : ceil(count($params['tags'])/$params['perPage']);
  $firstTag = Tag::Get($this->app);
?>
<h1>Tags</h1>
<?php echo paginate($firstTag->url("index", Null, ["page" => ""]), intval($this->app->page), $params['pages']); ?>
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
  while ($thisID = $params['tags']->fetch()) {
    $thisTag = new Tag($this->app, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisTag->link("show", $thisTag->name); ?></td>
      <td><?php echo escape_output($thisTag->description); ?></td>
      <td><?php echo $thisTag->type->link("show", $thisTag->type->name); ?></td>
      <td><?php echo $thisTag->allow($this->app->user, "edit") ? $thisTag->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $thisTag->allow($this->app->user, "delete") ? $thisTag->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo $firstTag->link("new", "Add a tag"); ?>
<?php echo paginate($firstTag->url("index", Null, ["page" => ""]), intval($this->app->page), $params['pages']); ?>