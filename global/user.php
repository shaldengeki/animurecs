<?php

class User {
  public $dbConn;
  public $id;
  public $username;
  public $name;
  public $email;
  public $about;
  public $usermask;
  public $createdAt;
  public $lastActive;
  public $lastIP;
  public $avatarPath;
  public $lastLoginCheckTime;
  public $animeEntries;
  public $animeList;
  public function __construct($database, $id=Null) {
    $this->dbConn = $database;
    if ($id === 0) {
      $this->id = 0;
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = 0;
      $this->email = $this->about = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = "";
      $this->animeEntries = $this->animeList = [];
    } else {
      $userInfo = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `email`, `about`, `usermask`, `last_ip`, `created_at`, `last_active`, `avatar_path` FROM `users` WHERE `id` = ".intval($id)." LIMIT 1");
      $this->id = intval($userInfo['id']);
      $this->username = $userInfo['username'];
      $this->name = $userInfo['name'];
      $this->email = $userInfo['email'];
      $this->about = $userInfo['about'];
      $this->usermask = intval($userInfo['usermask']);
      $this->createdAt = $userInfo['created_at'];
      $this->lastActive = $userInfo['last_active'];
      $this->lastIP = $userInfo['last_ip'];
      $this->avatarPath = $userInfo['avatar_path'];
      $this->animeEntries = $this->getAnimeEntries();
      $this->animeList = $this->getAnimeList();
    }
  }
  public function allow($authingUser, $action) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'edit':
        if ($authingUser->id == $this->id || ( ($authingUser->isModerator() || $authingUser->isAdmin()) && $authingUser->usermask > $this->usermask) ) {
          return True;
        }
        return False;
        break;
      case 'new':
        if (!$authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      case 'delete':
        if ($authingUser->isAdmin() && !$this->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'switch_user':
        if ($authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      case 'switch_back':
      case 'show':
      case 'index':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function loggedIn() {
    //if userID is not proper, or if user's last IP was not the requester's IP, return false.
    if (intval($this->id) <= 0) {
      return False;
    }
    if (($this->id == $_SESSION['id']) && $_SESSION['lastLoginCheckTime'] > microtime(true) - 1) {
      return True;
    } elseif (isset($_SESSION['switched_user'])) {
      $checkID = $_SESSION['switched_user'];
    } else {
      $checkID = $this->id;
    }
    $thisUserInfo = $this->dbConn->queryFirstRow("SELECT `last_ip` FROM `users` WHERE `id` = ".intval($checkID)." LIMIT 1");
    if (!$thisUserInfo || $thisUserInfo['last_ip'] != $_SERVER['REMOTE_ADDR']) {
      return False;
    }
    $_SESSION['lastLoginCheckTime'] = microtime(true);
    return True;
  }
  public function log_failed_login($username, $password) {
    $insert_log = $this->dbConn->stdQuery("INSERT IGNORE INTO `failed_logins` (`ip`, `time`, `username`, `password`) VALUES ('".$_SERVER['REMOTE_ADDR']."', NOW(), ".$this->dbConn->quoteSmart($username).", ".$this->dbConn->quoteSmart($password).")");
  }
  public function logIn($username, $password) {
    // rate-limit requests.
    $numFailedRequests = $this->dbConn->queryCount("SELECT COUNT(*) FROM `failed_logins` WHERE `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR'])." AND `time` > NOW() - INTERVAL 1 HOUR");
    if ($numFailedRequests > 5) {
      return array("location" => "index.php", "status" => "You have had too many unsuccessful login attempts. Please wait awhile and try again.", 'class' => 'error');
    }
  
    $bcrypt = new Bcrypt();
    $findUsername = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `usermask`, `password_hash` FROM `users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1");
    if (!$findUsername) {
      $this->log_failed_login($username, $password);
      return array("location" => "index.php", "status" => "Could not log in with the supplied credentials.", 'class' => 'error');
    }
    if (!$bcrypt->verify($password, $findUsername['password_hash'])) {
      $this->log_failed_login($username, $password);
      return array("location" => "index.php", "status" => "Could not log in with the supplied credentials.", 'class' => 'error');
    }
    
    //update last IP address and last active.
    $updateLastIP = $this->dbConn->stdQuery("UPDATE `users` SET `last_ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_active` = ".$this->dbConn->quoteSmart(unixToMySQLDateTime())." WHERE `id` = ".intval($findUsername['id'])." LIMIT 1");
    $_SESSION['id'] = $findUsername['id'];
    $_SESSION['name'] = $findUsername['name'];
    $_SESSION['username'] = $findUsername['username'];
    $_SESSION['usermask'] = $findUsername['usermask'];
    $this->id = intval($findUsername['id']);
    $this->username = $findUsername['username'];
    $this->name = $findUsername['name'];
    $this->usermask = intval($findUsername['usermask']);
    return array("location" => "/feed.php", "status" => "Successfully logged in.", 'class' => 'success');
  }
  public function register($username, $email, $password, $password_confirmation) {
    //check if user's passwords match.
    if ($password != $password_confirmation) {
        $returnArray = array("location" => "register.php", "status" => "Your passwords do not match. Please try again.");      
    } else {
      //check if email is well-formed.
      $email_regex = "/[0-9A-Za-z\\+\\-\\%\\.]+@[0-9A-Za-z\\.\\-]+\\.[A-Za-z]{2,4}/";
      if (!preg_match($email_regex, $email)) {
        $returnArray = array("location" => "register.php", "status" => "The email address you have entered is malformed. Please check it and try again.");
      } else {
        //check if user is already registered.
        $checkNameEmail = intval($this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE (`username` = ".$this->dbConn->quoteSmart($username)." || `email` = ".$this->dbConn->quoteSmart($email).")"));
        if ($checkNameEmail > 0) {
          $returnArray = array("location" => "register.php", "status" => "Your username or email has previously been registered. Please try logging in.");
        } else {
          //register this user.
          $bcrypt = new Bcrypt();
          $registerUser = $this->dbConn->stdQuery("INSERT INTO `users` SET `username` = ".$this->dbConn->quoteSmart($username).", `name` = '', `email` = ".$this->dbConn->quoteSmart($email).", `password_hash` = ".$this->dbConn->quoteSmart($bcrypt->hash($password)).", `usermask` = 1, `last_ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_active` = ".$this->dbConn->quoteSmart(unixToMySQLDateTime()).", `created_at` = ".$this->dbConn->quoteSmart(unixToMySQLDateTime()).", `avatar_path` = ''");
          if (!$registerUser) {
            $returnArray = array("location" => "register.php", "status" => "Database errors were encountered during registration. Please try again later.", 'class' => 'error');
          } else {
            $returnArray = array("location" => "register.php", "status" => "Registration successful. You can now log in as ".escape_output($username).".", 'class' => 'success');
          }
        }
      }
    }
    return $returnArray;
  }
  public function delete() {
    // delete this user from the database.
    // returns a boolean.
    $deleteUser = $this->dbConn->stdQuery("DELETE FROM `users` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$deleteUser) {
      return False;
    }
    return True;
  }
  public function create_or_update($user) {
    // creates or updates a user based on the parameters passed in $user and this object's attributes.
    // returns False if failure, or the ID of the user if success.

    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($user['password']) && $user['password'] != '') {
      $bcrypt = new Bcrypt();
      $user['password_hash'] = $bcrypt->hash($user['password']);
    }
    unset($user['password']);
    unset($user['password_confirmation']);
    if (isset($user['usermask']) && intval(@array_sum($user['usermask'])) != 0) {
      $user['usermask'] = intval(@array_sum($user['usermask']));
    } else {
      unset($user['usermask']);
    }

    $params = array();
    foreach ($user as $parameter => $value) {
      if (!is_array($value)) {
        $params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
      }
    }
    //go ahead and register or update this user.
    if ($this->id != 0) {
      //update this user.
      // process uploaded image.
      $file_array = $_FILES['avatar_image'];
      $imagePath = "";
      if (!empty($file_array['tmp_name']) && is_uploaded_file($file_array['tmp_name'])) {
        if ($file_array['error'] != UPLOAD_ERR_OK) {
          return False;
        }
        $file_contents = file_get_contents($file_array['tmp_name']);
        if (!$file_contents) {
          return False;
        }
        $newIm = @imagecreatefromstring($file_contents);
        if (!$newIm) {
          return False;
        }
        $imageSize = getimagesize($file_array['tmp_name']);
        if ($imageSize[0] > 300 || $imageSize[1] > 300) {
          return False;
        }
        // move file to destination and save path in db.
        if (!is_dir(joinPaths(APP_ROOT, "img", "users", intval($this->id)))) {
          mkdir(joinPaths(APP_ROOT, "img", "users", intval($this->id)));
        }
        $imagePathInfo = pathinfo($file_array['tmp_name']);
        $imagePath = joinPaths("img", "users", intval($this->id), $this->id.image_type_to_extension($imageSize[2]));
        if (!move_uploaded_file($file_array['tmp_name'], $imagePath)) {
          return False;
        }
      } else {
        $imagePath = $this->avatarPath;
      }

      $updateUser = $this->dbConn->stdQuery("UPDATE `users` SET ".implode(", ", $params).", `avatar_path` = ".$this->dbConn->quoteSmart($imagePath).", `last_active` = NOW()  WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
      return intval($this->id);
    } else {
      // add this user.
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `users` SET ".implode(",", $params).", `created_at` = NOW(), `last_active` = NOW()");
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
        return $this->id;
      }
    }
  }
  public function isModerator() {
    if (!$this->usermask or !(intval($this->usermask) & 2)) {
      return false;
    }
    return true;
  }
  public function isAdmin() {
    if (!$this->usermask or !(intval($this->usermask) & 4)) {
      return false;
    }
    return true;
  }
  public function getAnimeEntries() {
    // retrieves a list of id arrays corresponding to anime list entries belonging to this user.
    return $this->dbConn->queryAssoc("SELECT `id` FROM `anime_lists` WHERE `user_id` = ".intval($this->id)." ORDER BY `time` DESC", "id");
  }
  public function getAnimeList() {
    // retrieves a list of anime_id, time, status, score, episode arrays corresponding to the latest list entry for each anime this user has watched.
    return $this->dbConn->queryAssoc("SELECT `anime_id`, `time`, `score`, `status`, `episode` FROM (
                                        SELECT MAX(`id`) AS `id` FROM `anime_lists`
                                        WHERE `user_id` = ".intval($this->id)."
                                        GROUP BY `anime_id`
                                      ) `p` INNER JOIN `anime_lists` ON `anime_lists`.`id` = `p`.`id`
                                      ORDER BY `status` ASC, `score` DESC", "anime_id");
  }
  public function switchUser($username, $switch_back=True) {
    /*
      Switches the current user's session out for another user (provided by $username) in the etiStats db.
      If $switch_back is true, packs the current session into $_SESSION['switched_user'] before switching.
      If not, then retrieves the packed session and overrides current session with that info.
      Returns a redirect_to array.
    */
    if ($switch_back) {
      // get user entry in database.
      $findUserID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." && `id` != ".$this->id." LIMIT 1"));
      if (!$findUserID) {
        return array("location" => "feed.php", "status" => "The given user to switch to doesn't exist in the database.", 'class' => 'error');
      }
      $newUser = new User($this->dbConn, $findUserID);
      $newUser->switched_user = $_SESSION['id'];
      $_SESSION['lastLoginCheckTime'] = $newUser->lastLoginCheckTime = microtime(true);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['switched_user'] = $newUser->switched_user;
    } else {
      $newUser = new User($this->dbConn, $_SESSION['switched_user']);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['lastLoginCheckTime'] = microtime(true);
      unset($_SESSION['switched_user']);
    }
  }
  public function link($action="show", $text="Profile", $raw=False) {
    // returns an HTML link to the current user's profile, with text provided.
    return "<a href='/user.php?action=".urlencode($action)."&id=".intval($this->id)."'>".($raw ? $text : escape_output($text))."</a>";
  }
  public function animeFeed($maxTime=False,$numEntries=50) {
    // returns markup for this user's anime feed.
    $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
    $nowTime = new DateTime();
    $nowTime->setTimezone($outputTimezone);
    if ($maxTime === False) {
      $maxTime = $nowTime;
    }
    $feedEntries = $this->dbConn->stdQuery("SELECT `anime_lists`.`anime_id`, `time`, `status`, `score`, `episode` FROM `anime_lists` INNER JOIN `anime` ON `anime`.`id` = `anime_lists`.`anime_id`
                                            WHERE `user_id` = ".intval($this->id)." && `time` < ".$this->dbConn->quoteSmart($maxTime->format("Y-m-d H:i:s"))."
                                            ORDER BY `time` DESC LIMIT ".intval($numEntries));
    $output = "<ul class='userFeed'>\n";

    // the status messages we build will be different depending on 1) whether or not this is the first entry, and 2) what the status actually is.
    $statusStrings = array(0 => array(0 => "did something mysterious with [ANIME]",
                                      1 => "is now watching [ANIME]",
                                      2 => "marked [ANIME] as completed",
                                      3 => "marked [ANIME] as on-hold",
                                      4 => "marked [ANIME] as dropped",
                                      6 => "plans to watch [ANIME]"),
                            1 => array(0 => "removed [ANIME]",
                                      1 => "started watching [ANIME]",
                                      2 => "finished [ANIME]",
                                      3 => "put [ANIME] on hold",
                                      4 => "dropped [ANIME]",
                                      6 => "now plans to watch [ANIME]"));
    $scoreStrings = array(0 => array("rated [ANIME] a [SCORE]/10", "and rated it a [SCORE]/10"),
                          1 => array("unrated [ANIME]", "and unrated it"));
    $episodeStrings = array("is now watching episode [EPISODE]/[TOTAL_EPISODES] of [ANIME]", "and finished episode [EPISODE]/[TOTAL_EPISODES]");

    $cachedAnime = [];

    while ($entry = $feedEntries->fetch_assoc()) {
      // fetch the previous feed entry and compare values against current entry.
      if (!isset($cachedAnime[intval($entry['anime_id'])])) {
        $cachedAnime[intval($entry['anime_id'])] = new Anime($this->dbConn, intval($entry['anime_id']));
      }
      $entryAnime = $cachedAnime[intval($entry['anime_id'])];

      $entryTime = new DateTime($entry['time'], $outputTimezone);
      $diffInterval = $nowTime->diff($entryTime);
      $prevEntry = $this->dbConn->queryFirstRow("SELECT `status`, `score`, `episode` FROM `anime_lists`
                                                  WHERE `user_id` = ".intval($this->id)." && `anime_id` = ".intval($entryAnime->id)." && `time` < ".$this->dbConn->quoteSmart($entryTime->format("Y-m-d H:i:s"))."
                                                  ORDER BY `time` DESC LIMIT 1");
      if (!$prevEntry) {
        $prevEntry = array('status' => 0, 'score' => 0, 'episode' => 0);
      }
      $statusChanged = (bool) ($entry['status'] != $prevEntry['status']);
      $scoreChanged = (bool) ($entry['score'] != $prevEntry['score']);
      $episodeChanged = (bool) ($entry['episode'] != $prevEntry['episode']);
      
      // concatenate appropriate parts of this status text.
      $statusTexts = [];
      if ($statusChanged) {
        $statusTexts[] = $statusStrings[intval((bool)$prevEntry)][intval($entry['status'])];
      }
      if ($scoreChanged) {
        $statusTexts[] = $scoreStrings[intval($entry['score'] == 0)][intval($statusChanged)];
      }
      if ($episodeChanged) {
        $statusTexts[] = $episodeStrings[intval($statusChanged || $scoreChanged)];
      }
      $statusText = implode(" ", $statusTexts);

      // replace placeholders.
      $statusText = str_replace("[ANIME]", $entryAnime->link("show", $entryAnime->title), $statusText);
      $statusText = str_replace("[SCORE]", $entry['score'], $statusText);
      $statusText = str_replace("[EPISODE]", $entry['episode'], $statusText);
      $statusText = str_replace("[TOTAL_EPISODES]", $entryAnime->episodeCount, $statusText);
      $statusText = ucfirst($statusText);
      if ($statusText != '') {
        $output .= "  <li class='feedEntry row-fluid'><div class='feedDate' data-time='".$entryTime->format('U')."'>".ago($diffInterval)."</div><div class='feedAvatar'>".$this->link("show", "<img class='feedAvatarImg' src='".escape_output($this->avatarPath)."' />", True)."</div><div class='feedText'><div class='feedUser'>".$this->link("show", $this->username)."</div>".$statusText.".</div></li>\n";
      }
    }
    $output .= "</ul>\n";
    return $output;
  }
  public function animeListSection($status) {
    // returns markup for one status section of a user's anime list.
    $statusStrings = array(1 => array('id' => 'currentlyWatching', 'title' => 'Currently Watching'),
                          2 => array('id' => 'completed', 'title' => 'Completed'),
                          3 => array('id' => 'onHold', 'title' => 'On Hold'),
                          4 => array('id' => 'dropped', 'title' => 'Dropped'),
                          6 => array('id' => 'planToWatch', 'title' => 'Plan to Watch'));

    $relevantEntries = [];
    foreach ($this->animeList as $entry) {
      if ($entry['status'] == $status) {
        $relevantEntries[] = $entry;
      }
    }
    $output = "      <div class='".escape_output($statusStrings[$status]['id'])."'>
        <h2>".escape_output($statusStrings[$status]['title'])."</h2>
        <table class='table table-bordered table-striped dataTable'>
          <thead>
            <tr>
              <th>Title</th>
              <th class='dataTable-default-sort' data-sort-order='desc'>Score</th>
              <th>Episodes</th>
            </tr>
          </thead>
          <tbody>\n";
    foreach ($relevantEntries as $entry) {
          $anime = new Anime($this->dbConn, intval($entry['anime_id']));
          $output .= "          <tr>
              <td>".$anime->link("show", $anime->title)."</td>
              <td>".(intval($entry['score']) > 0 ? intval($entry['score'])."/10" : "")."</td>
              <td>".intval($entry['episode'])."/".intval($anime->episodeCount)."</td>
            </tr>\n";
    }
    $output .= "          <tbody>
        </table>      </div>\n";
    return $output;
  }
  public function animeList() {
    // returns markup for this user's anime list.
    $output = "";
    $output .= $this->animeListSection(1);
    $output .= $this->animeListSection(2);
    $output .= $this->animeListSection(3);
    $output .= $this->animeListSection(4);
    $output .= $this->animeListSection(6);
    return $output;
  }
  public function switchForm() {
    return "<form action='user.php' method='POST' class='form-horizontal'>
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='switch_username'>Username</label>
          <div class='controls'>
            <input name='switch_username' type='text' class='input-xlarge' id='switch_username' />
          </div>
        </div>
        <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>Switch</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>Back</a>
        </div>
      </fieldset>\n</form>\n";
  }
  public function profile($currentUser) {
    // displays a user's profile.

    // info header.
    $output = "     <div class='row-fluid'>
        <div class='span3'>
          <ul class='thumbnails avatarContainer'>
            <li class='span12'>
              <div class='thumbnail profileAvatar'>\n";
    if ($this->avatarPath != '') {
      $output .= "                <img src='".escape_output($this->avatarPath)."' class='img-rounded' alt=''>\n";
    } else {

    }
    $output .= "          </div>
            </li>
          </ul>
        </div>
        <div class='span9'>
          <div class='profileUserInfo'>
            <h1>".escape_output($this->username).($this->allow($currentUser, "edit") ? " <small>(".$this->link("edit", "edit").")</small>" : "")."</h1>
            ".($this->isModerator() ? "<span class='modUserTag'>Moderator</span>" : "").($this->isAdmin() ? "<span class='adminUserTag'>Admin</span>" : "")."
            <p class='lead'>
              ".escape_output($this->about)."
            </p>
          </div>
          <ul class='nav nav-tabs'>
            <li class='active'><a href='#userFeed' data-toggle='tab'>Feed</a></li>
            <li><a href='#userList' data-toggle='tab'>List</a></li>
            <li><a href='#userFriends' data-toggle='tab'>Friends</a></li>
          </ul>
          <div class='tab-content'>
            <div class='tab-pane active' id='userFeed'>".$this->animeFeed()."</div>
            <div class='tab-pane' id='userList'>".$this->animeList().".</div>
            <div class='tab-pane' id='userFriends'>Friends here.</div>
          </div>
        </div>
      </div>\n";
    return $output;
  }
  public function form($currentUser) {
    $output = "<form action='user.php".(($this->id === 0) ? "" : "?id=".intval($this->id))."' method='POST' enctype='multipart/form-data' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='user[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='user[name]'>Name</label>
          <div class='controls'>
            <input name='user[name]' type='text' class='input-xlarge' id='user[name]'".(($this->id === 0) ? "" : " value='".escape_output($this->name)."'").">
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[username]'>Username</label>
          <div class='controls'>
            <input name='user[username]' type='text' class='input-xlarge' id='user[username]'".(($this->id === 0) ? "" : " value='".escape_output($this->username)."'").">
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[password]'>Password</label>
          <div class='controls'>
            <input name='user[password]' type='password' class='input-xlarge' id='user[password]' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[password_confirmation]'>Confirm Password</label>
          <div class='controls'>
            <input name='user[password_confirmation]' type='password' class='input-xlarge' id='user[password_confirmation]' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[email]'>Email</label>
          <div class='controls'>
            <input name='user[email]' type='email' class='input-xlarge' id='user[email]'".(($this->id === 0) ? "" : " value='".escape_output($this->email)."'").">
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[about]'>About</label>
          <div class='controls'>
            <textarea name='user[about]' id='user[about]' rows='5'>".(($this->id === 0) ? "" : escape_output($this->about))."</textarea>
          </div>
        </div>\n";
        if ($this->id != 0) {
          $output .= "        <div class='control-group'>
          <label class='control-label' for='avatar_image'>Avatar</label>
          <div class='controls'>
            <input name='avatar_image' class='input-file' type='file' onChange='displayImagePreview(this.files);' /><span class='help-inline'>Max size 300x300, JPEG/PNG/GIF.</span>
          </div>
        </div>\n";
        }
        if ($currentUser->isAdmin()) {
          $output .= "      <div class='control-group'>
          <label class='control-label' for='user[usermask]'>Role(s)</label>
          <div class='controls'>\n".display_user_roles_select("user[usermask][]", ($this->id === 0) ? 0 : intval($this->usermask))."      </div>
        </div>\n";
        } else {
          $output .= "      <input type='hidden' name='user[usermask][]' value='".($this->id === 0 ? 1 : intval($this->usermask))."' />\n";
        }
        $output .= "    <div class='form-actions'>
          <button type='submit' class='btn btn-primary'>".(($this->id === 0) ? "Sign Up" : "Save changes")."</button>
          <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($this->id === 0) ? "Go back" : "Discard changes")."</a>
        </div>
      </fieldset>\n</form>\n";
    return $output;
  }
}

?>