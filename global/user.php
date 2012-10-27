<?php

class User {
  public $dbConn;
  public $id;
  public $username;
  public $name;
  public $email;
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
      $this->email = "";
      $this->animeEntries = $this->animeList = [];
    } else {
      $userInfo = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `usermask`, `email` FROM `users` WHERE `id` = ".intval($id)." LIMIT 1");
      $this->id = intval($userInfo['id']);
      $this->username = $userInfo['username'];
      $this->name = $userInfo['name'];
      $this->usermask = intval($userInfo['usermask']);
      $this->email = $userInfo['email'];
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
    $insert_log = $this->dbConn->stdQuery("INSERT IGNORE INTO `failed_logins` (`ip`, `time`, `username`, `password`) VALUES ('".$_SERVER['REMOTE_ADDR']."', NOW(), ".$this->quoteSmart($username).", ".$this->quoteSmart($password).")");
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
  public function register($username, $name, $email, $password, $password_confirmation, $facility_id) {
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
          $registerUser = $this->dbConn->stdQuery("INSERT INTO `users` SET `username` = ".$this->dbConn->quoteSmart($username).", `name` = ".$this->dbConn->quoteSmart($name).", `email` = ".$this->dbConn->quoteSmart($email).", `password_hash` = ".$this->dbConn->quoteSmart($bcrypt->hash($password)).", `usermask` = 1, `last_ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_active` = ".$this->dbConn->quoteSmart(unixToMySQLDateTime()).", `created_at` = ".$this->dbConn->quoteSmart(unixToMySQLDateTime()).", `avatar_path` = ''");
          if (!$registerUser) {
            $returnArray = array("location" => "register.php", "status" => "Database errors were encountered during registration. Please try again later.", 'class' => 'error');
          } else {
            $returnArray = array("location" => "register.php", "status" => "Registration successful. ".escape_output($name)." can now log in.", 'class' => 'success');
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
      $updateUser = $this->dbConn->stdQuery("UPDATE `users` SET ".implode(", ", $params).", `last_active` = NOW()  WHERE `id` = ".intval($this->id)." LIMIT 1");
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
    return $this->dbConn->queryAssoc("SELECT `id` FROM `anime_lists` WHERE `user_id` = ".intval($this->id)." ORDER BY `time` DESC");
  }
  public function getAnimeList() {
    // retrieves a list of anime_id, id arrays corresponding to the latest list entry for each anime this user has watched.
    return $this->dbConn->queryAssoc("SELECT `anime_id`, MAX(`id`) AS `id` FROM `anime_lists`
                                      WHERE `user_id` = ".intval($this->id)."
                                      GROUP BY `anime_id`");
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
  public function profile() {
    // displays a user's profile.
    return;
    $userObject = new User($database, $user_id);
    $facility = $database->queryFirstValue("SELECT `name` FROM `facilities` WHERE `id` = ".intval($userObject->facility_id)." LIMIT 1");
    $form_entries = $database->stdQuery("SELECT `form_entries`.*, `forms`.`name` AS `form_name`, `machines`.`name` AS `machine_name` FROM `form_entries` 
                                          LEFT OUTER JOIN `forms` ON `forms`.`id` = `form_entries`.`form_id`
                                          LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id`
                                          WHERE `user_id` = ".intval($user_id)." 
                                          ORDER BY `updated_at` DESC");
    echo "<dl class='dl-horizontal'>
      <dt>Email</dt>
      <dd>".escape_output($userObject->email)."</dd>
      <dt>Facility</dt>
      <dd><a href='facility.php?action=show&id=".intval($userObject->facility_id)."'>".escape_output($facility)."</a></dd>
      <dt>User Role</dt>
      <dd>".escape_output(convert_userlevel_to_text($userObject->userlevel))."</dd>
    </dl>\n";
    if (convert_userlevel_to_text($userObject->userlevel) == 'Physicist') {
      $form_approvals = $database->stdQuery("SELECT `form_entries`.`id`, `qa_month`, `qa_year`, `machine_id`, `machines`.`name` AS `machine_name`, `user_id`, `users`.`name` AS `user_name`, `approved_on` FROM `form_entries` LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id` LEFT OUTER JOIN `users` ON `users`.`id` = `form_entries`.`user_id` WHERE `approved_user_id` = ".intval($userObject->id)." ORDER BY `approved_on` DESC");
      echo "  <h3>Approvals</h3>
    <table class='table table-striped table-bordered dataTable'>
      <thead>
        <tr>
          <th>QA Date</th>
          <th>Machine</th>
          <th>Submitter</th>
          <th>Approval Date</th>
        </tr>
      </thead>
      <tbody>\n";
      while ($approval = mysqli_fetch_assoc($form_approvals)) {
        echo "      <tr>
          <td><a href='form_entry.php?action=edit&id=".intval($approval['id'])."'>".escape_output($approval['qa_year']."/".$approval['qa_month'])."</a></td>
          <td><a href='form.php?action=show&id=".intval($approval['machine_id'])."'>".escape_output($approval['machine_name'])."</a></td>
          <td><a href='user.php?action=show&id=".intval($approval['user_id'])."'>".escape_output($approval['user_name'])."</a></td>
          <td>".escape_output(format_mysql_timestamp($approval['approved_on']))."</td>
        </tr>\n";
      }
      echo "    </tbody>
    </table>\n";
    }
    echo "  <h3>Form Entries</h3>
    <table class='table table-striped table-bordered dataTable'>
      <thead>
        <tr>
          <th>Form</th>
          <th>Machine</th>
          <th>Comments</th>
          <th>QA Date</th>
          <th>Submitted on</th>
          <th></th>
        </tr>
      </thead>
      <tbody>\n";
    while ($form_entry = mysqli_fetch_assoc($form_entries)) {
      echo "    <tr>
        <td><a href='form.php?action=show&id=".intval($form_entry['form_id'])."'>".escape_output($form_entry['form_name'])."</a></td>
        <td><a href='form.php?action=show&id=".intval($form_entry['machine_id'])."'>".escape_output($form_entry['machine_name'])."</a></td>
        <td>".escape_output($form_entry['comments'])."</td>
        <td>".escape_output($form_entry['qa_year']."/".$form_entry['qa_month'])."</td>
        <td>".escape_output(format_mysql_timestamp($form_entry['created_at']))."</td>
        <td><a href='form_entry.php?action=edit&id=".intval($form_entry['id'])."'>View</a></td>
      </tr>\n";
    }
    echo "    </tbody>
    </table>\n";
  }
  public function form($currentUser) {
    $output = "<form action='user.php".(($this->id === 0) ? "" : "?id=".intval($this->id))."' method='POST' class='form-horizontal'>\n".(($this->id === 0) ? "" : "<input type='hidden' name='user[id]' value='".intval($this->id)."' />")."
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
        </div>\n";
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