<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class="row-fluid">
  <div class="span6">
    <h1>Welcome!</h1>
    <p>I'm rewriting Animurecs in PHP since half the time I spend in Rails is trying to work around its architecture. Here's what I've got on my to-do list:</p>
    <ol>
      <li>Anime
        <ul>
          <li>3/15/13- fixed these up a little; search on /anime/ and you can now browse anime through "discover"</li>
          <li>need to add stuff like tabbed content up top for general info / vas / trailer / stats etc.</li>
        </ul>
      </li>
      <li><s>Fee</s>d
        <ul>
          <li>feeds currently "work" for users and anime pages (i.e. infinite scrollback)</li>
          <li>still need to implement fetching updates in real time</li>
          <li>also, email notifications (should probably be in its own bullet) of events (signup, settings changed, big stuff happening)
        </ul>
      </li>
      <li><s>Recommend</s>ations
        <ul>
          <li>recommendations server / engine is complete</li>
          <li>1/1/13- now regenerates new features for users every hour on the hour using your newest ratings</li>
          <li>1/1/13- currently the way recs are ported from the original data to new animurecs users is <em>really</em> rough; lots of improvement to be had here</li>
          <li>3/15/13- i think i'm going to restructure recs in the backend a little so users can "dismiss" and otherwise handle them instead of having to manipulate their lists</li>
        </ul>
      </li>
      <li>Landing page
        <ul>
          <li>need to come up with demos, make it look not-godawful</li>
        </ul>
      </li>
      <li><s>Friends</s>
        <ul>
          <li>a basic toy "friend this user" implementation is done</li>
          <li>direct messages, tying friends more directly into what you see (recs, avg scores, etc) are planned for later</li>
        </ul>
      </li>
      <li><s>Posts/comments</s> + :3/:<
        <ul>
          <li>basic commenting and posting is completed on users, anime, anime entries, top-level comments</li>
          <li>some form of "like" or "upvoting" may be in the works eventually, but it's not a huge priority</li>
        </ul>
      </li>
      <li><s>Achi</s>eves
        <ul>
          <li>basic framework has been completed, one toy example added</li>
          <li>1/1/13- waiting on ideas from AKJ (and hopefully images) so i can start cranking these out</li>
          <li>3/15/13- have a bunch of ideas now, thanks akj. need images and a couple hours to get em all done at once.</li>
        </ul>
      </li>
      <li>Groups
        <ul>
          <li>1/1/13- need to talk to AKJ about how this is going to work</li>
          <li>3/15/13- this is on-hold until more basic functionality is complete.</li>
        </ul>
      </li>
      <li>Groupwatches, contests</li>
      <li><s>User model, auth, add/delete/update</s></li>
      <li><s>Anime model, add/delete/update anime</s></li>
      <li><s>Tag model, add/delete/update tags</s></li>
      <li><s>Taggings</s></li>
      <li><s>Start pulling anime from third-party DBs</s></li>
      <li><s>List model, add/delete/update list entries</s></li>
      <li><s>Import list from MAL</s></li>
    </ol>
  </div>
  <div class='span6'>
    <h2>Your Feed</h2>
    <?php echo $this->app->user->globalFeed(); ?>
  </div>
</div>