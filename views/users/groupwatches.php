<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);

  // get list of anime that this user and at least one other friend are planning to watch.
  $groupwatches = [];

  $ourPlanToWatch = array_keys($this->animeList()->listSection(6));
  foreach ($this->friends() as $friend) {
    $friendPlanToWatch = array_keys($friend['user']->animeList()->listSection(6));
    $intersect = array_intersect($ourPlanToWatch, $friendPlanToWatch);
    if ($intersect) {
      foreach ($intersect as $animeID) {
        if (!isset($groupwatches[$animeID])) {
          $groupwatches[$animeID] = ['anime' => new Anime($this->app, $animeID), 'users' => [$friend['user']]];
        } else {
          $groupwatches[$animeID]['users'][] = $friend['user'];
        }
      }
    }
  }
?>
<div class='page-header'>
  <h1>Groupwatches <small>Shows your friends are also planning to watch</small></h1>
</div>
<ul class='item-grid recommendations'>
<?php
  if ($groupwatches) {
    foreach ($groupwatches as $groupwatch) {
?>
  <li>
    <?php echo $groupwatch['anime']->link("show", "<h4>".escape_output($groupwatch['anime']->title())."</h4>", Null, True, array('title' => $groupwatch['anime']->title(), 'data-toggle' => 'tooltip')); ?>
    <?php echo $groupwatch['anime']->link("show", $groupwatch['anime']->imageTag, Null, True, array('title' => $groupwatch['anime']->description, 'data-toggle' => 'tooltip', 'data-placement' => 'right')); ?>
    <p><em>Friends: <?php echo implode(", ", array_map(function ($friend) {
      return $friend->link('show', $friend->username());
    }, $groupwatch['users'])); ?></p></em>
  </li>
<?php
    }
  } else {
?>
  No potential groupwatches found :( Maybe it's time to make new friends?
<?php
  }
?>
</ul>