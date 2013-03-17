<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<ul class='dropdown-menu'>
<?php
  foreach (array_sort_by_key($this->app->user->friendRequests(), 'status', 'desc') as $request) {
    $entryTime = new DateTime($request['time'], $this->app->serverTimeZone);
    $entryTime->setTimezone($this->app->outputTimeZone);
?>
  <li class='friendRequestEntry'>
    <strong><?php echo $request['user']->link('show', $request['user']->username()); ?></strong> requested to be your friend on <?php echo $entryTime->format('G:i n/j/y'); ?>.
    <div class='row-fluid'>
      <div class='span6'><?php echo $this->link('confirm_friend', "Accept", Null, True, Null, Null, $request['user']->username); ?></div>
      <div class='span6'><?php echo $this->link('ignore_friend', "Ignore", Null, True, Null, Null, $request['user']->username); ?></div>
    </div>
  </li>
<?php
  }
?>
</ul>