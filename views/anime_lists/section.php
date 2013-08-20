<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
  $params['status'] = isset($params['status']) ? $params['status'] : 1;
  // returns markup for one status section of a user's anime list.
  $statusStrings = [1 => ['id' => 'currentlyWatching', 'title' => 'Currently Watching'],
                        2 => ['id' => 'completed', 'title' => 'Completed'],
                        3 => ['id' => 'onHold', 'title' => 'On Hold'],
                        4 => ['id' => 'dropped', 'title' => 'Dropped'],
                        6 => ['id' => 'planToWatch', 'title' => 'Plan to Watch']];
  $relevantEntries = $this->listSection($params['status']);

  $newEntry = new AnimeEntry($this->app, Null, ['user' => $this->user()]);
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
                  <?php echo display_status_dropdown("anime_entries[status]", "form-control", intval($entry['status'])); ?>
                </span>
              </td>
              <td class='listEntryScore'><?php echo round(floatval($entry['score']), 2) > 0 ? round(floatval($entry['score']), 2) : ""; ?></td>
              <td class='listEntryEpisode'><?php echo intval($entry['episode'])."/".(intval($entry['anime']->episodeCount()) == 0 ? "?" : intval($entry['anime']->episodeCount())); ?></td>
<?php
      if ($this->app->user->id == $this->user()->id) {
?>              <td><a href='#' class='listEdit' data-url='<?php echo $newEntry->url("new"); ?>'><i class='glyphicon glyphicon-pencil'></i></td>
<?php
      }
?>            </tr>
<?php
    }
?>          <tbody>
        </table>
      </div>