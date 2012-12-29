<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<ul id="userListNav" class="nav nav-pills">
  <li><a href='#currentlyWatching'>Currently Watching</a></li>
  <li><a href='#completed'>Completed</a></li>
  <li><a href='#onHold'>On Hold</a></li>
  <li><a href='#dropped'>Dropped</a></li>
  <li><a href='#planToWatch'>Plan to Watch</a></li>
</ul>