<?php
class User extends BaseObject {
  protected $username;
  protected $name;
  protected $email;
  protected $about;
  protected $usermask;
  protected $createdAt;
  protected $lastActive;
  protected $lastIP;
  protected $avatarPath;

  public $switchedUser;

  protected $animeList;
  protected $friends;
  protected $friendRequests;
  protected $requestedFriends;
  protected $ownComments;
  protected $profileComments;

  public function __construct(DbConn $database, $id=Null) {
    parent::__construct($database, $id);
    $this->modelTable = "users";
    $this->modelPlural = "users";
    if ($id === 0) {
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = 0;
      $this->email = $this->about = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = "";
      $this->switchedUser = $this->friends = $this->friendRequests = $this->requestedFriends = $this->ownComments = $this->profileComments = [];
      $this->animeList = new AnimeList($this->dbConn, 0);
    } else {
      if (isset($_SESSION['switched_user'])) {
        $this->switchedUser = intval($_SESSION['switched_user']);
      }
      $this->username = $this->name = $this->email = $this->about = $this->usermask = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = $this->friends = $this->friendRequests = $this->requestedFriends = $this->animeList = $this->ownComments = $this->profileComments = Null;
    }
  }
  public function username() {
    return $this->returnInfo('username');
  }
  public function name() {
    return $this->returnInfo('name');
  }
  public function email() {
    return $this->returnInfo('email');
  }
  public function about() {
    return $this->returnInfo('about');
  }
  public function usermask() {
    return $this->returnInfo('usermask');
  }
  public function createdAt() {
    return new DateTime($this->returnInfo('createdAt'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function lastActive() {
    return new DateTime($this->returnInfo('lastActive'), new DateTimeZone(SERVER_TIMEZONE));
  }
  public function lastIP() {
    return $this->returnInfo('lastIP');
  }
  public function avatarPath() {
    return $this->returnInfo('avatarPath');
  }
  public function getFriends($status=1) {
    // returns a list of user,time,message arrays corresponding to all friends of this user.
    // keyed by not-this-userID.
    $friendReqs = $this->dbConn->stdQuery("SELECT `user_id_1`, `user_id_2`, `u1`.`username` AS `username_1`, `u2`.`username` AS `username_2`, `time`, `message` FROM `users_friends`
                                            INNER JOIN `users` AS `u1` ON `u1`.`id` = `user_id_1`
                                            INNER JOIN `users` AS `u2` ON `u2`.`id` = `user_id_2`
                                            WHERE ( (`user_id_1` = ".intval($this->id)." || `user_id_2` = ".intval($this->id).") && `status` = ".intval($status).")");
    $friends = [];
    while ($req = $friendReqs->fetch_assoc()) {
      $reqArray = array('time' => $req['time'], 'message' => $req['message']);
      if (intval($req['user_id_1']) === $this->id) {
        $userID = intval($req['user_id_2']);
      } else {
        $userID = intval($req['user_id_1']);
      }
      $reqArray['user'] = new User($this->dbConn, $userID);
      $friends[$userID] = $reqArray;
    }
    return $friends;
  }
  public function friends() {
    if ($this->friends === Null) {
      $this->friends = $this->getFriends();
    }
    return $this->friends;
  }
  public function getFriendRequests($status=0) {
    // returns a list of user_id,username,time,message arrays corresponding to all outstanding friend requests directed at this user.
    // user_id_1 is the user who requested, user_id_2 is the user who confirmed.
    // ordered by time desc.
    $friendReqsQuery = $this->dbConn->stdQuery("SELECT `user_id_1`, `time`, `message` FROM `users_friends`
                                                WHERE (`user_id_2` = ".intval($this->id)." && `status` = ".intval($status).")
                                                ORDER BY `time` DESC");
    $friendReqs = [];
    while ($req = $friendReqsQuery->fetch_assoc()) {
      $friendReqs[] = array(
          'user' => new User($this->dbConn, intval($req['user_id_1'])),
          'time' => $req['time'],
          'message' => $req['message']
        );
    }
    return $friendReqs;
  }
  public function friendRequests() {
    if ($this->friendRequests === Null) {
      $this->friendRequests = $this->getFriendRequests();
    }
    return $this->friendRequests;
  }
  public function getRequestedFriends($status=0) {
    // returns a list of user_id,username,time,message arrays corresponding to all outstanding friend requests originating from this user.
    // user_id_1 is the user who requested, user_id_2 is the user who confirmed.
    // ordered by time desc.
    $friendReqsQuery = $this->dbConn->stdQuery("SELECT `user_id_2`, `time`, `message` FROM `users_friends`
                                                WHERE (`user_id_1` = ".intval($this->id)." && `status` = ".intval($status).")
                                                ORDER BY `time` DESC");
    $friendReqs = [];
    while ($req = $friendReqsQuery->fetch_assoc()) {
      $friendReqs[] = array(
          'user' => new User($this->dbConn, intval($req['user_id_2'])),
          'time' => $req['time'],
          'message' => $req['message']
        );
    }
    return $friendReqs;
  }
  public function requestedFriends() {
    if ($this->requestedFriends === Null) {
      $this->requestedFriends = $this->getRequestedFriends();
    }
    return $this->requestedFriends;
  }
  public function animeList() {
    if ($this->animeList === Null) {
      $this->animeList = new AnimeList($this->dbConn, $this->id);
    }
    return $this->animeList;
  }
  public function getOwnComments() {
    // returns a list of comment objects sent by this user.
    $ownComments = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `user_id` = ".intval($this->id)." ORDER BY `created_at` DESC");
    $comments = [];
    while ($comment = $ownComments->fetch_assoc()) {
      $comments[] = new Comment($this->dbConn, intval($comment['id']));
    }
    return $comments;
  }
  public function ownComments() {
    if ($this->ownComments === Null) {
      $this->ownComments = $this->getOwnComments();
    }
    return $this->ownComments;
  }
  public function getProfileComments() {
    // returns a list of comment objects sent by this user.
    $profileComments = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `type` = 'User' && `parent_id` = ".intval($this->id)." ORDER BY `created_at` DESC");
    $comments = [];
    while ($comment = $profileComments->fetch_assoc()) {
      $comments[] = new Comment($this->dbConn, intval($comment['id']));
    }
    return $comments;
  }
  public function profileComments() {
    if ($this->profileComments === Null) {
      $this->profileComments = $this->getProfileComments();
    }
    return $this->profileComments;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'mal_import':
      case 'edit':
        if ($authingUser->id == $this->id || ( ($authingUser->isModerator() || $authingUser->isAdmin()) && $authingUser->usermask > $this->usermask) ) {
          return True;
        }
        return False;
        break;
      case 'confirm_friend':
      case 'request_friend':
        if ($authingUser->id !== 0 && $authingUser->loggedIn() && $this->id !== 0) {
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
      case 'comment':
        if ($authingUser->loggedIn() && $authingUser->id != $this->id) {
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
      return array("location" => "/", "status" => "You have had too many unsuccessful login attempts. Please wait awhile and try again.", 'class' => 'error');
    }
  
    $bcrypt = new Bcrypt();
    $findUsername = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `usermask`, `password_hash` FROM `users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1");
    if (!$findUsername) {
      $this->log_failed_login($username, $password);
      return array("location" => "/", "status" => "Could not log in with the supplied credentials.", 'class' => 'error');
    }
    if (!$bcrypt->verify($password, $findUsername['password_hash'])) {
      $this->log_failed_login($username, $password);
      return array("location" => "/", "status" => "Could not log in with the supplied credentials.", 'class' => 'error');
    }
    
    //update last IP address and last active.
    $updateLastIP = $this->dbConn->stdQuery("UPDATE `users` SET `last_ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_active` = NOW() WHERE `id` = ".intval($findUsername['id'])." LIMIT 1");
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
    //check if user's passwords match and are of sufficient length.
    if ($password != $password_confirmation) {
        return array("location" => "/register.php", "status" => "Your passwords do not match. Please try again.");      
    }
    if (strlen($password) < 6) {
      return array("location" => "/register.php", "status" => "Your password must be at least 6 characters long.");
    }
    //check if email is well-formed.
    $email_regex = "/[0-9A-Za-z\\+\\-\\%\\.]+@[0-9A-Za-z\\.\\-]+\\.[A-Za-z]{2,4}/";
    if (!preg_match($email_regex, $email)) {
      return array("location" => "/register.php", "status" => "The email address you have entered is malformed. Please check it and try again.");
    }
    //check if user is already registered.
    $checkNameEmail = intval($this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE (`username` = ".$this->dbConn->quoteSmart($username)." || `email` = ".$this->dbConn->quoteSmart($email).")"));
    if ($checkNameEmail > 0) {
      return array("location" => "/register.php", "status" => "Your username or email has previously been registered. Please try another username.");
    }
    //register this user.
    $bcrypt = new Bcrypt();
    $registerUser = $this->dbConn->stdQuery("INSERT INTO `users` SET `username` = ".$this->dbConn->quoteSmart($username).", `name` = '', `about` = '', `email` = ".$this->dbConn->quoteSmart($email).", `password_hash` = ".$this->dbConn->quoteSmart($bcrypt->hash($password)).", `usermask` = 1, `last_ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_active` = NOW(), `created_at` = NOW(), `avatar_path` = ''");
    if (!$registerUser) {
      return array("location" => "/register.php", "status" => "Database errors were encountered during registration. Please try again later.", 'class' => 'error');
    }
    $_SESSION['id'] = intval($this->dbConn->insert_id);
    $this->id = $_SESSION['id'];
    return array("location" => $this->url("show"), "status" => "Congrats! You're now signed in as ".escape_output($username).". Why not start out by adding some anime to your list?", 'class' => 'success');
  }
  public function importMAL($malUsername) {
    // imports a user's MAL lists.
    // takes a MAL username and returns a boolean.
    $malList = parseMALList($malUsername);
    $listIDs = [];
    foreach($malList as $entry) {
      $entry['user_id'] = $this->id;
      $listIDs[$entry['anime_id']] = $this->animeList()->create_or_update($entry);
    }
    if (in_array(False, $listIDs, True)) {
      return False;
    }
    return True;
  }
  public function requestFriend(User $requestedUser, array $request) {
    // generates a friend request from the current user to requestedUser.
    // returns a boolean.
    $params = [];
    $params[] = "`message` = ".(isset($request['message']) ? $this->dbConn->quoteSmart($request['message']) : '""');
    $params[] = "`user_id_1` = ".intval($this->id);
    $params[] = "`user_id_2` = ".intval($requestedUser->id);
    $params[] = "`status` = 0";
    $params[] = "`time` = NOW()";

    // check to see if this already exists in friends or requests.
    if (array_filter_by_key_property($this->friends(), 'user', 'id', $requestedUser->id)) {
      // this friendship already exists.
      return True;
    }
    if (array_filter_by_key_property($this->friendRequests(), 'user', 'id', $requestedUser->id) || array_filter_by_key_property($this->requestedFriends(), 'user', 'id', $requestedUser->id)) {
      // this request already exists.
      return True;
    }
    // otherwise, go ahead and create a request.
    $createRequest = $this->dbConn->stdQuery("INSERT INTO `users_friends` SET ".implode(", ",$params));
    if ($createRequest) {
      return True;
    } else {
      return False;
    }
  }
  public function confirmFriend(User $requestedUser) {
    // confirms a friend request from requestedUser directed at the current user.
    // returns a boolean.
    // check to see if this already exists in friends or requests.
    if (array_filter_by_key($this->friends(), 'user_id_1', $requestedUser->id) || array_filter_by_key($this->friends(), 'user_id_2', $requestedUser->id)) {
      // this friendship already exists.
      return True;
    }
    // otherwise, go ahead and update this request.
    $updateRequest = $this->dbConn->stdQuery("UPDATE `users_friends` SET `status` = 1 WHERE `user_id_1` = ".intval($requestedUser->id)." && `user_id_2` = ".intval($this->id)." && `status` = 0 LIMIT 1");
    if ($updateRequest) {
      return True;
    } else {
      return False;
    }
  }
  public function create_or_update(array $user, User $currentUser=Null) {
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
    if (isset($user['username']) && $this->id != 0) {
      unset($user['username']);
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
      if ($file_array['tmp_name'] && is_uploaded_file($file_array['tmp_name'])) {
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
        $imagePath = $this->avatarPath();
      }

      $params[] = "`avatar_path` = ".$this->dbConn->quoteSmart($imagePath);
      $params[] = "`last_active` = NOW()";

      $updateUser = $this->dbConn->stdQuery("UPDATE `users` SET ".implode(", ", $params)." WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
    } else {
      // add this user.
      $params[] = "`created_at` = NOW()";
      $params[] = "`last_active` = NOW()";
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `users` SET ".implode(",", $params));
      if (!$insertUser) {
        return False;
      } else {
        $this->id = intval($this->dbConn->insert_id);
      }
    }

    // now process anime entries.
    // TODO ?_?

    return intval($this->id);
  }
  public function delete(array $entries=Null) {
    // delete this user from the database.
    // returns a boolean.

    // delete objects that belong to this user.
    $deleteList = $this->animeList()->delete();
    if (!$deleteList) {
      return False;
    }
    return parent::delete();
  }
  public function isModerator() {
    if (!$this->usermask() or !(intval($this->usermask()) & 2)) {
      return false;
    }
    return true;
  }
  public function isAdmin() {
    if (!$this->usermask() or !(intval($this->usermask()) & 4)) {
      return false;
    }
    return true;
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
        return array("location" => "/feed.php", "status" => "The given user to switch to doesn't exist in the database.", 'class' => 'error');
      }
      $newUser = new User($this->dbConn, $findUserID);
      $newUser->switchedUser = $_SESSION['id'];
      $_SESSION['lastLoginCheckTime'] = microtime(true);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['switched_user'] = $newUser->switchedUser;
      return array("location" => "/feed.php", "status" => "You've switched to ".urlencode($newUser->username()).".", 'class' => 'success');
    } else {
      $newUser = new User($this->dbConn, $username);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['lastLoginCheckTime'] = microtime(true);
      unset($_SESSION['switched_user']);
      return array("location" => "/feed.php", "status" => "You've switched back to ".urlencode($newUser->username()).".", 'class' => 'success');
    }
  }
  public function friendRequestsList() {
    // returns markup for the list of friend requests directed at this user.
    $serverTimezone = new DateTimeZone(SERVER_TIMEZONE);
    $outputTimezone = new DateTimeZone(OUTPUT_TIMEZONE);
    $output = "";
    foreach ($this->friendRequests() as $request) {
      $entryTime = new DateTime($request['time'], $serverTimezone);
      $entryTime->setTimezone($outputTimezone);
      $output .= "<li class='friendRequestEntry'><strong>".escape_output($request['user']->username())."</strong> requested to be your friend on ".$entryTime->format('G:i n/j/y').".".$this->link('confirm_friend', "Accept", True, Null, Null, $request['user']->id)."</li>\n";
    } 
    return $output;
  }
  public function profileFeed(User $currentUser, DateTime $maxTime=Null, $numEntries=50) {
    // returns markup for this user's profile feed.
    $feedEntries = $this->animeList()->entries($maxTime, $numEntries);
    foreach ($this->profileComments() as $comment) {
      $commentEntry = new CommentEntry($this->dbConn, intval($comment->id));
      $commentEntry->object = $comment;
      $feedEntries[] = $commentEntry;
    }
    return $this->animeList()->feed($feedEntries, $currentUser, $numEntries, "<blockquote><p>No entries yet - add some above!</p></blockquote>\n");
  }
  public function globalFeed(DateTime $maxTime=Null, $numEntries=50) {
    // returns markup for this user's global feed.

    // add each user's personal feeds to the total feed.
    $feedEntries = $this->animeList()->entries($maxTime, $numEntries);
    foreach ($this->friends() as $friend) {
      $feedEntries = array_merge($feedEntries, $friend['user']->animeList()->entries($maxTime, $numEntries));
      foreach ($friend['user']->ownComments() as $comment) {
        $commentEntry = new CommentEntry($this->dbConn, intval($comment->id));
        $commentEntry->object = $comment;
        $feedEntries[] = $commentEntry;
      }
    }
    return $this->animeList()->feed($feedEntries, $this, $numEntries, "<blockquote><p>Nothing's in your feed yet. Why not add some anime to your list?</p></blockquote>\n");
  }
  public function animeListSection($status, User $currentUser) {
    // returns markup for one status section of a user's anime list.
    $statusStrings = array(1 => array('id' => 'currentlyWatching', 'title' => 'Currently Watching'),
                          2 => array('id' => 'completed', 'title' => 'Completed'),
                          3 => array('id' => 'onHold', 'title' => 'On Hold'),
                          4 => array('id' => 'dropped', 'title' => 'Dropped'),
                          6 => array('id' => 'planToWatch', 'title' => 'Plan to Watch'));
    $relevantEntries = $this->animeList()->listSection($status);

    $output = "      <div class='".escape_output($statusStrings[$status]['id'])."'>
        <h2>".escape_output($statusStrings[$status]['title'])."</h2>
        <table class='table table-bordered table-striped dataTable' data-id='".intval($this->id)."'>
          <thead>
            <tr>
              <th>Title</th>
              <th class='dataTable-default-sort' data-sort-order='desc'>Score</th>
              <th>Episodes</th>\n";
    if ($currentUser->id == $this->id) {
      $output .= "              <th></th>\n";
    }
    $output .= "            </tr>
          </thead>
          <tbody>\n";
    foreach ($relevantEntries as $entry) {
          $output .= "          <tr data-id='".intval($entry['anime']->id)."'>
              <td class='listEntryTitle'>
                ".$entry['anime']->link("show", $entry['anime']->title)."
                <span class='pull-right hidden listEntryStatus'>
                  ".display_status_dropdown("anime_list[status]", "span12", intval($entry['status']))."
                </span>
              </td>
              <td class='listEntryScore'>".(intval($entry['score']) > 0 ? intval($entry['score'])."/10" : "")."</td>
              <td class='listEntryEpisode'>".intval($entry['episode'])."/".(intval($entry['anime']->episodeCount) == 0 ? "?" : intval($entry['anime']->episodeCount))."</td>\n";
          if ($currentUser->id == $this->id) {
            $output .= "              <td><a href='#' class='listEdit' data-url='/anime_lists/'><i class='icon-pencil'></i></td>\n";
          }
          $output .="            </tr>\n";
    }
    $output .= "          <tbody>
        </table>      </div>\n";
    return $output;
  }
  public function displayAnimeList(User $currentUser) {
    // returns markup for this user's anime list.
    $output = "";
    $output .= $this->animeListSection(1, $currentUser);
    $output .= $this->animeListSection(2, $currentUser);
    $output .= $this->animeListSection(3, $currentUser);
    $output .= $this->animeListSection(4, $currentUser);
    $output .= $this->animeListSection(6, $currentUser);
    return $output;
  }
  public function displayStats() {
    /* TODO: user stats. */
  }
  public function switchForm() {
    return "<form action='".$this->url("switch_user")."' method='POST' class='form-horizontal'>
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
  public function profile(User $currentUser) {
    // displays a user's profile.
    // info header.
    $output = "     <div class='row-fluid'>
        <div class='span3 userProfileColumn leftColumn'>
          <ul class='thumbnails avatarContainer'>
            <li class='span12'>
              <div class='thumbnail profileAvatar'>\n";
    if ($this->avatarPath() != '') {
      $output .= "                <img src='".joinPaths(array(ROOT_URL,escape_output($this->avatarPath())))."' class='img-rounded' alt=''>\n";
    } else {
      $output .= "                <img src='/img/users/blank.png' class='img-rounded' alt=''>\n";
    }
    $output .= "          </div>
            </li>
          </ul>
          <div class='friendListBox'>
            <h3>Friends".($this->friends() ? " (".count($this->friends()).") " : "")."</h3>
            <ul class='friendGrid'>\n";
    $friendSlice = $this->friends();
    shuffle($friendSlice);
    $friendSlice = array_slice($friendSlice, 0, 4);
    foreach ($friendSlice as $friendEntry) {
      $output .= "            <li class='friendGridEntry'>".$friendEntry['user']->link("show", "<img class='friendGridImage' src='/".$friendEntry['user']->avatarPath()."' /><div class='friendGridUsername'>".escape_output($friendEntry['user']->username)."</div>", True)."</li>\n";
    }
    $output .= "            </ul>
          </div>
        </div>
        <div class='span9 userProfileColumn rightColumn'>
          <div class='profileUserInfo'>
            <h1>
              ".escape_output($this->username())." 
              ".($this->isModerator() ? "<span class='label label-info staffUserTag'>Moderator</span>" : "").
              ($this->isAdmin() ? "<span class='label label-important staffUserTag'>Admin</span>" : "").
              ($this->allow($currentUser, "edit") ? "<small>(".$this->link("edit", "edit").")</small>" : "").
              (($this->id === $currentUser->id) ? "" : ((array_filter_by_key_property($this->friends(), 'user', 'id', $currentUser->id)) ? "<span class='pull-right'><button type='button' class='btn btn-success btn-large disabled' disabled='disabled'>Friend</button></span>" : "<span class='pull-right'><a href='".$this->url("request_friend")."' class='btn btn-primary btn-large'>Friend</a></span>"))."</h1>
            <p class='lead'>
              ".escape_output($this->about())."
            </p>\n";
    if ($this->id !== $currentUser->id) {
      $output .= "            <ul class='thumbnails'>
              <li class='span4'>
                <p>Anime compatibility:</p>
                ".$this->animeList()->compatibilityBar($currentUser->animeList())."
              </li>
              <li class='span4'>
                
              </li>
              <li class='span4'>
                
              </li>
            </ul>\n";
    }
    $output .= "          </div>
          <div class='profileTabs'>
            <ul class='nav nav-tabs'>
              <li class='active'><a href='#userFeed' data-toggle='tab'>Feed</a></li>
              <li><a href='#userList' data-toggle='tab'>List</a></li>
              <li><a href='#userStats' data-toggle='tab'>Stats</a></li>
              <li><a href='#userAchieves' data-toggle='tab'>Achievements</a></li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane active' id='userFeed'>\n";
    if ($this->animeList()->allow($currentUser, 'edit')) {
      $animeList = new AnimeList($this->dbConn, 0);
      $anime = new Anime($this->dbConn, 0);
      $output .= "                <div class='addListEntryForm'>
                  <form class='form-inline' action='".$animeList->url("new", array("user_id" => intval($this->id)))."' method='POST'>
                    <input name='anime_list[user_id]' id='anime_list_user_id' type='hidden' value='".intval($this->id)."' />
                    <input name='anime_list_anime_title' id='anime_list_anime_title' type='text' class='autocomplete input-xlarge' data-labelField='title' data-valueField='id' data-url='".$anime->url("token_search")."' data-tokenLimit='1' data-outputElement='#anime_list_anime_id' placeholder='Have an anime to update? Type it in!' />
                    <input name='anime_list[anime_id]' id='anime_list_anime_id' type='hidden' value='' />
                    ".display_status_dropdown("anime_list[status]", "span2")."
                    <div class='input-append'>
                      <input class='input-mini' name='anime_list[score]' id='anime_list_score' type='number' min='0' max='10' step='1' value='0' />
                      <span class='add-on'>/10</span>
                    </div>
                    <div class='input-prepend'>
                      <span class='add-on'>Ep</span>
                      <input class='input-mini' name='anime_list[episode]' id='anime_list_episode' type='number' min='0' step='1' />
                    </div>
                    <input type='submit' class='btn btn-primary updateEntryButton' value='Update' />
                  </form>
                </div>\n";
    }
    $blankComment = new Comment($this->dbConn, 0, $currentuser, $this);
    if ($blankComment->allow($currentUser, 'new')) {
      $output .= "                <div class='addListEntryForm'>
                  ".$blankComment->inlineForm($currentUser, $this)."
                </div>\n";
    }
    $output .= "                ".$this->profileFeed($currentUser)."
              </div>
              <div class='tab-pane' id='userList'>
                ".$this->displayAnimeList($currentUser)."
              </div>
              <div class='tab-pane' id='userStats'>
                Stats coming soon!
              </div>
              <div class='tab-pane' id='userAchieves'>
                Achievements coming soon!
              </div>
            </div>
          </div>
        </div>
      </div>\n";
    return $output;
  }
  public function form(User $currentUser) {
    $output = "<form action='".(($this->id === 0) ? $this->url("new") : $this->url("edit"))."' method='POST' enctype='multipart/form-data' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='user[id]' value='".intval($this->id)."' />")."
      <fieldset>
        <div class='control-group'>
          <label class='control-label' for='user[name]'>Name</label>
          <div class='controls'>
            <input name='user[name]' type='text' class='input-xlarge' id='user[name]'".(($this->id === 0) ? "" : " value='".escape_output($this->name())."'").">
          </div>
        </div>";
    if ($this->id === 0) {
      $output .= "        <div class='control-group'>
          <label class='control-label' for='user[username]'>Username</label>
          <div class='controls'>
            <input name='user[username]' type='text' class='input-xlarge' id='user[username]'".(($this->id === 0) ? "" : " value='".escape_output($this->username())."'").">
          </div>
        </div>\n";
    }
    $output .= "        <div class='control-group'>
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
            <input name='user[email]' type='email' class='input-xlarge' id='user[email]'".(($this->id === 0) ? "" : " value='".escape_output($this->email())."'").">
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label' for='user[about]'>About</label>
          <div class='controls'>
            <textarea name='user[about]' id='user[about]' rows='5'>".(($this->id === 0) ? "" : escape_output($this->about()))."</textarea>
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
          <div class='controls'>\n".display_user_roles_select("user[usermask][]", ($this->id === 0) ? 0 : intval($this->usermask()))."      </div>
        </div>\n";
        } else {
          $output .= "      <input type='hidden' name='user[usermask][]' value='".($this->id === 0 ? 1 : intval($this->usermask()))."' />\n";
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