<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  /* renders a feed given by entries in params['entries'].
    optional parameters:
      params['numEntries'] to limit number of entries returned.
      params['feedURL'] to provide AJAX source for more feed entries
      params['emptyFeedText'] to provide text when no entries are found.
  */

  $params['numEntries'] = (isset($params['numEntries']) && is_numeric($params['numEntries'])) ? intval($params['numEntries']) : 50;

  // sort by key and grab only the latest numEntries.
  $params['entries'] = array_sort_by_method($params['entries']->load('comments')->entries(), 'time', array(), 'desc');
  $params['entries'] = array_slice($params['entries'], 0, $params['numEntries']);
  if (!$params['entries']) {
    echo (isset($params['emptyFeedText']) ? $params['emptyFeedText'] : "<blockquote><p>No entries yet. Be the first!</p></blockquote>");
  } else {
    // now pull info en masse for these entries.
    $entryGroup = new EntryGroup($this->app, $params['entries']);
?>
<ul class='media-list ajaxFeed' data-url='<?php echo (isset($params['feedURL']) ? $params['feedURL'] : $this->url("feed")); ?>'>
<?php
    foreach ($entryGroup->load('info')->load('users')->load('anime')->load('comments')->entries() as $entry) {
      echo $this->view('feedEntry', array('entry' => $entry))."\n";
    }
?>
</ul>
<?php
  }
?>
