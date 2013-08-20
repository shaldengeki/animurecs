<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<ul class='dropdown-menu'>
<?php
  if (!$this->app->user->friendRequests()) {
?>
  <li>
    <p>
      <em>No friend requests are pending right now.</em>
    </p>
  </li>
<?php    
  } else {
    foreach (array_sort_by_key($this->app->user->friendRequests(), 'status', 'desc') as $request) {
      $entryTime = new DateTime($request['time'], $this->app->serverTimeZone);
      $entryTime->setTimezone($this->app->outputTimeZone);
?>
  <li class='friendRequestEntry'>
    <p>
      <strong><?php echo $request['user']->link('show', $request['user']->username()); ?></strong> requested to be your friend on <?php echo $entryTime->format('G:i n/j/y'); ?>.
    </p>
    <div class='row'>
      <div class='col-md-6'><?php echo $this->app->form(['action' => $this->url('confirm_friend', Null, Null, $request['user']->username), 'class' => 'form-horizontal']); ?><button type='submit' class='btn btn-primary'>Confirm</button></form></div>
      <div class='col-md-6'><?php echo $this->app->form(['action' => $this->url('ignore_friend', Null, Null, $request['user']->username), 'class' => 'form-horizontal']); ?><button type='submit' class='btn btn-warning'>Ignore</button></form></div>
    </div>
  </li>
<?php
    }
  }
?>
</ul>