<?php
  if (str_replace("\\", "/", __FILE__) === $_SERVER['SCRIPT_FILENAME']) {
    echo "This partial cannot be rendered by itself.";
    exit;
  }
?>
<div class='center-horizontal'>
  <h1>Error (500): Internal Server Error</h1>
  <img src='/img/500.png' />
  <p>Something went wrong in the machinery somewhere, and the staff's been notified. Sorry for the inconvenience!</p>
</div>
