<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/../includes.php");
  $this->app->check_partial_include(__FILE__);
  $newEntry = new AnimeEntry($this->app, Null, ['user' => $this]);
  $params['entries'] = isset($params['entries']) ? $params['entries'] : [];
?>
     <div class='row'>
        <div class='col-md-3 userProfileColumn leftColumn'>
          <ul class='thumbnails avatarContainer'>
            <li class='col-md-12'>
              <div class='img-thumbnail profileAvatar'>
<?php
if ($this->avatarPath() != '') {
?>
                <?php echo $this->avatarImage(['class' => 'img-rounded', 'alt' => '']); ?>
<?php
} else {
?>
                <img src='/img/users/blank.png' class='img-rounded' alt=''>
<?php
}
?>
          </div>
            </li>
          </ul>
          <div class='friendListBox'>
            <h3>Friends<?php echo $this->friends() ? " (".count($this->friends()).") " : ""; ?></h3>
            <ul class='friendGrid'>
<?php
  /*
    TODO: refactor this logic into the controller.
  */
  $friendSlice = $this->friends();
  shuffle($friendSlice);
  $friendSlice = array_slice($friendSlice, 0, 4);
  foreach ($friendSlice as $friendEntry) {
?>            <li class='friendGridEntry'><?php echo $friendEntry['user']->link("show", $friendEntry['user']->thumbImage(['class' => 'friendGridImage'])."<div class='friendGridUsername'>".escape_output($friendEntry['user']->username)."</div>", Null, True); ?></li>
<?php
  }
?>
            </ul>
          </div>
        </div>
        <div class='col-md-9 userProfileColumn rightColumn'>
          <div class='profileUserInfo'>
            <div class='page-header'>
              <h1>
                <?php echo $this->link('show', $this->username); ?>
                <?php echo $this->isModerator() ? "<span class='label label-info staffUserTag'>Moderator</span>" : ""; ?>
                <?php echo $this->isAdmin() ? "<span class='label label-danger staffUserTag'>Admin</span>" : ""; ?>
                <?php echo $this->allow($this->app->user, "edit") ? "<small>(".$this->link("edit", "edit").")</small>" : "" ?>
              <?php 
                if ($this->allow($this->app->user, 'request_friend') && $this->id != $this->app->user->id) {
                  if ($this->isFriend($this->app->user)) {
?>
                <span class='pull-right'><button type='button' class='btn btn-success btn-lg disabled' disabled='disabled'>Friends</button></span>
<?php
                  } elseif ($this->hasFriendRequestFrom($this->app->user)) {
?>
                <span class='pull-right'><button type='button' class='btn btn-warning btn-lg disabled' disabled='disabled'>Request Sent</button></span>
<?php                    
                  } elseif ($this->hasRequestedFriend($this->app->user)) {
                    $buttonParams = ['type' => 'submit', 'class' => 'btn btn-primary btn-lg', 'value' => 'Confirm Request'];
?>
                <span class='pull-right'><?php echo $this->app->form(['action' => $this->url('confirm_friend')]).$this->app->input($buttonParams); ?></form></span>
<?php
                  } else {
                    $buttonParams = ['type' => 'submit', 'class' => 'btn btn-primary btn-lg', 'value' => 'Add Friend'];
?>
                <span class='pull-right'><?php echo $this->app->form(['action' => $this->url('request_friend')]).$this->app->input($buttonParams); ?></form></span>
<?php
                  }
                }
?>
              </h1>
            </div>
            <p class='lead'>
              <?php echo escape_output($this->about); ?>
            </p>
            <ul class='thumbnails'>
<?php
  if ($this->id !== $this->app->user->id) {
?>
              <li class='col-md-4'>
                <p>Anime compatibility:</p>
                <?php echo $this->animeList()->view('compatibilityBar', ['compatibility' => $this->animeList()->compatibility($this->app->user->animeList())]); ?>
              </li>
<?php
  }
?>
              <li class='col-md-4'>
                <?php echo $this->view('pointsBar'); ?>
              </li>
              <li class='col-md-4'>
                
              </li>
            </ul>
          </div>
          <div class='profileTabs'>
            <ul class='nav nav-tabs'>
              <li class='active ajaxTab' data-url='<?php echo $this->url("feed"); ?>'><a href='#feed' data-toggle='tab'>Feed</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("anime_list"); ?>'><a href='#anime' data-toggle='tab'>List</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("stats"); ?>'><a href='#stats' data-toggle='tab'>Stats</a></li>
              <!--<li class='ajaxTab' data-url='<?php //echo $this->url("achievements"); ?>'><a href='#achievements' data-toggle='tab'>Achievements</a></li>-->
              <li class='ajaxTab' data-url='<?php echo $this->url("friends"); ?>'><a href='#friends' data-toggle='tab'>Friends (<?php echo count($this->friends()); ?>)</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("achievements2"); ?>'><a href='#achievements2' data-toggle='tab'>Achievements (beta)</a></li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane active' id='feed'>
<?php
  if ($newEntry->allow($this->app->user, 'edit')) {
    echo $this->view('addEntryInlineForm');
  }
  if ($this->allow($this->app->user, 'comment')) {
    $blankComment = new Comment($this->app, 0, $this->app->user, $this);
?>
                <div class='addListEntryForm'>
                  <?php echo $blankComment->view('inlineForm', ['currentObject' => $this]); ?>
                </div>
<?php
  }
?>
                <?php echo $this->view('feed', ['entries' => $params['entries'], 'numEntries' => 50, 'feedURL' => $this->url('feed')]); ?>
              </div>
              <div class='tab-pane' id='anime'>
                Loading...
              </div>
              <div class='tab-pane' id='stats'>
                Loading...
              </div>
              <div class='tab-pane' id='friends'>
                Loading...
              </div>
              <div class='tab-pane' id='achievements2'>
                Loading...
              </div>
            </div>
          </div>
        </div>
      </div>