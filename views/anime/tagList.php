<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // displays a list of tags for this anime, sorted by tag type and then count of tag.

  // maximal number of tags to display under each category heading.
  $params['numTags'] = isset($params['numTags']) ? intval($params['numTags']) : 20;

  // order the tags in this animeGroup's tags by tagType id
  $tagTypesQuery = $this->app->dbConn->table(TagType::$MODEL_TABLE)->fields('id', 'name')->order('id ASC')->query();
  $tagTypes = [];
  while ($tagType = $tagTypesQuery->fetch()) {
    $tagTypeObj = new TagType($this->app, intval($tagType['id']));
    $tagTypes[] = $tagTypeObj->set(['name' => $tagType['name']]);
  }
  $tagCounts = $this->tags()->load('info')->tagCounts();
  $tagsByTagType = [];
  foreach ($tagCounts as $tagCount) {
    if (!isset($tagsByTagType[$tagCount['tag']->type->id])) {
      $tagsByTagType[$tagCount['tag']->type->id] = [$tagCount['tag']];
    } else {
      $tagsByTagType[$tagCount['tag']->type->id][] = $tagCount['tag'];
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
