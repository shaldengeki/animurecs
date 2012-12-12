<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  check_partial_include(__FILE__);

  // lists all tags.
  $resultsPerPage = 25;
  $newTagType = new TagType($this->dbConn, 0);
  if ($currentUser->isAdmin()) {
    $tagType = $this->dbConn->stdQuery("SELECT `tag_types`.`id` FROM `tag_types` ORDER BY `tag_types`.`name` ASC LIMIT ".((intval($params['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagTypePages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `tag_types`")/$resultsPerPage);
  } else {
    $tagType = $this->dbConn->stdQuery("SELECT `tag_types`.`id` FROM `tag_types` WHERE `approved_on` != '' ORDER BY `tag_types`.`name` ASC LIMIT ".((intval($params['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagTypePages = ceil($this->dbConn->queryCount("SELECT COUNT(*) FROM `tag_types` WHERE `approved_on` != ''")/$resultsPerPage);
  }
?>
<h1>All Tag Types</h1>
<?php echo paginate($newTagType->url("index", array("page" => "")), intval($params['page']), $tagTypePages); ?>
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
  while ($thisID = $tagType->fetch_assoc()) {
    $thisTagType = new TagType($this->dbConn, intval($thisID['id']));
?>
    <tr>
      <td><?php echo $thisTagType->link("show", $thisTagType->name()); ?></td>
      <td><?php echo escape_output($thisTagType->description()); ?></td>
      <td><?php echo $currentUser->isAdmin() ? $thisTagType->link("edit", "Edit") : ""; ?></td>
      <td><?php echo $currentUser->isAdmin() ? $thisTagType->link("delete", "Delete") : ""; ?></td>
    </tr>
<?php
  }
?>
  </tbody>
</table>
<?php echo $newTagType->link("new", "Add a tag type"); ?>
<?php echo paginate($newTagType->url("index", array("page" => "")), intval($params['page']), $tagTypePages); ?>