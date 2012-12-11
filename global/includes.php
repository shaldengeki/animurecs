<?php
require_once($_SERVER['DOCUMENT_ROOT']."/global/config.php");

require_once($_SERVER['DOCUMENT_ROOT']."/global/bcrypt.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/database.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/curl.php");

require_once($_SERVER['DOCUMENT_ROOT']."/global/aliasable.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/commentable.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/feedable.php");

require_once($_SERVER['DOCUMENT_ROOT']."/global/base_object.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/base_list.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/base_entry.php");

require_once($_SERVER['DOCUMENT_ROOT']."/global/alias.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/tag_type.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/tag.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/anime.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/anime_list.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/anime_entry.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/user.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/comment.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/comment_entry.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/recs_engine.php");

// include all achievements.
require_once($_SERVER['DOCUMENT_ROOT']."/global/base_achievement.php");
foreach (glob($_SERVER['DOCUMENT_ROOT']."/global/achievements/*.php") as $filename) {
  require_once($filename);
}

require_once($_SERVER['DOCUMENT_ROOT']."/global/display.php");
require_once($_SERVER['DOCUMENT_ROOT']."/global/misc.php");
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