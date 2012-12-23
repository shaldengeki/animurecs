<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
?>
<div class='center-horizontal'>
  <h1>Error (403): Insufficient privileges</h1>
  <img src='/img/kawaii.png' />
  <p>Unfortunately, you don't have the required permissions to do that! If this is in error, please contact a site admin. Thanks!</p>
</div>
