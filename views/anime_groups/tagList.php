<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // displays a list of tags for this group of anime, sorted by tag type and then frequency of tag.

  // maximal number of tags to display under each category heading.
  $params['numTags'] = isset($params['numTags']) ? intval($params['numTags']) : 20;

  $this->tags()->load('info');

  // order the tags in this animeGroup's tags by tagType id.
  $tagTypesQuery = $this->app->dbConn->query("SELECT `name`, `id` FROM `tag_types` ORDER BY `id` ASC");
  $tagTypes = [];
  while ($tagType = $tagTypesQuery->fetch_assoc()) {
    $tagTypeObj = new TagType($this->app, intval($tagType['id']));
    $tagTypes[] = $tagTypeObj->set(['name' => $tagType['name']]);
  }
  $tagCounts = $this->tagCounts();
  $tagsByTagType = [];
  foreach ($this->tags() as $tag) {
    if (!isset($tagsByTagType[$tag->type->id])) {
      $tagsByTagType[$tag->type->id] = [$tag];
    } else {
      $tagsByTagType[$tag->type->id][] = $tag;
    }
  }
?>
<ul class='tagList'>
<?php
  foreach ($tagTypes as $tagType) {
    $tagsDisplayed = 1;
?>
  <li class='tagType-heading'><?php echo escape_output($tagType->name).":"; ?></li>
  <li>
<?php
    if (!isset($tagsByTagType[$tagType->id])) {
?>
    <em>No tags under this category.</em>
<?php
      continue;
    }
?>
    <ul class='tagTypeTags'>
<?php
    foreach ($tagsByTagType[$tagType->id] as $tag) {
?>
      <li><?php echo $tag->link("show", $tag->name)." ".intval($tagCounts[$tag->id]['count']); ?></li>
<?php
      if ($tagsDisplayed >= $params['numTags']) {
        break;
      }
      $tagsDisplayed++;
    }
?>
    </ul>
  </li>
<?php
  }
?>
</ul>