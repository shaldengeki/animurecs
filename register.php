<?php
include_once("global/includes.php");
if ($user->loggedIn()) {
  header("Location: index.php");
}
if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_confirmation'])) {
  $registerUser = $user->register($_POST['username'], $_POST['email'], $_POST['password'], $_POST['password_confirmation']);
  redirect_to($registerUser);
} else {
  start_html($database, $user, "Animurecs", "", $_REQUEST['status']);
?>
<div class="row-fluid">
  <div class="span4">&nbsp;</div>
  <div class="span4">
<?php
  display_register_form("register.php");
?>
  </div>
  <div class="span4">&nbsp;</div>
</div>
<?php
  display_footer();
}
?>