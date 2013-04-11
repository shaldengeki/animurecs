<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<div class="row-fluid">
  <div class="span6">
    <h1>Welcome!</h1>
    <p>I'm rewriting Animurecs in PHP since half the time I spend in Rails is trying to work around its architecture. Here's what I've got on my to-do list:</p>
    <ol>
      <li>Anime: Top/Index
        <ul>
          <li>4/8/13- can now flip through pages of related anime</li>
          <li>4/6/13- rudimentary anime stats tab.</li>
          <li>3/17/13- you can also search for anime via the topbar now!!</li>
          <li>3/17/13- added info tabs atop anime pages and alpha version of "related" anime; will have stats later</li>
          <li>3/15/13- fixed these up a little; search on /anime/ and you can now browse anime through "discover"</li>
        </ul>
      </li>
      <li><s>Fee</s>d: Real-time updates
        <ul>
          <li>4/6/13- email notification for user signup finished.</li>
          <li>3/16/13- infinite scrollback should now work for every activity feed on the site</li>
          <li>still need to implement fetching updates in real time</li>
          <li>also, email notifications (should probably be in its own bullet) of events (signup, settings changed, big stuff happening)</li>
        </ul>
      </li>
      <li><s>Recommend</s>ations: Dismiss
        <ul>
          <li>4/8/13- can now flip through pages of recs</li>
          <li>3/17/13- improved accuracy of recs significantly; next is adding "dismiss" feature</li>
          <li>3/15/13- i think i'm going to restructure recs in the backend a little so users can "dismiss" and otherwise handle them instead of having to manipulate their lists</li>
          <li>1/1/13- currently the way recs are ported from the original data to new animurecs users is <em>really</em> rough; lots of improvement to be had here</li>
          <li>1/1/13- now regenerates new features for users every hour on the hour using your newest ratings</li>
          <li>recommendations server / engine is complete</li>
        </ul>
      </li>
      <li>Landing page: Complete (?)
        <ul>
          <li>4/11/13- fixed up landing page, gonna run it by akj</li>
          <li>need to come up with demos, make it look not-godawful</li>
        </ul>
      </li>
      <li><s>Friends</s>: Integration
        <ul>
          <li>a basic toy "friend this user" implementation is done</li>
          <li>direct messages, tying friends more directly into what you see (recs, avg scores, etc) are planned for later</li>
        </ul>
      </li>
      <li><s>Posts/comments</s> + like/dislike: TODO
        <ul>
          <li>basic commenting and posting is completed on users, anime, anime entries, top-level comments</li>
          <li>some form of "like" or "upvoting" may be in the works eventually, but it's not a huge priority</li>
        </ul>
      </li>
      <li><s>Achi</s>eves: Implementation
        <ul>
          <li>4/6/13- a couple achieves are done but there's many more left. still need images.</li>
          <li>3/15/13- have a bunch of ideas now, thanks akj. need images and a couple hours to get em all done at once.</li>
          <li>1/1/13- waiting on ideas from AKJ (and hopefully images) so i can start cranking these out</li>
          <li>basic framework has been completed, one toy example added</li>
        </ul>
      </li>
      <li>Groups: TODO
        <ul>
          <li>3/15/13- this is on-hold until more basic functionality is complete.</li>
          <li>1/1/13- need to talk to AKJ about how this is going to work</li>
        </ul>
      </li>
      <li>Groupwatches, contests: TODO
        <ul>
          <li>4/11/2013- a basic "search for potential groupwatches" interface is up under discover/groupwatches. The rest can wait until groups are done.</li>
        </ul>
      </li>
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
    <?php echo $this->view('feed', $params); ?>
  </div>
</div>