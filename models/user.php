<?php
class User extends Model {
  use Commentable;

  public static $TABLE = "users";
  public static $PLURAL = "users";
  public static $FIELDS = [
    'id' => [
      'type' => 'int',
      'db' => 'id'
    ],
    'username' => [
      'type' => 'str',
      'db' => 'username'
    ],
    'name' => [
      'type' => 'str',
      'db' => 'name'
    ],
    'email' => [
      'type' => 'str',
      'db' => 'email'
    ],
    'passwordHash' => [
      'type' => 'str',
      'serialize' => False,
      'db' => 'password_hash'
    ],
    'activationCode' => [
      'type' => 'str',
      'serialize' => False,
      'db' => 'activation_code'
    ],
    'about' => [
      'type' => 'str',
      'db' => 'about'
    ],
    'usermask' => [
      'type' => 'int',
      'db' => 'usermask'
    ],
    'achievementMask' => [
      'type' => 'str',
      'db' => 'achievement_mask'
    ],
    'points' => [
      'type' => 'str',
      'db' => 'points'
    ],
    'lastLogin' => [
      'type' => 'date',
      'db' => 'last_login'
    ],
    'lastIP' => [
      'type' => 'str',
      'serialize' => False,
      'db' => 'last_ip'
    ],
    'malUsername' => [
      'type' => 'str',
      'db' => 'mal_username'
    ],
    'lastImport' => [
      'type' => 'date',
      'db' => 'last_import'
    ],
    'lastImportFailed' => [
      'type' => 'bool',
      'db' => 'last_import_failed'
    ],
    'createdAt' => [
      'type' => 'date',
      'db' => 'created_at'
    ],
    'updatedAt' => [
      'type' => 'date',
      'db' => 'updated_at'
    ],
    'lastActive' => [
      'type' => 'date',
      'db' => 'last_active'
    ],
    'avatarPath' => [
      'type' => 'str',
      'db' => 'avatar_path'
    ],
    'thumbPath' => [
      'type' => 'str',
      'db' => 'thumb_path'
    ]
  ];
  public static $JOINS = [
    'ownComments' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'user_id',
      'type' => 'many'
    ],
    'comments' => [
      'obj' => 'CommentEntry',
      'table' => 'comments',
      'own_col' => 'id',
      'join_col' => 'parent_id',
      'condition' => "comments.type = 'User'",
      'type' => 'many'
    ]
  ];
  public static $maxFailedLogins = 10;

  public $switchedUser;

  public $animeList;
  public $friends;
  public $friendRequests;
  public $requestedFriends;

  public function __construct(Application $app, $id=Null) {
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = $this->points = 0;
      $this->passwordHash = $this->email = $this->about = $this->createdAt = $this->lastActive = $this->malUsername = $this->lastImport = $this->lastIP = $this->avatarPath = $this->thumbPath = "";
      $this->switchedUser = $this->friends = $this->friendRequests = $this->requestedFriends = $this->ownComments = $this->comments = [];
      $this->animeList = new AnimeList($this->app, 0);
    } else {
      if (isset($_SESSION['switched_user'])) {
        $this->switchedUser = intval($_SESSION['switched_user']);
      }
    }
  }
  public function generateActivationCode() {
    // 20 character-long string unique to this user at this moment in time.
    do {
      $code = bin2hex(openssl_random_pseudo_bytes(10));
      $numResults = User::Count($this->app, ['activation_code' => $code]);
    } while ($numResults > 0);
    return $code;
  }
  public function usermaskText() {
    $roles = [];
    if ($this->usermask == 0) {
      return "Guest";
    }
    if ($this->usermask & 4) {
      $roles[] = "Administrator";
    }
    if ($this->usermask & 2) {
      $roles[] = "Moderator";
    }
    if ($this->usermask & 1) {
      $roles[] = "User";
    }
    if (!$roles) {
      return "Unknown";
    }
    return implode(", ", $roles);
  }
  public function calculateUsermask(array $permArray) {
    // takes an array like [1, 2, 3] of userlevels that this user has
    // converts it to the proper usermask.
    return array_sum(array_map(function($perm) {
      return is_numeric($perm) ? pow(2, intval($perm)) : 0;
    }, $permArray));
  }
  public function avatarPath() {
    return $this->avatarPath ? $this->avatarPath : "img/blank.png";
  }
  public function thumbPath() {
    return $this->thumbPath ? $this->thumbPath : $this->avatarPath();
  }
  public function avatarImage(array $params=Null) {
    return $this->image($this->avatarPath(), $params);
  }
  public function thumbImage(array $params=Null) {
    return $this->image($this->thumbPath(), $params);
  }
  public function getFriends($status=1) {
    // returns a list of user,time,message arrays corresponding to all friends of this user.
    // keyed by not-this-userID.

    $friendReqs = $this->app->dbConn->table('users_friends')->fields('user_id_1', 'user_id_2', 'u1.username AS username_1', 'u2.username AS username_2', 'time', 'message')
      ->join('users AS u1 ON u1.id=user_id_1')
      ->join('users AS u2 ON u2.id=user_id_2')
      ->where(["user_id_1=".intval($this->id)." || user_id_2=".intval($this->id), 'status' => $status])->query();
    $friends = [];
    while ($req = $friendReqs->fetch()) {
      $reqArray = ['time' => $req['time'], 'message' => $req['message']];
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
  public function isFriend(User $potentialFriend) {
    // returns a bool reflecting whether or not current user is a friend of $potentialFriend.
    return isset($this->friends()[$potentialFriend->id]);
  }
  public function getFriendRequests() {
    // returns a list of user,time,message arrays corresponding to all outstanding friend requests directed at this user.
    // user_id_1 is the user who requested, user_id_2 is the user who confirmed.
    // ordered by time desc.
    $friendReqsQuery = $this->app->dbConn->table('users_friends')->fields('user_id_1', 'time', 'message', 'status')
      ->where(['user_id_2' => $this->id, "status <= 0"])->order('time DESC')->query();
    $friendReqs = [];
    while ($req = $friendReqsQuery->fetch()) {
      $friendReqs[intval($req['user_id_1'])] = [
          'user' => new User($this->app, intval($req['user_id_1'])),
          'time' => $req['time'],
          'message' => $req['message'],
          'status' => $req['status']
        ];
    }
    return $friendReqs;
  }
  public function friendRequests() {
    if ($this->friendRequests === Null) {
      $this->friendRequests = $this->getFriendRequests();
    }
    return $this->friendRequests;
  }
  public function outstandingFriendRequests() {
    return array_filter_by_key($this->friendRequests(), 'status', 0);
  }
  public function getRequestedFriends() {
    // returns a list of user_id,username,time,message arrays corresponding to all outstanding friend requests originating from this user.
    // user_id_1 is the user who requested, user_id_2 is the user who confirmed.
    // ordered by time desc.
    $friendReqsQuery = $this->app->dbConn->table('users_friends')->fields('user_id_2', 'time', 'message', 'status')
      ->where(['user_id_1' => $this->id, "status <= 0"])->order('time DESC')->query();
    $friendReqs = [];
    while ($req = $friendReqsQuery->fetch()) {
      $friendReqs[intval($req['user_id_2'])] = [
          'user' => new User($this->app, intval($req['user_id_2'])),
          'time' => $req['time'],
          'message' => $req['message'],
          'status' => $req['status']
        ];
    }
    return $friendReqs;
  }
  public function requestedFriends() {
    if ($this->requestedFriends === Null) {
      $this->requestedFriends = $this->getRequestedFriends();
    }
    return $this->requestedFriends;
  }
  public function outstandingRequestedFriends() {
    return array_filter_by_key($this->requestedFriends(), 'status', 0);
  }
  public function hasRequestedFriend(User $potentialFriend) {
    // returns a bool reflecting whether or not this user has requested to be friends with $potentialFriend.
    return isset($this->requestedFriends()[$potentialFriend->id]);
  }
  public function hasFriendRequestFrom(User $potentialFriend) {
    return isset($this->friendRequests()[$potentialFriend->id]);
  }
  public function hasFriendRequestWith(User $potentialFriend) {
    // returns a bool reflecting whether or not this user has an outstanding friend request with $potentialFriend, REGARDLESS of who initiated it.
    return $this->hasFriendRequestFrom($potentialFriend) || $this->hasRequestedFriend($potentialFriend);
  }
  public function requestFriend(User $requestedUser, array $request) {
    // generates a friend request from the current user to requestedUser.
    // returns a boolean.

    // check to see if this already exists in friends or requests.
    if ($this->isFriend($requestedUser)) {
      // this friendship already exists.
      return True;
    }
    if ($this->hasFriendRequestWith($requestedUser)) {
      // this request already exists.
      return True;
    }
    // otherwise, go ahead and create a request.

    $params = [];
    $params['message'] = isset($request['message']) ? $request['message'] : "";
    $params['user_id_1'] = $this->id;
    $params['user_id_2'] = $requestedUser->id;
    $params['status'] = 0;

    if ($this->app->dbConn->table('users_friends')->set($params)->set(['time=NOW()'])->insert()) {
      $this->fire('requestFriend', ['id' => $requestedUser->id]);
      $requestedUser->fire('friendRequested', ['id' => $this->id]);
      return True;
    } else {
      return False;
    }
  }
  public function updateFriend(User $requestedUser, $status) {
    // updates a friend request status from requestedUser directed at current user.
    // returns a boolean.

    if ($this->app->dbConn->table('users_friends')->set(['status' => $status])->where(['user_id_1' => $requestedUser->id, 'user_id_2' => $this->id])->limit(1)->update()) {
      $this->fire('updateFriend', ['id' => $requestedUser->id, 'status' => $status]);
      $requestedUser->fire('friendUpdated', ['id' => $this->id, 'status' => $status]);
      return True;
    } else {
      return False;
    }
  }
  public function confirmFriend(User $requestedUser) {
    // confirms a friend request from requestedUser directed at the current user.
    // returns a boolean.
    // check to ensure this is an extant request.
    if (!$this->hasFriendRequestFrom($requestedUser)) {
      return False;
    }

    // check to see if this already exists in friends.
    if ($this->isFriend($requestedUser)) {
      // this friendship already exists.
      return True;
    }
    // otherwise, go ahead and confirm this request.
    if ($this->updateFriend($requestedUser, 1)) {
      $this->fire('confirmFriend', ['id' => $requestedUser->id]);
      $requestedUser->fire('friendConfirmed', ['id' => $this->id]);
      return True;
    }
    return False;
  }
  public function ignoreFriend(User $requestedUser) {
    // ignores a friend request from requestedUser directed at the current user.
    // returns a boolean.
    // check to ensure this is an extant request.
    if (!$this->hasFriendRequestFrom($requestedUser)) {
      return False;
    }

    // check to see if this already exists in friends.
    if ($this->isFriend($requestedUser)) {
      // this friendship already exists.
      return False;
    }
    // otherwise, go ahead and ignore this request.
    return $this->updateFriend($requestedUser, -1);
  }
  public function animeList() {
    if ($this->animeList === Null) {
      $this->animeList = new AnimeList($this->app, $this->id);
    }
    return $this->animeList;
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
    $thisUserInfo = $this->app->dbConn->table(static::$TABLE)->fields('last_ip')->where(['id' => $checkID])->limit(1)->firstRow();
    if (!$thisUserInfo || $thisUserInfo['last_ip'] != $_SERVER['REMOTE_ADDR']) {
      return False;
    }
    $_SESSION['lastLoginCheckTime'] = microtime(True);
    return True;
  }
  public function isCurrentlyActive() {
    // return bool reflecting whether or not user has done something recently.
    return $this->lastActive->diff(new DateTime("now", $this->app->serverTimeZone))->i < 5;
  }
  public function isModerator() {
    if (!$this->usermask or !(intval($this->usermask) & 2)) {
      return False;
    }
    return True;
  }
  public function isAdmin() {
    if (!$this->usermask or !(intval($this->usermask) & 4)) {
      return False;
    }
    return True;
  }
  public function isStaff() {
    return $this->isModerator() || $this->isAdmin();
  }
  public function validate(array $user) {
    $validationErrors = [];
    if (!parent::validate($user)) {
      $validationErrors[] = "Parent validation failure";
    }

    if ($this->id === 0) {
      if (!isset($user['username']) || !isset($user['email'])) {
        $validationErrors[] = "ID set without username or email";
      }
      if (User::Count($this->app, [['LOWER(username)=?', strtolower($user['username'])]]) > 0) {
        $validationErrors[] = "This username already exists";
      }
    } else {
      if (isset($user['username'])) {
        if (mb_strlen($user['username']) < 1 || mb_strlen($user['username']) > 40) {
          $validationErrors[] = "Username must be between 1 and 40 characters";
        }
        // username must be unique if we're changing.
        if ($user['username'] != $this->username && User::Count($this->app, [['LOWER(username)=?', strtolower($user['username'])]]) > 0) {
          $validationErrors[] = "New username is already taken";
        }
      }
    }

    if (isset($user['password']) && ($this->id === 0 || $user['password'] != '')) {
     if (mb_strlen($user['password']) < 6) {
        $validationErrors[] = "Password must be longer than 6 characters";
      }
      if (!isset($user['password_confirmation']) || $user['password_confirmation'] != $user['password']) {
        $validationErrors[] = "Password confirmation does not match";
      }
    }
    if (isset($user['email'])) {
      if (mb_strlen($user['email']) < 1 || !preg_match("/[0-9A-Za-z\\+\\-\\%\\.]+@[0-9A-Za-z\\.\\-]+\\.[A-Za-z]{2,4}/", $user['email'])) {
        $validationErrors[] = "Malformed email address";
      }
      // email must be unique if we're changing.
      if (($this->id === 0 || $user['email'] != $this->email) && User::Count($this->app, [['LOWER(email)=?', strtolower($user['email'])]]) > 0) {
        $validationErrors[] = "This email has already been taken. Try resetting your password!";
      }
    }
    if (isset($user['activation_code']) && (mb_strlen($user['activation_code']) < 0 || mb_strlen($user['activation_code']) > 20)) {
      $validationErrors[] = "Your activation code must be at most 20 characters";
    }
    if (isset($user['about']) && (mb_strlen($user['about']) < 0 || mb_strlen($user['about']) > 600)) {
      $validationErrors[] = "Your bio must be at most 600 characters";
    }
    if (isset($user['usermask']) && ( !is_integral($user['usermask']) || intval($user['usermask']) < 0) ) {
      $validationErrors[] = "Your user permissions are invalid";
    }
    if (isset($user['last_active']) && !strtotime($user['last_active'])) {
      $validationErrors[] = "Malformed last-active time";
    }
    if (isset($user['last_ip']) && !preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $user['last_ip'])) {
      $validationErrors[] = "Malformed IP address";
    }
    if (isset($user['points']) && !is_numeric($user['points'])) {
      $validationErrors[] = "Your points must be numeric";
    }
    if (isset($user['mal_username']) && (strlen(trim($user['mal_username'])) < 4 || strlen(trim($user['mal_username'])) > 20)) {
      $validationErrors[] = "Your MAL username must be between 4 and 20 non-empty characters";
    }
    if (isset($user['last_import'])) {
      $validationErrors[] = "You cannot set the last_import field.";
    }
    if (isset($user['last_import_failed']) && (!is_integral($user['last_import_failed']) || intval($user['last_import_failed']) < 0 || intval($user['last_import_failed']) > 1)) {
      $validationErrors[] = "The last_import_failed field must be a boolean.";
    }
    if ($validationErrors) {
      throw new ValidationException($this->app, $user, $validationErrors);
    } else {
      return True;
    }
  }
  public function create_or_update(array $user, array $whereConditions=Null) {
    // creates or updates a user based on the parameters passed in $user and this object's attributes.
    // returns False if failure, or the ID of the user if success.
    if (isset($user['usermask']) && intval(@array_sum($user['usermask'])) != 0) {
      $user['usermask'] = intval(@array_sum($user['usermask']));
    } else {
      unset($user['usermask']);
    }

    $this->validate($user);

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
    $file_array = isset($_FILES['users']) ? $_FILES['users'] : [];
    $imagePath = "";
    if (isset($file_array['tmp_name']['avatar_image']) && $file_array['tmp_name']['avatar_image'] && is_uploaded_file($file_array['tmp_name']['avatar_image'])) {
      if ($file_array['error']['avatar_image'] != UPLOAD_ERR_OK) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while uploading your avatar');
      }

      $acceptableFormats = ["BMP", "BMP2", "BMP3", "GIF", "GIF87", "ICO", "JPEG", "JPG", "PNG", "PNG24", "PNG32", "PNG8", "TGA", "TIFF", "TIFF64", "WBMP"];
      // load image and resize it.
      try {
        $avatarImage = new Imagick($file_array['tmp_name']['avatar_image']);
        $avatarFormat = $avatarImage->getImageFormat();
        $avatarIterations = $avatarImage->getImageIterations();
        if (!in_array($avatarFormat, $acceptableFormats)) {
          throw new ValidationException($this->app, $file_array, 'Avatar is not one of formats: '.implode(', ', $acceptableFormats));
        }
        $imageProperties = $avatarImage->getImageGeometry();
        if ($avatarIterations) {
          // animated image. convert to gif.
          $imageExtension = "gif";
        }  else {
          // convert to png.
          $imageExtension = "png";
        }
        $avatarImage->setImageFormat($imageExtension);

        // only create thumbnail if necessary.
        $thumbnailImage = Null;
        if ($imageProperties['width'] > Config::$THUMB_AVATAR_DIMENSIONS[0] || $imageProperties['height'] > Config::$THUMB_AVATAR_DIMENSIONS[1]) {
          $thumbnailImage = clone $avatarImage;
          $thumbnailImage->coalesceImages();
          foreach ($thumbnailImage as $frame) {
            /* Thumbnail each frame */
            $frame->thumbnailImage(Config::$THUMB_AVATAR_DIMENSIONS[0], Config::$THUMB_AVATAR_DIMENSIONS[1], True);

            /* Set virtual canvas size to 100x100 */
            $frame->setImagePage(Config::$THUMB_AVATAR_DIMENSIONS[0], Config::$THUMB_AVATAR_DIMENSIONS[1], 0, 0);
          }
          $thumbnailImage = $thumbnailImage->deconstructImages();
          $thumbnailImage->setImageIterations(0);
        }
        // only resize avatar if necessary.
        if ($imageProperties['width'] > Config::$MAX_AVATAR_DIMENSIONS[0] || $imageProperties['height'] > Config::$MAX_AVATAR_DIMENSIONS[1]) {
          $avatarImage->coalesceImages();
          foreach ($avatarImage as $frame) {
            /* Thumbnail each frame */
            $frame->thumbnailImage(Config::$MAX_AVATAR_DIMENSIONS[0], Config::$MAX_AVATAR_DIMENSIONS[1], True);

            /* Set virtual canvas size to 100x100 */
            $frame->setImagePage(Config::$MAX_AVATAR_DIMENSIONS[0], Config::$MAX_AVATAR_DIMENSIONS[1], 0, 0);
          }
          $avatarImage = $avatarImage->deconstructImages();
          $avatarImage->setImageIterations(0);
        }
      } catch (ImagickException $e) {
        $this->app->statsd->increment("ImagickException");
        $this->app->log_exception($e);
        throw new ValidationException($this->app, $file_array, 'An error occurred while resizing your avatar');
      }

      // move file to destination and save path in db.
      if (!is_dir(joinPaths(Config::APP_ROOT, "public", "img", "users", intval($this->id)))) {
        mkdir(joinPaths(Config::APP_ROOT, "public", "img", "users", intval($this->id)));
      }
      $imagePathInfo = pathinfo($file_array['tmp_name']['avatar_image']);
      $imagePath = joinPaths("img", "users", intval($this->id), $this->id.'.'.$imageExtension);
      $thumbnailPath = joinPaths("img", "users", intval($this->id), $this->id.'-thumb.'.$imageExtension);
      if ($this->avatarPath) {
        try {
          $removeOldAvatar = unlink(joinPaths(Config::APP_ROOT, $this->avatarPath));
          $oldThumbPathParts = explode(".", $this->avatarPath);
          $oldThumbPath = implode(".", array_slice($oldThumbPathParts, 0, -1))."-thumb.".array_slice($oldThumbPathParts, -1, 1);
          $removeOldThumb = unlink($oldThumbPath);
        } catch (ErrorException $e) {
          // we're trying to unlink a file we don't have permissions to. this happens when user doesn't have a previous avatar.
        }
      }
      if ($imageExtension == "gif") {
        $writeImages = $avatarImage->writeImages($imagePath, True) && ($thumbnailImage === Null || $thumbnailImage->writeImages($thumbnailPath, True));
      } else {
        $writeImages = $avatarImage->writeImage($imagePath) && ($thumbnailImage === Null || $thumbnailImage->writeImage($thumbnailPath));
      }
      if (!$writeImages) {
        throw new ValidationException($this->app, $file_array, 'An error occurred while saving your avatar');
      }
    } else {
      $imagePath = $this->avatarPath;
    }
    $user['avatar_path'] = $imagePath;
    $user['thumb_path'] = $thumbnailImage ? $thumbnailPath : "";

    $this->id = parent::create_or_update($user, $whereConditions);

    // now process anime entries.
    // now process comments.
    // TODO ?_?

    return intval($this->id);
  }
  public function delete($entries=Null) {
    // delete this user from the database.
    // returns a boolean.

    $this->beforeDelete([]);
    // delete objects that belong to this user.
    foreach ($this->comments as $comment) {
      $comment->delete();
    }
    $this->animeList()->delete();
    // now delete this user.
    return parent::delete();
    $this->afterDelete([]);
  }
  public function updateLastActive($time=Null) {
    $now = new DateTime("now", $this->app->serverTimeZone);
    $params = ['last_active' => $now->format("Y-m-d H:i:s")];
    if ($time !== Null) {
      $params['last_active'] = $time->format("Y-m-d H:i:s");
    }
    $updateLastActive = $this->create_or_update($params);
    if (!$updateLastActive) {
      throw new DatabaseException("Could not update UserID ".$this->id."'s last-active: ".print_r($params['last_active'], True));
    }
    return True;
  }
  public function addPoints($points) {
    if (!is_integral($points)) {
      throw new InvalidParameterException($this->app, [$points], 'integral');
    }
    return $this->create_or_update(['points' => $this->points + intval($points)]);
  }
  public function logFailedLogin($username) {
    $dateTime = new DateTime('now', $this->app->serverTimeZone);
    $insert_log = $this->app->dbConn->table('failed_logins')->fields('ip', 'time', 'username')->values([$_SERVER['REMOTE_ADDR'], $dateTime->format("Y-m-d H:i:s"), $username])->insert();
  }
  private function setCurrentSession() {
    // sets the current session to this user.
    if ($this->id) {
      $_SESSION['id'] = $this->id;
      $_SESSION['name'] = $this->name;
      $_SESSION['username'] = $this->username;
      $_SESSION['email'] = $this->email;
      $_SESSION['usermask'] = $this->usermask;
      $_SESSION['avatarPath'] = $this->avatarPath;
      return True;
    }
    return False;
  }
  public function logIn($username, $password) {
    // rate-limit requests.
    $failedLoginCount = $this->app->dbConn->table('failed_logins')
      ->fields('COUNT(*)')
      ->where([
        'ip' => $_SERVER['REMOTE_ADDR'],
        "time > NOW() - INTERVAL 1 HOUR"
      ])
      ->count();
    if ($failedLoginCount > self::$maxFailedLogins) {
      return False;
    }
  
    // check for existence of username and matching password.
    $bcrypt = new Bcrypt();
    try {
      $findUser = User::Get($this->app, ['username' => $username]);
    } catch (DatabaseException $e) {
      $this->logFailedLogin($username);
      return False;
    }
    if ($findUser->activationCode) {
      return False;
    }
    if (!$findUser || !$bcrypt->verify($password, $findUser->passwordHash)) {
      $this->logFailedLogin($username);
      return False;
    }

    // sign user in.
    $newUser = User::Get($this->app, ['username' => $findUser->username]);
    $newUser->setCurrentSession();

    // check for failed logins.
    $failedLoginQuery = $this->app->dbConn->table('failed_logins')
      ->fields('ip', 'time')
      ->where([
        'username' => $username, 
        ['time > ?', $newUser->lastLogin->setTimezone($this->app->serverTimeZone)->format('Y-m-d H:i:s')]
      ])
      ->order('time DESC')
      ->assoc();
    if ($failedLoginQuery) {
      foreach ($failedLoginQuery as $failedLogin) {
        $this->app->delayedMessage('There was a failed login attempt from '.$failedLogin['ip'].' at '.$failedLogin['time'].'.', 'error');
      }
    }

    //update last login info.
    $currTime = new DateTime('now', $this->app->serverTimeZone);
    $updateUser = [
      'last_login' => $currTime->format('Y-m-d H:i:s'),
      'last_ip' => $_SERVER['REMOTE_ADDR']
    ];
    $newUser->create_or_update($updateUser);

    $newUser->fire('logIn');
    return True;
  }
  public function logOut() {
    $_SESSION = [];
    $this->fire('logOut');
    return session_destroy();
  }
  public function register($username, $email, $password, $password_confirmation) {
    // shorthand for create_or_update.
    $user = ['username' => $username, 'about' => '', 'usermask' => [1], 'email' => $email, 'password' => $password, 'password_confirmation' => $password_confirmation, 'activation_code' => $this->generateActivationCode()];
    try {
      $registerUser = $this->create_or_update($user);
    } catch (ValidationException $e) {
      // append these validation errors to the app's delayed messages.
      foreach ($e->messages() as $message) {
        $this->app->delayedMessage($message);
      }
      return ["location" => "/register.php", "status" => "Some errors were encountered in registering you:"];
    }
    if (!$registerUser) {
      return ["location" => "/register.php", "status" => "Database errors were encountered during registration. Please try again later.", 'class' => 'error'];
    }
    $newUser = new User($this->app, $this->id);

    // Create activation message
    $message = Swift_Message::newInstance()
      ->setSubject('Animurecs account activation')
      ->setFrom([Config::SMTP_USERNAME => 'Animurecs'])
      ->setTo([$newUser->email => $newUser->name])
      ->setBody($newUser->view('activationEmail'), 'text/html');
    $result = $this->app->mailer->send($message);

    if (!$result) {
      // activation error. rollback user.
      $newUser->delete();
      return ["location" => "/register.php", "status" => "We ran into an error trying to email you. Please check your email and try again.", 'class' => 'error'];
    }
    // otherwise, let this user know to activate their account.
    return ["location" => "/", "status" => "Registration successful! Check your email to activate your account."];
  }
  public function addAchievement(Achievement $achievement) {
    $this->fire('addAchievement', ['id' => $achievement->id, 'points' => $achievement->points]);
    $updateArray = ['points' => $this->points + $achievement->points, 'achievement_mask' => $this->achievementMask + pow(2, $achievement->id - 1)];
    return $this->create_or_update($updateArray);
  }
  public function removeAchievement(Achievement $achievement) {
    $this->fire('removeAchievement', ['id' => $achievement->id, 'points' => $achievement->points]);
    return $this->create_or_update(['points' => $this->points + $achievement->points, 'achievement_mask' => $this->achievementMask - pow(2, $achievement->id - 1)]);
  }
  public function switchUser($userID, $switch_back=True) {
    /*
      Switches the current user's session out for another user (provided by $userID) in the animurecs db.
      If $switch_back is True, puts the original userID into $_SESSION['switched_user'] before switching.
      If not, then retrieves the packed session and overrides current session with that info.
      Returns a bool reflecting operation status.
    */
    if ($switch_back) {
      // get user entry in database.
      try {
        $findUser = User::Get($this->app, ['id' => $userID]);
      } catch (NoDatabaseRowsRetrievedException $e) {
        return False;
      }
      $findUser->switchedUser = $this->app->user->id;
      $findUser->setCurrentSession();
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      $_SESSION['switched_user'] = $findUser->switchedUser;
      return True;
    } else {
      try {
        $newUser = User::Get($this->app, ['id' => $_SESSION['switched_user']]);
      } catch (NoDatabaseRowsRetrievedException $e) {
        return False;
      }
      $newUser->setCurrentSession();
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      unset($_SESSION['switched_user']);
      return True;
    }
  }
  public function profileFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup consisting of entries for this user's profile feed.
    if ($maxTime == Null) {
      $maxTime = new DateTime("now", $this->app->serverTimeZone);
    }
    $feedEntries = $this->animeList()->entries($minTime, $maxTime, $numEntries);

    $filteredComments = new EntryGroup($this->app, array_sort_by_property(array_filter($this->comments->entries(), function($a) use ($maxTime, $minTime) {
      return $a->time < $maxTime && $a->time > $minTime;
    }), 'time', 'desc'));

    $feedEntries->append($filteredComments->limit($numEntries));

    return $feedEntries;
    //return $this->animeList()->feed($feedEntries, $numEntries, "<blockquote><p>No entries yet - add some above!</p></blockquote>\n");
  }
  public function conversationFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup corresponding to the last-active conversations that this user was a part of.
    $feedEntries = $this->comments;
    $feedEntries->append($this->ownComments);
    return $feedEntries;
  }
  public function globalFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup of entries corresponding to this user's global feed.
    if ($maxTime == Null) {
      $maxTime = new DateTime("now", $this->app->serverTimeZone);
    }
    $this->app->addTiming("Start globalFeed: | ".$maxTime->format('U')." | ".$numEntries);

    // add each friend's personal feed to the global feed.
    $feedEntries = $this->animeList()->entries($minTime, $maxTime, $numEntries);

    $this->app->addTiming("Finish getting user anime list: ".$feedEntries->length());

    foreach ($this->friends() as $friend) {
      $animeList = $friend['user']->animeList();
      $this->app->addTiming("Finish getting friend anime list: ".$animeList->length());
      $feedEntries->append($animeList->entries($minTime, $maxTime, $numEntries));
      $this->app->addTiming("Finish adding friend anime list: ".$feedEntries->length());
      $comments = [];

      // now append all comments made by this friend between the given datetimes.
      $friendComments = $friend['user']->ownComments === Null ? [] : $friend['user']->ownComments;
      $friendComments = array_filter($friendComments, function($a) use ($maxTime,$minTime) {
        return $a->time() < $maxTime && $a->time() > $minTime;
      });

      foreach ($friendComments as $commentEntry) {
        // only append top-level comments.
        if ($commentEntry->depth() === 0) {
          $comments[] = new CommentEntry($this->app, intval($commentEntry->id));
        }
      }
      $this->app->addTiming("Finish filtering friend comments");
      $feedEntries->append(new EntryGroup($this->app, $comments));
      $this->app->addTiming("Finish adding friend comments: ".$feedEntries->length());
    }
    return $feedEntries;
  }
  public function url($action="show", $format=Null, array $params=Null, $username=Null) {
    // returns the url that maps to this object and the given action.
    if ($username === Null) {
      $username = $this->username;
    }
    return parent::url($action, $format, $params, $username);
  }
}
?>