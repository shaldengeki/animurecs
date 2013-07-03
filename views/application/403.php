<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
?>
<div class='center-horizontal'>
<?php
  if ($this->user->loggedIn()) {
?>
  <h1>Error (403): Insufficient privileges</h1>
  <img src='/img/403.png' />
  <p>Unfortunately, you don't have the required permissions to do that! If this is in error, please contact a site admin. Thanks!</p>
<?php
  } else {
?>
  <h1>Error (403): Not signed in</h1>
  <p>Please log in to view this page.</p>
  <div class='row-fluid'>
    <div class='span3'>&nbsp;</div>
    <div class='span6'>
<?php
      echo $this->user->view('login', $params);
  }
?>
    </div>
    <div class='span3'>&nbsp;</div>
  </div>
</div>
