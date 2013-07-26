<?php
class User extends BaseObject {
  use Commentable;

  public static $MODEL_TABLE = "users";
  public static $MODEL_PLURAL = "users";
  public static $maxFailedLogins = 10;

  protected $username;
  protected $name;
  protected $email;
  protected $activationCode;
  protected $about;
  protected $usermask;
  protected $achievementMask;
  protected $points;
  protected $lastActive;
  protected $lastLogin;
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
      $id = intval($app->dbConn->table(static::$MODEL_TABLE)->fields('id')->where(['username' => $username])->limit(1)->firstValue());
    }
    parent::__construct($app, $id);
    if ($id === 0) {
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = $this->points = 0;
      $this->email = $this->about = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = $this->thumbPath = "";
      $this->switchedUser = $this->friends = $this->friendRequests = $this->requestedFriends = $this->ownComments = $this->comments = [];
      $this->animeList = new AnimeList($this->app, 0);
    } else {
      if (isset($_SESSION['switched_user'])) {
        $this->switchedUser = intval($_SESSION['switched_user']);
      }
      $this->username = $this->name = $this->email = $this->about = $this->usermask = $this->achievementMask = $this->points = $this->createdAt = $this->lastActive = $this->lastIP = $this->avatarPath = $this->thumbPath = $this->friends = $this->friendRequests = $this->requestedFriends = $this->animeList = $this->ownComments = $this->comments = Null;
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
  public function generateActivationCode() {
    // 20 character-long string unique to this user at this moment in time.
    do {
      $code = bin2hex(openssl_random_pseudo_bytes(10));
      $numResults = User::count($this->app, ['activation_code' => $code]);
    } while ($numResults > 0);
    return $code;
  }
  public function activationCode() {
    return $this->returnInfo('activationCode');
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
  public function calculateUsermask(array $permArray) {
    // takes an array like [1, 2, 3] of userlevels that this user has
    // converts it to the proper usermask.
    return array_sum(array_map(function($perm) {
      return is_numeric($perm) ? pow(2, intval($perm)) : 0;
    }, $permArray));
  }
  public function achievementMask() {
    return $this->returnInfo('achievementMask');
  }
  public function points() {
    return $this->returnInfo('points');
  }
  public function lastActive() {
    return new DateTime($this->returnInfo('lastActive'), $this->app->serverTimeZone);
  }
  public function lastLogin() {
    return new DateTime($this->returnInfo('lastLogin'), $this->app->serverTimeZone);
  }
  public function lastIP() {
    return $this->returnInfo('lastIP');
  }
  public function avatarPath() {
    return $this->returnInfo('avatarPath') ? $this->returnInfo('avatarPath') : "img/blank.png";
  }
  public function thumbPath() {
    return $this->returnInfo('thumbPath') ? $this->returnInfo('thumbPath') : $this->avatarPath();
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
  public function getOwnComments() {
    // returns a list of comment objects sent by this user.
    $ownComments = $this->app->dbConn->table(Comment::$MODEL_TABLE)->fields('id')->where(['user_id' => $this->id])->order('created_at DESC')->query();
    $comments = [];
    while ($comment = $ownComments->fetch()) {
      $comments[] = new CommentEntry($this->app, intval($comment['id']));
    }
    return new EntryGroup($this->app, $comments);
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
      /* post-register conversions - must be logged in */
      case 'register_conversion':
        if ($this->loggedIn()) {
          return True;
        }
        return False;
        break;
      /* cases where we want only user+staff capable, keeping the first user public */
      case 'discover':
      case 'friendRecs':
      case 'recommendations':
      case 'groupwatches':
        if ($this->id === 1 || ($authingUser->id == $this->id || ( ($authingUser->isStaff()) && $authingUser->usermask > $this->usermask) )) {
          return True;
        }
        return False;
        break;
      /* cases where we want only this user + staff capable */
      case 'anime':
      case 'globalFeedEntries':
      case 'globalFeed':
      case 'mal_import':
      case 'edit':
        if ($authingUser->id == $this->id || ( ($authingUser->isStaff()) && $authingUser->usermask > $this->usermask) ) {
          return True;
        }
        return False;
        break;
      case 'request_friend':
      case 'confirm_friend':
      case 'ignore_friend':
        if ($authingUser->id !== 0 && $authingUser->loggedIn() && $this->id !== 0) {
          return True;
        }
        return False;
        break;
      /* cases where we only want non-logged-in users */
      case 'new':
      case 'activate':
        if (!$authingUser->loggedIn()) {
          return True;
        }
        return False;
        break;
      /* cases where we only want admins, and only target non-admins */
      case 'delete':
        if ($authingUser->isAdmin() && !$this->isAdmin()) {
          return True;
        }
        return False;
        break;
      /* cases where we only want admins */
      case 'switch_user':
        if ($authingUser->isAdmin()) {
          return True;
        }
        return False;
        break;
      /* cases where we want only logged-in users who are not this user */
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
      case 'friends':
      case 'achievements':
      case 'achievements2':
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
    $thisUserInfo = $this->app->dbConn->table(static::$MODEL_TABLE)->fields('last_ip')->where(['id' => $checkID])->limit(1)->firstRow();
    if (!$thisUserInfo || $thisUserInfo['last_ip'] != $_SERVER['REMOTE_ADDR']) {
      return False;
    }
    $_SESSION['lastLoginCheckTime'] = microtime(True);
    return True;
  }
  public function isCurrentlyActive() {
    // return bool reflecting whether or not user has done something recently.
    return $this->lastActive()->diff(new DateTime("now", $this->app->serverTimeZone))->i < 5;
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
    $validationErrors = [];
    if (!parent::validate($user)) {
      $validationErrors[] = "Parent validation failure";
    }

    if ($this->id === 0) {
      if (!isset($user['username']) || !isset($user['email'])) {
        $validationErrors[] = "ID set without username or email";
      }
      if (User::count($this->app, [['LOWER(username)=?', strtolower($user['username'])]]) > 0) {
        $validationErrors[] = "This username already exists";
      }
    } else {
      if (isset($user['username'])) {
        if (mb_strlen($user['username']) < 1 || mb_strlen($user['username']) > 40) {
          $validationErrors[] = "Username must be between 1 and 40 characters";
        }
        // username must be unique if we're changing.
        if ($user['username'] != $this->username() && User::count($this->app, [['LOWER(username)=?', strtolower($user['username'])]]) > 0) {
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
      if (($this->id === 0 || $user['email'] != $this->email()) && User::count($this->app, [['LOWER(email)=?', strtolower($user['email'])]]) > 0) {
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

      $acceptableFormats = ["GIF", "GIF87", "BMP", "BMP2", "BMP3", "GIF", "GIF87", "ICO", "JPEG", "JPG", "PNG", "PNG24", "PNG32", "PNG8", "TGA", "TIFF", "TIFF64", "WBMP"];
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
        $this->app->logger->err($e->__toString());
        throw new ValidationException($this->app, $file_array, 'An error occurred while resizing your avatar');
      }

      // move file to destination and save path in db.
      if (!is_dir(joinPaths(Config::APP_ROOT, "img", "users", intval($this->id)))) {
        mkdir(joinPaths(Config::APP_ROOT, "img", "users", intval($this->id)));
      }
      $imagePathInfo = pathinfo($file_array['tmp_name']['avatar_image']);
      $imagePath = joinPaths("img", "users", intval($this->id), $this->id.'.'.$imageExtension);
      $thumbnailPath = joinPaths("img", "users", intval($this->id), $this->id.'-thumb.'.$imageExtension);
      if ($this->avatarPath()) {
        try {
          $removeOldAvatar = unlink(joinPaths(Config::APP_ROOT, $this->avatarPath()));
          $oldThumbPathParts = explode(".", $this->avatarPath());
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
      $imagePath = $this->avatarPath();
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
    foreach ($this->comments() as $comment) {
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
      throw new DbException("Could not update UserID ".$this->id."'s last-active: ".print_r($params['last_active'], True));
    }
    return True;
  }
  public function addPoints($points) {
    if (!is_integral($points)) {
      throw new InvalidParameterException($this->app, [$points], 'integral');
    }
    return $this->create_or_update(['points' => $this->points() + intval($points)]);
  }
  public function logFailedLogin($username) {
    $dateTime = new DateTime('now', $this->app->serverTimeZone);
    $insert_log = $this->app->dbConn->table('failed_logins')->fields('ip', 'time', 'username')->values([$_SERVER['REMOTE_ADDR'], $dateTime->format("Y-m-d H:i:s"), $username])->insert();
  }
  private function setCurrentSession() {
    // sets the current session to this user.
    if ($this->id) {
      $_SESSION['id'] = $this->id;
      $_SESSION['name'] = $this->name();
      $_SESSION['username'] = $this->username();
      $_SESSION['email'] = $this->email();
      $_SESSION['usermask'] = $this->usermask();
      $_SESSION['avatarPath'] = $this->avatarPath();
      return True;
    }
    return False;
  }
  public function logIn($username, $password) {
    // rate-limit requests.
    $failedLoginCount = $this->app->dbConn->table('failed_logins')->fields('COUNT(*)')->where(['ip' => $_SERVER['REMOTE_ADDR'], "time > NOW() - INTERVAL 1 HOUR"])->count();
    if ($failedLoginCount > self::$maxFailedLogins) {
      $this->app->delayedMessage("You have had too many unsuccessful login attempts. Please wait awhile and try again.", "error");
      return False;
    }
  
    // check for existence of username and matching password.
    $bcrypt = new Bcrypt();
    try {
      $findUser = User::find($this->app, ['username' => $username, 'activation_code IS NULL']);
    } catch (DbException $e) {
      $this->logFailedLogin($username);
      $this->app->delayedMessage("Could not log in with the supplied credentials.", "error");
      return False;
    }
    $findUser = $findUser[0];
    if (!$findUser || !$bcrypt->verify($password, $findUser->passwordHash)) {
      $this->logFailedLogin($username);
      $this->app->delayedMessage("Could not log in with the supplied credentials.", "error");
      return False;
    }

    // sign user in.
    $newUser = new User($this->app, Null, $findUser->username);
    $newUser->setCurrentSession();

    // check for failed logins.
    $failedLoginQuery = $this->app->dbConn->table('failed_logins')->fields('ip', 'time')->where(['username' => $username, ['time > ?', $newUser->lastLogin()->setTimezone($this->app->serverTimeZone)->format('Y-m-d H:i:s')]])->order('time DESC')->assoc();
    if ($failedLoginQuery) {
      foreach ($failedLoginQuery as $failedLogin) {
        $this->app->delayedMessage('There was a failed login attempt from '.$failedLogin['ip'].' at '.$failedLogin['time'].'.', 'error');
      }
    }

    //update last login info.
    $currTime = new DateTime('now', $this->app->serverTimeZone);
    $updateUser = ['last_login' => $currTime->format('Y-m-d H:i:s'), 'last_ip' => $_SERVER['REMOTE_ADDR']];
    $newUser->create_or_update($updateUser);
    $newUser->fire('logIn');
    $this->app->delayedMessage("Successfully logged in.", "success");

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
      ->setTo([$newUser->email() => $newUser->name()])
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
  public function importMAL($malUsername) {
    // imports a user's MAL lists.
    // takes a MAL username and returns an array of animeID=>boolean pairs indicating import status for each.
    $malList = parseMALList($malUsername);
    if (!$malList) {
      throw new AppException($this->app, "Could not parse MAL list");
    }
    $listIDs = [];

    foreach($malList as $entry) {
      // ensure that the user doesn't already have this entry in their list.
      $entry['user_id'] = $this->id;
      try {
        $foundEntry = AnimeEntry::find($this->app, $entry);
      } catch (DbException $e) {
        // entry doesn't already exist.
        try {
          $newEntry = new AnimeEntry($this->app, Null, ['user' => $this]);
          $listIDs[$entry['anime_id']] = $newEntry->create_or_update($entry);
        } catch (DbException $e) {
          $this->app->logger->err($e->__toString());
          $listIDs[$entry['anime_id']] = False;
        }

      }
    }

    // fire an event for this.
    $this->fire('importMAL', $listIDs);

    return $listIDs;
  }
  public function addAchievement(BaseAchievement $achievement) {
    $this->fire('addAchievement', ['id' => $achievement->id, 'points' => $achievement->points]);
    $updateArray = ['points' => $this->points() + $achievement->points, 'achievement_mask' => $this->achievementMask() + pow(2, $achievement->id - 1)];
    return $this->create_or_update($updateArray);
  }
  public function removeAchievement(BaseAchievement $achievement) {
    $this->fire('removeAchievement', ['id' => $achievement->id, 'points' => $achievement->points]);
    return $this->create_or_update(['points' => $this->points() + $achievement->points, 'achievement_mask' => $this->achievementMask() - pow(2, $achievement->id - 1)]);
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
      try {
        $findUser = User::find($this->app, ['username' => $username, ['id != ?', $this->id]]);
      } catch (DbException $e) {
        return ["location" => $this->url('globalFeed'), "status" => "The given user to switch to doesn't exist in the database.", 'class' => 'error'];
      }
      $newUser = new User($this->app, $findUser->id);
      $newUser->switchedUser = $_SESSION['id'];
      $newUser->setCurrentSession();
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      $_SESSION['switched_user'] = $newUser->switchedUser;
      return ["location" => $newUser->url('globalFeed'), "status" => "You've switched to ".rawurlencode($newUser->username()).".", 'class' => 'success'];
    } else {
      $newUser = new User($this->app, $username);
      $newUser->setCurrentSession();
      $_SESSION['lastLoginCheckTime'] = microtime(True);
      unset($_SESSION['switched_user']);
      return ["location" => $newUser->url('globalFeed'), "status" => "You've switched back to ".rawurlencode($newUser->username()).".", 'class' => 'success'];
    }
  }
  public function render() {
    switch($this->app->action) {
      /* Topbar views */
      case 'request_friend':
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        if ($this->id === $this->app->user->id) {
          $this->app->delayedMessage("You can't befriend yourself, silly!");
          $this->app->redirect();
        }
        if (!isset($_POST['friend_request'])) {
          $_POST['friend_request'] = [];
        }
        $requestFriend = $this->app->user->requestFriend($this, $_POST['friend_request']);
        if ($requestFriend) {
          $this->app->delayedMessage("Your friend request has been sent to ".rawurlencode($this->username()).".", "success");
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while requesting this friend. Please try again.', 'error');
          $this->app->redirect();
        }
        break;
      case 'confirm_friend':
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        $confirmFriend = $this->app->user->confirmFriend($this);
        if ($confirmFriend) {
          $this->app->delayedMessage("Hooray! You're now friends with ".rawurlencode($this->username()).".", 'success');
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while confirming this friend. Please try again.', 'error');
          $this->app->redirect();
        }
        break;
      case 'ignore_friend':
        if (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        $ignoreFriend = $this->app->user->ignoreFriend($this);
        if ($ignoreFriend) {
          $this->app->delayedMessage("You ignored a friend request from ".rawurlencode($this->username()).".", 'success');
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while ignoring this friend. Please try again.', 'error');
          $this->app->redirect();
        }
        break;
      case 'switch_back':
        $switchUser = $this->app->user->switchUser($_SESSION['switched_user'], False);
        $this->app->delayedMessage($switchUser['status'], $switchUser['class']);
        $this->app->redirect($switchUser['location']);
        break;
      case 'switch_user':
        if (isset($_POST['switch_username'])) {
          $switchUser = $this->app->user->switchUser($_POST['switch_username']);
          $this->app->delayedMessage($switchUser['status'], $switchUser['class']);
          $this->app->redirect($switchUser['location']);
        }
        $title = "Switch Users";
        $output = "<h1>Switch Users</h1>\n".$this->app->user->view("switchForm");
        break;
      case 'register_conversion':
        $title = "Redirecting...";
        $output = $this->app->user->view("postRegisterConversion");
        break;
      case 'new':
        $title = "Sign Up";
        $output = $this->view('new');
        break;

      /* user setting views */
      case 'edit':
        if (isset($_POST['users']) && is_array($_POST['users'])) {
          // check to ensure userlevels aren't being elevated beyond this user's abilities.
          if (isset($_POST['users']['usermask']) && array_sum($_POST['users']['usermask']) > 1 && (($this->app->user->id != intval($_POST['users']['id']) && array_sum($_POST['users']['usermask']) >= $this->app->user->usermask()) || $this->app->user->id == intval($_POST['users']['id']) && array_sum($_POST['users']['usermask']) > $this->usermask())) {
            $this->app->delayedMessage("You can't set permissions beyond your own userlevel: ".array_sum($_POST['users']['usermask']), 'error');
            $this->app->redirect();
          }
          $updateErrors = False;
          try {
            $updateUser = $this->create_or_update($_POST['users']);
          } catch (ValidationException $e) {
            // validation exceptions don't need to be logged.
            $this->app->delayedMessage($e->formatMessages(), 'error');
            $this->app->redirect($this->id === 0 ? $this->url("new") : $this->url("edit"));
          }
          if ($updateUser) {
            $this->app->delayedMessage(isset($_POST['users']['id']) ? "Your user settings have been saved." : "Congratulations, you're now signed in!", 'success');
            $this->app->redirect($this->url("show"));
          } else {
            $this->app->delayedMessage("An error occurred while creating or updating this user.", 'error');
            $this->app->redirect($this->id === 0 ? $this->url("new") : $this->url("edit"));
          }
        }
        if ($this->id === 0) {
          $this->app->display_error(404);
        }
        $title = "Editing ".escape_output($this->username());
        $output = $this->view("edit");
        break;

      case 'activate':
        if (!$this->activationCode() || !isset($_REQUEST['code']) || $_REQUEST['code'] != $this->activationCode()) {
          $this->app->delayedMessage('The activation code you provided was incorrect. Please check your email and try again.', 'error');
          $this->app->redirect();
        } else {
          $this->app->dbConn->table(static::$MODEL_TABLE)->set(['activation_code' => Null])->where(['id' => $this->id])->update();
          $this->setCurrentSession();

          //update last IP address and last active.
          $currTime = new DateTime("now", $this->app->serverTmeZone);
          $updateUser = ['last_ip' => $_SERVER['REMOTE_ADDR'], 'last_active' => $currTime->format("Y-m-d H:i:s")];
          $this->create_or_update($updateUser);

          $this->app->delayedMessage("Congrats! You're now signed in as ".escape_output($username).". Why not start out by adding some anime to your list?", 'success');
          $this->app->redirect($this->url("register_conversion"));
        }
        break;

      case 'mal_import':
        // import a MAL list for this user.
        if (!isset($_POST['users']) || !is_array($_POST['users']) || !isset($_POST['users']['mal_username'])) {
          $this->app->delayedMessage('Please enter a MAL username.');
          $this->app->redirect();
        }
        $importMAL = $this->importMAL($_POST['users']['mal_username']);
        if (!in_array(False, $importMAL, True)) {
          $this->app->delayedMessage('Hooray! Your MAL was successfully imported.', 'success');
          $this->app->redirect($this->url("show"));
        } else {
          // some titles failed to import. fetch the title names for each failed ID so we can display them.
          $failedTitles = [];
          foreach ($importMAL as $id=>$status) {
            if ($status === False) {
              try {
                $thisAnime = new Anime($this->app, intval($id));
                $title = $thisAnime->title;
              } catch (DbException $e) {
                $title = "(Unknown anime ID: ".intval($id).")";
              }
              $failedTitles[] = $title;
            }
          }
          $this->app->delayedMessage('An error occurred while importing your MAL for the titles: '.implode(", ", $failedTitles).". Please check your MAL for any errors and if necessary, try again.", 'error');
          $this->app->redirect();
        }
        break;

      /* user profile views */
      case 'show':
        if ($this->id === 0) {
          $this->app->display_error(404);
        }
        $title = escape_output($this->username())."'s Profile";
        $output = $this->view("show");
        break;
      case 'feed':
        $output = "";
        if (isset($_REQUEST['maxTime']) && is_numeric($_REQUEST['maxTime'])) {
          $maxTime = '@'.intval($_REQUEST['maxTime']);
        } else {
          // if this isn't a non-current slice of the user's feed, append the usual forms at the top.
          $maxTime = "now";
          $newEntry = new AnimeEntry($this->app, Null, ['user' => $this]);
          if ($newEntry->allow($this->app->user, 'edit')) {
            $output .= $this->view('addEntryInlineForm');
          }
          if ($this->allow($this->app->user, 'comment')) {
            $blankComment = new Comment($this->app, 0, $this->app->user, $this);
            $output .= "                <div class='addListEntryForm'>
                        ".$blankComment->view('inlineForm', ['currentObject' => $this])."
                      </div>\n";

          }
        }
        $maxTime = new DateTime($maxTime, $this->app->serverTimeZone);
        $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime']) , $this->app->serverTimeZone) : Null;
        $output .= $this->view('feed', ['entries' => $this->profileFeed($minTime, $maxTime, 50), 'numEntries' => 50, 'feedURL' => $this->url('feed'), 'emptyFeedText' => '']);
        echo $output;
        exit;
      case 'anime_list':
        echo $this->animeList()->view("show");
        exit;
      case 'anime':
        if (!isset($_REQUEST['anime_id']) || !is_numeric($_REQUEST['anime_id'])) {
          echo "Please specify a valid anime ID.";
          exit;
        }
        if (!isset($this->animeList()->uniqueList[intval($_REQUEST['anime_id'])])) {
          echo json_encode([]);
          exit;
        }
        $latestEntry = $this->animeList()->uniqueList[intval($_REQUEST['anime_id'])];
        $latestEntry['anime_id'] = $latestEntry['anime']->id;
        $latestEntry['episode_count'] = $latestEntry['anime']->episodeCount;
        unset($latestEntry['anime']);
        echo json_encode($latestEntry);
        exit;
      case 'stats':
        echo $this->view('stats');
        exit;
      case 'friends':
        echo $this->view('friends');
        exit;
      case 'achievements':
        echo $this->view('achievements');
        exit;
      case 'achievements2':
        echo $this->view('achievements2');
        exit;
      case 'delete':
        if ($this->id == 0) {
          $this->app->display_error(404);
        } elseif (!$this->app->checkCSRF()) {
          $this->app->display_error(403);
        }
        $username = $this->username();
        $deleteUser = $this->delete();
        if ($deleteUser) {
          $this->app->delayedMessage('Successfully deleted '.$username.'.', 'success');
          $this->app->redirect();
        } else {
          $this->app->delayedMessage('An error occurred while deleting '.$username.'.', 'error');
          $this->app->redirect();
        }
        break;

      /* feed views */
      case 'globalFeed':
        $title = escape_output("Global Feed");
        $globalFeedView = [ 'numEntries' => 50,
          'entries' => $this->globalFeed(),
          'feedURL' => $this->url('globalFeedEntries'),
          'emptyFeedText' => ''
        ];
        // $convoFeedView = [ 'numEntries' => 50,
        //   'entries' => $this->conversationFeed(),
        //   'feedURL' => $this->url('conversationFeedEntries'),
        //   'emptyFeedText' => ''
        // ];
        $output = $this->view("globalFeed", ['global' => $globalFeedView/*, 'conversations' => $convoFeedView*/]);
        break;
      case 'globalFeedEntries':
        if (isset($_REQUEST['maxTime']) && is_numeric($_REQUEST['maxTime'])) {
          $maxTime = '@'.intval($_REQUEST['maxTime']);
        } else {
          $maxTime = "now";
        }
        $maxTime = new DateTime($maxTime, $this->app->serverTimeZone);
        if (isset($_REQUEST['minTime']) && is_numeric($_REQUEST['minTime'])) {
          $minTime = new DateTime('@'.intval($_REQUEST['minTime']), $this->app->serverTimeZone);
        } else {
          $minTime = Null;
        }
        $output .= $this->view('feed', ['entries' => $this->globalFeed($minTime, $maxTime, 50), 'numEntries' => 50, 'feedURL' => $this->url('globalFeedEntries'), 'emptyFeedText' => '']);
        echo $output;
        exit;

      /* Discover views */
      case 'discover':
        $_REQUEST['page'] = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        $title = escape_output("Discover Anime");
        $output = $this->view("discover", ['page' => intval($_REQUEST['page'])]);
        break;
      case 'recommendations':
        $_REQUEST['page'] = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        echo $this->view('recommendations', ['page' => intval($_REQUEST['page'])]);
        exit;
        break;
      case 'friendRecs':
        echo $this->view('friendRecs');
        exit;
      case 'groupwatches':
        echo $this->view('groupwatches');
        exit;
      default:
      case 'index':
        $title = "All Users";
        $output = $this->app->user->view('index');
        break;
    }
    return $this->app->render($output, ['subtitle' => $title]);
  }
  public function friendRequestsList() {
    // returns markup for the list of friend requests directed at this user.
    $output = "";
    foreach ($this->friendRequests() as $request) {
      $entryTime = new DateTime($request['time'], $this->app->serverTimeZone);
      $entryTime->setTimezone($this->app->outputTimeZone);
      $output .= "<li class='friendRequestEntry'><strong>".escape_output($request['user']->username())."</strong> requested to be your friend on ".$entryTime->format('G:i n/j/y').".".$this->link('confirm_friend', "Accept", Null, True, Null, Null, $request['user']->id)."</li>\n";
    }
    return $output;
  }
  public function profileFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup consisting of entries for this user's profile feed.
    if ($maxTime == Null) {
      $maxTime = new DateTime("now", $this->app->serverTimeZone);
    }
    $feedEntries = $this->animeList()->entries($minTime, $maxTime, $numEntries);
    $feedEntries->append($this->comments()->filter(function($a) use ($maxTime, $minTime) {
      return $a->time < $maxTime && $a->time > $minTime;
    })->sort(buildPropertySorter("time", -1))->limit($numEntries));
    return $feedEntries;
    //return $this->animeList()->feed($feedEntries, $numEntries, "<blockquote><p>No entries yet - add some above!</p></blockquote>\n");
  }
  public function conversationFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup corresponding to the last-active conversations that this user was a part of.
    $feedEntries = $this->comments();
    $feedEntries->append($this->ownComments());
    return $feedEntries;
  }
  public function globalFeed(DateTime $minTime=Null, DateTime $maxTime=Null, $numEntries=50) {
    // returns an EntryGroup of entries corresponding to this user's global feed.
    if ($maxTime == Null) {
      $maxTime = new DateTime("now", $this->app->serverTimeZone);
    }
    $this->app->addTiming("Start globalFeed");

    // add each friend's personal feed to the global feed.
    $feedEntries = $this->animeList()->entries($minTime, $maxTime, $numEntries);

    $this->app->addTiming("Finish user animeList");

    foreach ($this->friends() as $friend) {
      $feedEntries->append($friend['user']->animeList()->entries($minTime, $maxTime, $numEntries));
      $this->app->addTiming("Finish adding friend animeList");
      $comments = [];

      // now append all comments made by this friend between the given datetimes.
      $friendComments = $friend['user']->ownComments()->filter(function($a) use ($maxTime,$minTime) {
        return $a->time() < $maxTime && $a->time() > $minTime;
      });

      foreach ($friendComments->entries() as $commentEntry) {
        // only append top-level comments.
        if ($commentEntry->depth() === 0) {
          $comments[] = new CommentEntry($this->app, intval($commentEntry->id));
        }
      }
      $this->app->addTiming("Finish filtering friend comments");
      $feedEntries->append(new EntryGroup($this->app, $comments));
      $this->app->addTiming("Finish adding friend comments");
    }
    return $feedEntries;
  }
  public function url($action="show", $format=Null, array $params=Null, $username=Null) {
    // returns the url that maps to this object and the given action.
    if ($username === Null) {
      $username = $this->username();
    }
    return parent::url($action, $format, $params, $username);
  }
}
?>