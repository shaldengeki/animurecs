<?php
include_once("global/includes.php");

if (!$user->loggedIn()) {
  header("Location: /");
}
start_html($database, $user, "Animurecs", "Home", $_REQUEST['status'], $_REQUEST['class']);
?>
<div class="row-fluid">
  <div class="span6">
    <h1>Welcome!</h1>
    <p>I'm rewriting Animurecs in PHP since half the time I spend in Rails is trying to work around its architecture. Here's what I've got on my to-do list:</p>
    <ol>
      <li><s>User model, auth, add/delete/update</s></li>
      <li><s>Anime model, add/delete/update anime</s></li>
      <li><s>Tag model, add/delete/update tags</s></li>
      <li><s>Taggings</s></li>
      <li><s>Start pulling anime from third-party DBs</s></li>
      <li><s>List model, add/delete/update list entries</s></li>
      <li><s>Import list from MAL</s></li>
      <li><s>Friends</s></li>
      <li>Posts/comments/+1/props/upvote/likes</li>
      <li><s>Fe</s>ed</li>
      <li>Recommendations</li>
      <li>Groups</li>
    </ol>
  </div>
<?php
echo "  <div class='span6'>
    <h2>My Feed</h2>
    ".$user->globalFeed()."
  </div>
</div>";
display_footer();
?>