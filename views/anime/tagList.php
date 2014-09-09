<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);

  // displays a list of tags for this anime, sorted by tag type and then count of tag.

  // maximal number of tags to display under each category heading.
  $params['numTags'] = isset($params['numTags']) ? intval($params['numTags']) : 20;
  $params['tagCounts'] = isset($params['tagCounts']) ? $params['tagCounts'] : [];
  $params['tagsByType'] = isset($params['tagsByType']) ? $params['tagsByType'] : [];

  // order the tags in this animeGroup's tags by tagType id
  $tagTypes = TagType::GetList($this->app);
?>
<ul class='tagList'>
<?php
  foreach ($tagTypes as $tagType) {
    $tagsDisplayed = 1;
?>
  <li class='tagType-heading'><?php echo escape_output($tagType->name).":"; ?></li>
  <li>
<?php
    if (!isset($params['tagsByType'][$tagType->id])) {
?>
    <em>No tags under this category.</em>
<?php
      continue;
    }
?>
    <ul class='tagTypeTags'>
<?php
    foreach ($params['tagsByType'][$tagType->id] as $tag) {
?>
      <li><?php echo $tag->link("show", $tag->name)." ".intval($tag->numAnime()); ?></li>
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
