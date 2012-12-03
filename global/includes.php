<?php
include_once("./global/config.php");

include_once("./global/bcrypt.php");
include_once("./global/database.php");
include_once("./global/curl.php");

include_once("./global/commentable.php");
include_once("./global/feedable.php");

include_once("./global/base_object.php");
include_once("./global/base_list.php");
include_once("./global/base_entry.php");
include_once("./global/tag_type.php");
include_once("./global/tag.php");
include_once("./global/anime.php");
include_once("./global/anime_list.php");
include_once("./global/anime_entry.php");
include_once("./global/user.php");
include_once("./global/comment.php");
include_once("./global/comment_entry.php");
include_once("./global/recs_engine.php");

include_once("./global/display.php");
include_once("./global/misc.php");
session_start();
$database = new DbConn(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE);
$recsEngine = new RecsEngine(RECS_ENGINE_HOST, RECS_ENGINE_PORT);

date_default_timezone_set(SERVER_TIMEZONE);
$serverTimeZone = new DateTimeZone(SERVER_TIMEZONE);
$outputTimeZone = new DateTimeZone(OUTPUT_TIMEZONE);

if (isset($_SESSION['id'])) {
  $user = new User($database, $_SESSION['id']);
  // if user's last action was 5 or more minutes ago, update his/her last-active time.
  if ($user->lastActive->diff(new DateTime("now", new DateTimeZone(SERVER_TIMEZONE)))->i >= 5) {
    $user->updateLastActive();
  }
} else {
  $user = new User($database, 0, "Guest");
}
if (!isset($_REQUEST['status'])) {
  $_REQUEST['status'] = "";
}
if (!isset($_REQUEST['class'])) {
  $_REQUEST['class'] = "";
}
if (!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
  if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
    $_REQUEST['action'] = 'show';
  } else {
    $_REQUEST['action'] = 'index';
  }
}
if (!isset($_REQUEST['page'])) {
  $_REQUEST['page']  = 1;
} else {
  $_REQUEST['page'] = max(1, intval($_REQUEST['page']));
}

?>