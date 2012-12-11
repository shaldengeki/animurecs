<?php
  if ($_SERVER['DOCUMENT_URI'] === $_SERVER['REQUEST_URI']) {
    echo "This partial cannot be viewed on its own.";
    exit;
  }
?>
     <div class='row-fluid'>
        <div class='span3 userProfileColumn leftColumn'>
          <ul class='thumbnails avatarContainer'>
            <li class='span12'>
              <div class='thumbnail profileAvatar'>
<?php
if ($this->avatarPath() != '') {
?>
                <img src='<?php echo joinPaths(ROOT_URL,escape_output($this->avatarPath())); ?>' class='img-rounded' alt=''>
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
  $friendSlice = $this->friends();
  shuffle($friendSlice);
  $friendSlice = array_slice($friendSlice, 0, 4);
  foreach ($friendSlice as $friendEntry) {
?>            <li class='friendGridEntry'><?php echo $friendEntry['user']->link("show", "<img class='friendGridImage' src='/".$friendEntry['user']->avatarPath()."' /><div class='friendGridUsername'>".escape_output($friendEntry['user']->username)."</div>", True); ?></li>
<?php
  }
?>
            </ul>
          </div>
        </div>
        <div class='span9 userProfileColumn rightColumn'>
          <div class='profileUserInfo'>
            <h1>
              <?php echo escape_output($this->username()); ?>
              <?php echo $this->isModerator() ? "<span class='label label-info staffUserTag'>Moderator</span>" : ""; ?>
              <?php echo $this->isAdmin() ? "<span class='label label-important staffUserTag'>Admin</span>" : ""; ?>
              <?php echo $this->allow($currentUser, "edit") ? "<small>(".$this->link("edit", "edit").")</small>" : "" ?>
              <?php echo (!$this->allow($currentUser, 'request_friend') || $this->id === $currentUser->id) ? "" : ((array_filter_by_key_property($this->friends(), 'user', 'id', $currentUser->id)) ? "<span class='pull-right'><button type='button' class='btn btn-success btn-large disabled' disabled='disabled'>Friend</button></span>" : "<span class='pull-right'><a href='".$this->url("request_friend")."' class='btn btn-primary btn-large'>Friend</a></span>"); ?>
            </h1>
            <p class='lead'>
              <?php echo escape_output($this->about()); ?>
            </p>
<?php
  if ($this->id !== $currentUser->id) {
?>            <ul class='thumbnails'>
              <li class='span4'>
                <p>Anime compatibility:</p>
                <?php echo $this->animeList()->compatibilityBar($currentUser->animeList()); ?>
              </li>
              <li class='span4'>
                
              </li>
              <li class='span4'>
                
              </li>
            </ul>
<?php
  }
?>
          </div>
          <div class='profileTabs'>
            <ul class='nav nav-tabs'>
              <li class='active ajaxTab' data-url='<?php echo $this->url("feed"); ?>'><a href='#userFeed' data-toggle='tab'>Feed</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("anime_list"); ?>'><a href='#userList' data-toggle='tab'>List</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("stats"); ?>'><a href='#userStats' data-toggle='tab'>Stats</a></li>
              <li class='ajaxTab' data-url='<?php echo $this->url("achievements"); ?>'><a href='#userAchievements' data-toggle='tab'>Achievements</a></li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane active' id='userFeed'>
<?php
  if ($this->animeList()->allow($currentUser, 'edit')) {
    echo $this->view('addEntryInlineForm');
  }
  if ($this->allow($currentUser, 'comment')) {
    $blankComment = new Comment($this->dbConn, 0, $currentUser, $this);
?>
                <div class='addListEntryForm'>
                  <?php echo $blankComment->view('inlineForm', $currentUser, array('currentObject' => $this)); ?>
                </div>
<?php
  }
?>
                <?php echo $this->profileFeed($currentUser); ?>
              </div>
              <div class='tab-pane' id='userList'>
                Loading...
              </div>
              <div class='tab-pane' id='userStats'>
                Loading...
              </div>
              <div class='tab-pane' id='userAchievements'>
                Loading...
              </div>
            </div>
          </div>
        </div>
      </div>