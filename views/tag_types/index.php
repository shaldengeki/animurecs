<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  // lists all tags.
  $params['perPage'] = isset($params['perPage']) ? $params['perPage'] : 25;
  $params['tagTypes'] = isset($params['tagTypes']) ? $params['tagTypes'] : [];
  $params['pages'] = isset($params['pages']) ? $params['pages'] : ceil(count($params['tagTypes'])/$params['perPage']);
  $firstTagType = TagType::Get($this->app);

?>
<h1>Tag Types</h1>
<?php echo paginate($firstTagType->url("index", Null, ["page" => ""]), intval($this->app->page), $params['pages']); ?>
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
  while ($thisID = $params['tagTypes']->fetch()) {
    $thisTagType = new TagType($this->app, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisTagType->link("show", $thisTagType->name); ?></td>
      <td><?php echo escape_output($thisTagType->description); ?></td>
      <td><?php echo $thisTagType->allow($this->app->user, "edit") ? $thisTagType->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $thisTagType->allow($this->app->user, "delete") ? $thisTagType->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo paginate($firstTagType->url("index", Null, ["page" => ""]), intval($this->app->page), $params['pages']); ?>
<?php echo $firstTagType->link("new", "Add a tag type"); ?>