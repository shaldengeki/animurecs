<?php
require_once("global/includes.php");

if (!$app->user->loggedIn()) {
  header("Location: /?redirect_to=/feed.php");
}
$pageHTML = <<<EOD
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
      <li><s>Posts/comment/</s>+1/props/upvote/likes</li>
      <li><s>Fee</s>d</li>
      <li><s>Recommend</s>ations</li>
      <li><s>Achi</s>eves</li>
      <li>Groups</li>
      <li>Groupwatches, contests</li>
    </ol>
  </div>
  <div class='span6'>
    <h2>My Feed</h2>
    {$app->user->globalFeed()}
  </div>
</div>
EOD;
$app->render($pageHTML);
?>