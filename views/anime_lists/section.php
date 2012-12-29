<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['status'] = isset($params['status']) ? $params['status'] : 1;
  // returns markup for one status section of a user's anime list.
  $statusStrings = array(1 => array('id' => 'currentlyWatching', 'title' => 'Currently Watching'),
                        2 => array('id' => 'completed', 'title' => 'Completed'),
                        3 => array('id' => 'onHold', 'title' => 'On Hold'),
                        4 => array('id' => 'dropped', 'title' => 'Dropped'),
                        6 => array('id' => 'planToWatch', 'title' => 'Plan to Watch'));
  $relevantEntries = $this->listSection($params['status']);
?>
      <div id='<?php echo escape_output($statusStrings[$params['status']]['id']); ?>'>
        <h2><?php echo escape_output($statusStrings[$params['status']]['title']); ?></h2>
        <table class='table table-bordered table-striped dataTable' data-id='<?php echo intval($this->user()->id); ?>'>
          <thead>
            <tr>
              <th>Title</th>
              <th class='dataTable-default-sort' data-sort-order='desc'>Score</th>
              <th>Episodes</th>
<?php
  if ($this->app->user->id == $this->user()->id) {
?>              <th></th>
<?php
  }
?>            </tr>
          </thead>
          <tbody>
<?php
    foreach ($relevantEntries as $entry) {
?>          <tr data-id='<?php echo intval($entry['anime']->id); ?>'>
              <td class='listEntryTitle'>
                <?php echo $entry['anime']->link("show", $entry['anime']->title()); ?>
                <span class='pull-right hidden listEntryStatus'>
                  <?php echo display_status_dropdown("anime_list[status]", "span12", intval($entry['status'])); ?>
                </span>
              </td>
              <td class='listEntryScore'><?php echo intval($entry['score']) > 0 ? intval($entry['score'])."/10" : ""; ?></td>
              <td class='listEntryEpisode'><?php echo intval($entry['episode'])."/".(intval($entry['anime']->episodeCount()) == 0 ? "?" : intval($entry['anime']->episodeCount())); ?></td>
<?php
      if ($this->app->user->id == $this->user()->id) {
?>              <td><a href='#' class='listEdit' data-url='<?php echo $this->url("new"); ?>'><i class='icon-pencil'></i></td>
<?php
      }
?>            </tr>
<?php
    }
?>          <tbody>
        </table>
      </div>