<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // displays a list of tags for this group of anime, sorted by tag type and then frequency of tag.

  // maximal number of tags to display under each category heading.
  $params['numTags'] = isset($params['numTags']) ? intval($params['numTags']) : 20;

  $this->tags()->load('info');

  // order the tags in this animeGroup's tags by tagType id.
  $tagTypesQuery = $this->app->dbConn->table(TagType::$MODEL_TABLE)->fields('id', 'name')->order('id ASC')->query(); 
  $tagTypes = [];
  while ($tagType = $tagTypesQuery->fetch()) {
    $tagTypeObj = new TagType($this->app, intval($tagType['id']));
    $tagTypes[] = $tagTypeObj->set(['name' => $tagType['name']]);
  }
  $tagCounts = [];
  foreach ($this->tagCounts() as $id=>$countArray) {
    $tagCounts[$id] = $countArray['count'];
  }

  $tagsByTagType = [];
  $tagCountsByType = [];

  foreach ($this->tags() as $tag) {
    if (!isset($tagCountsByType[$tag->type->id])) {
      $tagCountsByType[$tag->type->id] = [$tag->id => $tagCounts[$tag->id]];
    } else {
      $tagCountsByType[$tag->type->id][$tag->id] = $tagCounts[$tag->id];
    }
  }

  // go back and sort by count.
  foreach ($tagCountsByType as $tagTypeID => $tags) {
    arsort($tagCountsByType[$tagTypeID]);
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
    if (!isset($tagCountsByType[$tagType->id])) {
?>
    <em>No tags under this category.</em>
<?php
      continue;
    }
?>
    <ul class='tagTypeTags'>
<?php
    foreach ($tagCountsByType[$tagType->id] as $tagID => $count) {
?>
      <li><?php echo $this->tags()[$tagID]->link("show", $this->tags()[$tagID]->name)." ".intval($count); ?></li>
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