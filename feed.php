<?php
include_once("global/includes.php");

if (!$user->loggedIn()) {
  header("Location: index.php");
}
start_html($database, $user, "Animurecs", "Home", $_REQUEST['status'], $_REQUEST['class']);
?>
<div class="row-fluid">
  <div class="span12">
    <h1>Welcome!</h1>
    <p>You are now logged in. Here's what I've got on my to-do list:</p>
    <ol>
      <li><s>User auth</s></li>
      <li><s>Anime model, add/delete/update anime</s></li>
      <li>Tag model, add/delete/update tags</li>
      <li>Taggings</li>
      <li>Start pulling anime from third-party DBs</li>
      <li>List model, add/delete/update list entries</li>
      <li>Feed</li>
      <li>Friends</li>
      <li>Posts/comments/+1/props/upvote/likes</li>
      <li>Recommendations</li>
      <li>Groups</li>
    </ol>
  </div>
</div>
<?php
display_footer();
?>