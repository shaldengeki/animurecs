<?php
class User extends BaseObject {
  use Commentable;

  protected $username;
  protected $name;
  protected $email;
  protected $about;
  protected $usermask;
  protected $achievementMask;
  protected $lastActive;
  protected $lastIP;
  protected $avatarPath;

  public $switchedUser;

  protected $animeList;
  protected $friends;
  protected $friendRequests;
  protected $requestedFriends;
  protected $ownComments;

  public function __construct(Application $app, $id=Null, $username=Null) {
    if ($username !== Null) {
      $id = intval($app->dbConn->queryFirstValue("SELECT `id` FROM `users` WHERE `username` = ".$app->dbConn->quoteSmart($username)." LIMIT 1"));
    }
    parent::__construct($app, $id);
    $this->modelTable = "users";
    $this->modelPlural = "users";
    if ($id === 0) {
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = 0;
      $this->email = $this->about = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = "";
      $this->switchedUser = $this->friends = $this->friendRequests = $this->requestedFriends = $this->ownComments = $this->comments = [];
      $this->animeList = new AnimeList($this->app, 0);
    } else {
      if (isset($_SESSION['switched_user'])) {
        $this->switchedUser = intval($_SESSION['switched_user']);
      }
      $this->username = $this->name = $this->email = $this->about = $this->usermask = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = $this->friends = $this->friendRequests = $this->requestedFriends = $this->animeList = $this->ownComments = $this->comments = Null;
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
  public function usermaskText() {
    $roles = [];
    if ($this->usermask() == 0) {
      return "Guest";
    }
    if ($this->usermask() & 4) {
      $roles[] = "Administrator";
    }
    if ($this->usermask() & 2) {
      $roles[] = "Moderator";
    }
    if ($this->usermask() & 1) {
      $roles[] = "User";
    }
    if (!$roles) {
      return "Unknown";
    }
    return implode(", ", $roles);
  }
  public function achievementMask() {
    return $this->returnInfo('achievementMask');
  }
  public function lastActive() {
    return new DateTime($this->returnInfo('lastActive'), new DateTimeZone(Config::SERVER_TIMEZONE));
  }
  public function lastIP() {
    return $this->returnInfo('lastIP');
  }
  public function avatarPath() {
    return $this->returnInfo('avatarPath');
  }
  public function avatarImage(array $params=Null) {
    $imageParams = [];
    if (is_array($params) && $params) {
      foreach ($params as $key => $value) {
        $imageParams[] = escape_output($key)."='".escape_output($value)."'";
      }
    }
    return "<img src='".joinPaths(Config::ROOT_URL, escape_output($this->avatarPath()))."' ".implode(" ", $imageParams)." />";
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
      $reqArray['user'] = new User($this->app, $userID);
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
          'user' => new User($this->app, intval($req['user_id_1'])),
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
          'user' => new User($this->app, intval($req['user_id_2'])),
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
      $this->animeList = new AnimeList($this->app, $this->id);
    }
    return $this->animeList;
  }
  public function getOwnComments() {
    // returns a list of comment objects sent by this user.
    $ownComments = $this->dbConn->stdQuery("SELECT `id` FROM `comments` WHERE `user_id` = ".intval($this->id)." ORDER BY `created_at` DESC");
    $comments = [];
    while ($comment = $ownComments->fetch_assoc()) {
      $comments[] = new Comment($this->app, intval($comment['id']));
    }
    return $comments;
  }
  public function ownComments() {
    if ($this->ownComments === Null) {
      $this->ownComments = $this->getOwnComments();
    }
    return $this->ownComments;
  }
  public function allow(User $authingUser, $action, array $params=Null) {
    // takes a user object and an action and returns a bool.
    switch($action) {
      case 'mal_import':
      case 'edit':
        if ($authingUser->id == $this->id || ( ($authingUser->isStaff()) && $authingUser->usermask > $this->usermask) ) {
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
      case 'feed':
      case 'anime_list':
      case 'stats':
      case 'achievements':
        return True;
        break;
      default:
        return False;
        break;
    }
  }
  public function currentUser() {
    // returns bool if this object is the currently logged-in user.
    return $this->id === $_SESSION['id'];
  }
  public function loggedIn() {
    //if userID is not proper, or if user's last IP was not the requester's IP, return False.
    if (intval($this->id) <= 0) {
      return False;
    }
    if (($this->id == $_SESSION['id']) && $_SESSION['lastLoginCheckTime'] > microtime(True) - 1) {
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
    $_SESSION['lastLoginCheckTime'] = microtime(True);
    return True;
  }
  public function isCurrentlyActive() {
    // return bool reflecting whether or not user has done something recently.
    return $this->lastActive()->diff(new DateTime("now", new DateTimeZone(Config::SERVER_TIMEZONE)))->i < 5;
  }
  public function isModerator() {
    if (!$this->usermask() or !(intval($this->usermask()) & 2)) {
      return False;
    }
    return True;
  }
  public function isAdmin() {
    if (!$this->usermask() or !(intval($this->usermask()) & 4)) {
      return False;
    }
    return True;
  }
  public function isStaff() {
    return $this->isModerator() || $this->isAdmin();
  }
  public function validate(array $user) {
    if (!parent::validate($user)) {
      return False;
    }

    if ($this->id === 0) {
      if (!isset($user['username']) || !isset($user['email'])) {
        return False;
      }
      if ($this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE LOWER(`username`) = ".$this->dbConn->quoteSmart(strtolower($user['username']))) > 0) {
        return False;
      }
    } else {
      if (isset($user['username'])) {
        if (strlen($user['username']) < 1 || strlen($user['username']) > 40) {
          return False;
        }
        // username must be unique if we're changing.
        if ($user['username'] != $this->username() && $this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE LOWER(`username`) = ".$this->dbConn->quoteSmart(strtolower($user['username']))) > 0) {
          return False;
        }
      }
    }

    if (isset($user['password']) && ($this->id === 0 || $user['password'] != '')) {
     if (strlen($user['password']) < 6) {
        return False;
      }
      if (!isset($user['password_confirmation']) || $user['password_confirmation'] != $user['password']) {
        return False;
      }
    }
    if (isset($user['email'])) {
      if (strlen($user['email']) < 1 || !preg_match("/[0-9A-Za-z\\+\\-\\%\\.]+@[0-9A-Za-z\\.\\-]+\\.[A-Za-z]{2,4}/", $user['email'])) {
        return False;
      }
      // email must be unique if we're changing.
      if (($this->id === 0 || $user['email'] != $this->email()) && $this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE LOWER(`email`) = ".$this->dbConn->quoteSmart(strtolower($user['email']))) > 0) {
        return False;
      }
    }
    if (isset($user['about']) && (strlen($user['about']) < 0 || strlen($user['about']) > 600)) {
      return False;
    }
    if (isset($user['usermask']) && ( !is_numeric($user['usermask']) || intval($user['usermask']) != $user['usermask'] || intval($user['usermask']) < 0) ) {
      return False;
    }
    if (isset($user['last_active']) && !strtotime($user['last_active'])) {
      return False;
    }
    if (isset($user['last_ip']) && !preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $user['last_ip'])) {
      return False;
    }
    return True;
  }
  public function create_or_update(array $user, array $whereConditions=Null) {
    // creates or updates a user based on the parameters passed in $user and this object's attributes.
    // returns False if failure, or the ID of the user if success.
    if (isset($user['usermask']) && intval(@array_sum($user['usermask'])) != 0) {
      $user['usermask'] = intval(@array_sum($user['usermask']));
    } else {
      unset($user['usermask']);
    }
    if (!$this->validate($user)) {
      return False;
    }
    // filter some parameters out first and replace them with their corresponding db fields.
    if (isset($user['password']) && $user['password'] != '') {
      $bcrypt = new Bcrypt();
      $user['password_hash'] = $bcrypt->hash($user['password']);
    }
    unset($user['password']);
    unset($user['password_confirmation']);
    if (isset($user['username']) && $this->id != 0) {
      unset($user['username']);
    }

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
      if (!is_dir(joinPaths(Config::APP_ROOT, "img", "users", intval($this->id)))) {
        mkdir(joinPaths(Config::APP_ROOT, "img", "users", intval($this->id)));
      }
      $imagePathInfo = pathinfo($file_array['tmp_name']);
      $imagePath = joinPaths("img", "users", intval($this->id), $this->id.image_type_to_extension($imageSize[2]));
      if ($this->avatarPath()) {
        $removeOldAvatar = unlink(joinPaths(Config::APP_ROOT, $this->avatarPath()));
      }
      if (!move_uploaded_file($file_array['tmp_name'], $imagePath)) {
        return False;
      }
    } else {
      $imagePath = $this->avatarPath();
    }
    $user['avatar_path'] = $imagePath;
    $user['last_active'] = unixToMySQLDateTime(Null, Config::SERVER_TIMEZONE);
    $result = parent::create_or_update($user, $whereConditions);
    if (!$result) {
      return False;
    }

    // now process anime entries.
    // now process comments.
    // TODO ?_?

    return intval($this->id);
  }
  public function delete($entries=Null) {
    // delete this user from the database.
    // returns a boolean.

    $this->before_update();
    // delete objects that belong to this user.
    foreach ($this->comments() as $comment) {
      if (!$comment->delete()) {
        return False;
      }
    }
    $deleteList = $this->animeList()->delete();
    if (!$deleteList) {
      return False;
    }
    $this->after_update();

    // now delete this user.
    return parent::delete();
  }
  public function updateLastActive($time=Null) {
    $params = array();
    if ($time !== Null) {
      $params['last_active'] = $time->format("Y-m-d H:i:s");
    }
    $updateLastActive = $this->create_or_update($params);
    if (!$updateLastActive) {
      return False;
    }
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
    
    $this->id = $_SESSION['id'] = intval($findUsername['id']);
    $this->name = $_SESSION['name'] = $findUsername['name'];
    $this->username = $_SESSION['username'] = $findUsername['username'];
    $this->usermask = $_SESSION['usermask'] = intval($findUsername['usermask']);

    //update last IP address and last active.
    $updateUser = array('username' => $this->username, 'email' => $this->email, 'last_ip' => $_SERVER['REMOTE_ADDR']);
    $this->create_or_update($updateUser);
    return array("/feed.php", array("status" => "Successfully logged in.", 'class' => 'success'));
  }
  public function register($username, $email, $password, $password_confirmation) {
    // shorthand for create_or_update.
    $user = array('username' => $username, 'about' => '', 'usermask' => array(1), 'email' => $email, 'password' => $password, 'password_confirmation' => $password_confirmation);
    $registerUser = $this->create_or_update($user);
    if (!$registerUser) {
      return array("location" => "/register.php", "status" => "Database errors were encountered during registration. Please try again later.", 'class' => 'error');
    }
    $this->id = $_SESSION['id'] = intval($registerUser);
    $this->username = $_SESSION['username'] = $user['username'];
    $this->email = $_SESSION['email'] = $user['email'];
    $this->usermask = $_SESSION['usermask'] = intval($user['usermask']);

    //update last IP address and last active.
    $updateUser = array('last_ip' => $_SERVER['REMOTE_ADDR']);
    $this->create_or_update($updateUser);

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
    $this->before_update();
    $requestedUser->before_update();
    $createRequest = $this->dbConn->stdQuery("INSERT INTO `users_friends` SET ".implode(", ",$params));
    if ($createRequest) {
      $this->after_update();
      $requestedUser->after_update();
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
    $this->before_update();
    $requestedUser->before_update();
    $updateRequest = $this->dbConn->stdQuery("UPDATE `users_friends` SET `status` = 1 WHERE `user_id_1` = ".intval($requestedUser->id)." && `user_id_2` = ".intval($this->id)." && `status` = 0 LIMIT 1");
    if ($updateRequest) {
      $this->after_update();
      $requestedUser->after_update();
      return True;
    } else {
      return False;
    }
  }
  public function addAchievement(BaseAchievement $achievement) {
    return $this->create_or_update(array('achievement_mask' => $this->achievementMask() + pow(2, $achievement->id - 1)));
  }
  public function removeAchievement(BaseAchievement $achievement) {
    return $this->create_or_update(array('achievement_mask' => $this->achievementMask() - pow(2, $achievement->id - 1)));
  }
  public function switchUser($username, $switch_back=True) {
    /*
      Switches the current user's session out for another user (provided by $username) in the etiStats db.
      If $switch_back is True, packs the current session into $_SESSION['switched_user'] before switching.
      If not, then retrieves the packed session and overrides current session with that info.
      Returns a redirect_to array.
    */
    if ($switch_back) {
      // get user entry in database.
      $findUserID = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." && `id` != ".$this->id." LIMIT 1"));
      if (!$findUserID) {
        return array("location" => "/feed.php", "status" => "The given user to switch to doesn't exist in the database.", 'class' => 'error');
      }
      $newUser = new User($this->app, $findUserID);
      $newUser->switchedUser = $_SESSION['id'];
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['switched_user'] = $newUser->switchedUser;
      return array("location" => "/feed.php", "status" => "You've switched to ".urlencode($newUser->username()).".", 'class' => 'success');
    } else {
      $newUser = new User($this->app, $username);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      unset($_SESSION['switched_user']);
      return array("location" => "/feed.php", "status" => "You've switched back to ".urlencode($newUser->username()).".", 'class' => 'success');
    }
  }
  public function render() {
    switch($this->app->action) {
      case 'request_friend':
        if ($this->id === $this->app->user->id) {
          redirect_to($this->app->user->url("show"), array('status' => "You can't befriend yourself, silly!"));
        }
        $requestFriend = $this->app->user->requestFriend($this, $_POST['friend_request']);
        if ($requestFriend) {
          redirect_to($this->url("show"), array('status' => "Your friend request has been sent to ".urlencode($this->username()).".", 'class' => 'success'));
        } else {
          redirect_to($this->url("show"), array('status' => 'An error occurred while requesting this friend. Please try again.', 'class' => 'error'));
        }
        break;
      case 'confirm_friend':
        $confirmFriend = $this->app->user->confirmFriend($this);
        if ($confirmFriend) {
          redirect_to($this->url("show"), array('status' => "Hooray! You're now friends with ".urlencode($this->username()).".", 'class' => 'success'));
        } else {
          redirect_to($this->url("show"), array('status' => 'An error occurred while confirming this friend. Please try again.', 'class' => 'error'));
        }
        break;
      case 'mal_import':
        // import a MAL list for this user.
        if (!isset($_POST['user']) || !is_array($_POST['user']) || !isset($_POST['user']['mal_username'])) {
          redirect_to($this->url("edit"), array('status' => 'Please enter a MAL username.'));
        }
        $importMAL = $this->importMAL($_POST['user']['mal_username']);
        if ($importMAL) {
          redirect_to($this->url("show"), array('status' => 'Hooray! Your MAL was successfully imported.', 'class' => 'success'));
        } else {
          redirect_to($this->url("edit"), array('status' => 'An error occurred while importing your MAL. Please try again.', 'class' => 'error'));
        }
        break;
      case 'switch_back':
        $switchUser = $this->app->user->switchUser($_SESSION['switched_user'], False);
        redirect_to($switchUser['location'], array('status' => $switchUser['status'], 'class' => $switchUser['class']));
        break;
      case 'switch_user':
        if (isset($_POST['switch_username'])) {
          $switchUser = $this->app->user->switchUser($_POST['switch_username']);
          redirect_to($switchUser['location'], array('status' => $switchUser['status'], 'class' => $switchUser['class']));
        }
        $title = "Switch Users";
        $output = "<h1>Switch Users</h1>\n".$this->app->user->view("switchForm");
        break;
      case 'new':
        $title = "Sign Up";
        $output = $this->view('new');
        break;
      case 'edit':
        if (isset($_POST['user']) && is_array($_POST['user'])) {
          // check to ensure userlevels aren't being elevated beyond this user's abilities.
          if (isset($_POST['user']['usermask']) && intval($_POST['user']['usermask']) > 1 && intval($_POST['user']['usermask']) >= $this->usermask()) {
            redirect_to($this->url("new"), array('status' => "You can't set permissions beyond your own userlevel.", 'class' => 'error'));
          }
          $updateUser = $this->create_or_update($_POST['user']);
          if ($updateUser) {
            redirect_to($this->url("show"), array('status' => (isset($_POST['user']['id']) ? "Your user settings have been saved." : "Congratulations, you're now signed in!"), 'class' => 'success'));
          } else {
            redirect_to(($this->id === 0 ? $this->url("new") : $this->url("edit")), array('status' => "An error occurred while creating or updating this user.", 'class' => 'error'));
          }
        }
        if ($this->id === 0) {
          $this->app->display_error(404);
          exit;
        }
        $title = "Editing ".escape_output($this->username());
        $output = $this->view("edit");
        break;
      case 'show':
        if ($this->id === 0) {
          $this->app->display_error(404);
          exit;
        }
        $title = escape_output($this->username())."'s Profile";
        $output = $this->view("show");
        break;
      case 'feed':
        if ($this->animeList()->allow($this->app->user, 'edit')) {
          $output .= $this->view('addEntryInlineForm');
        }
        if ($this->allow($this->app->user, 'comment')) {
          $blankComment = new Comment($this->app, 0, $this->app->user, $this);
          $output .= "                <div class='addListEntryForm'>
                      ".$blankComment->view('inlineForm', $this->app, array('currentObject' => $this))."
                    </div>\n";

        }
        $output .= $this->profileFeed();
        echo $output;
        exit;
      case 'anime_list':
        echo $this->animeList()->view("show");
        exit;
      case 'stats':
        echo $this->view('stats');
        exit;
      case 'achievements':
        echo $this->view('achievements');
        exit;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404);
          exit;
        }
        $username = $this->username();
        $deleteUser = $this->delete();
        if ($deleteUser) {
          redirect_to('/users/', array('status' => 'Successfully deleted '.$username.'.', 'class' => 'success'));
        } else {
          redirect_to($this->url("show"), array('status' => 'An error occurred while deleting '.$username.'.', 'class' => 'error'));
        }
        break;
      default:
      case 'index':
        $title = "All Users";
        $output = $this->app->user->view('index');
        break;
    }
    $this->app->render($output, array('title' => $title));
  }
  public function friendRequestsList() {
    // returns markup for the list of friend requests directed at this user.
    $serverTimezone = new DateTimeZone(Config::SERVER_TIMEZONE);
    $outputTimezone = new DateTimeZone(Config::OUTPUT_TIMEZONE);
    $output = "";
    foreach ($this->friendRequests() as $request) {
      $entryTime = new DateTime($request['time'], $serverTimezone);
      $entryTime->setTimezone($outputTimezone);
      $output .= "<li class='friendRequestEntry'><strong>".escape_output($request['user']->username())."</strong> requested to be your friend on ".$entryTime->format('G:i n/j/y').".".$this->link('confirm_friend', "Accept", True, Null, Null, $request['user']->id)."</li>\n";
    }
    return $output;
  }
  public function profileFeed(DateTime $maxTime=Null, $numEntries=50) {
    // returns markup for this user's profile feed.
    $feedEntries = $this->animeList()->entries($maxTime, $numEntries);
    $commentEntries = [];
    foreach ($this->comments() as $comment) {
      $commentEntries[] = new CommentEntry($this->app, intval($comment->id));
    }
    $feedEntries->append(new EntryGroup($this->app, $commentEntries));
    return $this->animeList()->feed($feedEntries, $numEntries, "<blockquote><p>No entries yet - add some above!</p></blockquote>\n");
  }
  public function globalFeed(DateTime $maxTime=Null, $numEntries=50) {
    // returns markup for this user's global feed.

    // add each user's personal feeds to the total feed.
    $feedEntries = $this->animeList()->entries($maxTime, $numEntries);
    foreach ($this->friends() as $friend) {
      $feedEntries->append($friend['user']->animeList()->entries($maxTime, $numEntries));
      $comments = [];
      foreach ($friend['user']->ownComments() as $comment) {
        // only append top-level comments.
        if ($comment->depth() === 0) {
          $comments[] = new CommentEntry($this->app, intval($comment->id));
        }
      }
      $feedEntries->append(new EntryGroup($this->app, $comments));
    }
    return $this->animeList()->feed($feedEntries, $numEntries, "<blockquote><p>Nothing's in your feed yet. Why not add some anime to your list?</p></blockquote>\n");
  }
  public function url($action="show", array $params=Null, $username=Null) {
    // returns the url that maps to this object and the given action.
    if ($username === Null) {
      $username = $this->username();
    }
    $urlParams = "";
    if (is_array($params)) {
      $urlParams = http_build_query($params);
    }
    return "/".escape_output($this->modelUrl())."/".($action !== "index" ? urlencode(urlencode($username))."/".escape_output($action)."/" : "").($params !== Null ? "?".$urlParams : "");
  }
}
?>