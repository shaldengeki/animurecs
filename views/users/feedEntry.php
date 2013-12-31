<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  
  // outputs feed markup for the feed entry provided at params['entry'] or list of feed entries provided at params['entries'].
  // also takes an optional parameter at params['nested'] to indicate if entry is nested.

  $params['nested'] = isset($params['nested']) ? $params['nested'] : False;
  $params['entries'] = isset($params['entries']) ? $params['entries'] : isset($params['entry']) ? [$params['entry']] : [];

  if (!$params['entries']) {
    $this->app->logger->err("No entry passed to users/feedEntry.");
    return;
  }

  $nowTime = new DateTime("now", $this->app->outputTimeZone);

  foreach ($params['entries'] as $entry) {
    // try {
      $diffInterval = $nowTime->diff($params['entry']->time());
    // } catch (ErrorException $e) {
    //   ob_end_clean();
    //   unset($params['entry']->app);
    //   echo "<pre>".print_r($params['entry'], True)."</pre>";
    //   exit;
    // }
    $feedMessage = $entry->formatFeedEntry();

    if ($feedMessage['text']) {

      $blankEntryComment = new Comment($this->app, 0, $this->app->user, $entry);

      $entryType = $params['nested'] ? "div" : "li";
  ?>
        <<?php echo $entryType; ?> class='media'>
      <div class='pull-right feedDate' data-time='<?php echo $entry->time()->format('U'); ?>'><?php echo ago($diffInterval); ?></div>
      <?php echo $entry->user->link("show", $entry->user->thumbImage(['class' => 'feedAvatarImg']), Null, True, ['class' => 'feedAvatar pull-left']); ?>
      <div class='media-body feedText'>
        <div class='feedEntry'>
          <h4 class='media-heading feedUser'><?php echo $feedMessage['title']; ?></h4>
          <?php echo $feedMessage['text']; ?>
<?php
      if ($entry->allow($this->app->user, 'delete')) {
?>
          <ul class='feedEntryMenu hidden'><li><?php echo $entry->link("delete", "<i class='glyphicon glyphicon-trash'></i> Delete", Null, True); ?></li></ul>
<?php
      }
?>
        </div>
<?php
      if ($entry->comments) {
        foreach ($entry->comments->load('info')->load('users')->load('comments')->entries() as $commentEntry) {
          echo $this->view('feedEntry', ['entry' => $commentEntry, 'nested' => True]);
        }
      }
      if ($entry->allow($this->app->user, 'comment') && $blankEntryComment->depth() < 2) {
?>
        <div class='entryComment'><?php echo $blankEntryComment->view('inlineForm', ['currentObject' => $entry]); ?></div>
<?php
      }
?>
      </div>
    </<?php echo $entryType; ?>>
<?php
    }
  }
?>