<?php
include_once("./global/config.php");

include_once("./global/bcrypt.php");
include_once("./global/database.php");
include_once("./global/curl.php");

include_once("./global/tag_type.php");
include_once("./global/tag.php");
include_once("./global/anime.php");
include_once("./global/user.php");

include_once("./global/display.php");
include_once("./global/misc.php");

$database = new DbConn(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE);
session_start();
if (isset($_SESSION['id'])) {
  $user = new User($database, $_SESSION['id']);
} else {
  $user = new User($database, 0, "Guest");
}
if (!isset($_REQUEST['status'])) {
  $_REQUEST['status'] = "";
}
if (!isset($_REQUEST['class'])) {
  $_REQUEST['class'] = "";
}
if (!isset($_REQUEST['action'])) {
  $_REQUEST['action'] = 'index';
}
if (!isset($_REQUEST['page'])) {
  $_REQUEST['page']  = 1;
} else {
  $_REQUEST['page'] = max(1, intval($_REQUEST['page']));
}
date_default_timezone_set(SERVER_TIMEZONE);
$outputTimeZone = new DateTimeZone(OUTPUT_TIMEZONE);

?>