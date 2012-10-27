<?php

class User {
  public $id;
  public $username;
  public $name;
  public $email;
  public $usermask;
  public $dbConn;
  public $createdAt;
  public $lastActive;
  public $lastIP;
  public $avatarPath;
  public function __construct($database, $id=Null) {
    $this->dbConn = $database;
    if ($id === 0) {
      $this->id = 0;
      $this->username = "guest";
      $this->name = "Guest";
      $this->usermask = 0;
      $this->email = "";
    } else {
      $userInfo = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `usermask`, `email` FROM `users` WHERE `id` = ".intval($id)." LIMIT 1");
      $this->id = intval($userInfo['id']);
      $this->username = $userInfo['username'];
      $this->name = $userInfo['name'];
      $this->usermask = intval($userInfo['usermask']);
      $this->email = $userInfo['email'];
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
      return false;
    }
    $thisUserInfo = $this->dbConn->queryFirstRow("SELECT `last_ip` FROM `users` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if ($thisUserInfo['last_ip'] != $_SERVER['REMOTE_ADDR']) {
      return false;
    }
    return true;
  }
  public function logIn($username, $password) {
    // rate-limit requests.
    $numFailedRequests = $this->dbConn->queryCount("SELECT COUNT(*) FROM `failed_logins` WHERE `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR'])." AND `date` > NOW() - INTERVAL 1 HOUR");
    if ($numFailedRequests > 5) {
      return array("location" => "index.php", "status" => "You have had too many unsuccessful login attempts. Please wait awhile and try again.", 'class' => 'error');
    }
  
    $bcrypt = new Bcrypt();
    $findUsername = $this->dbConn->queryFirstRow("SELECT `id`, `username`, `name`, `usermask`, `password_hash` FROM `users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1");
    if (!$findUsername) {
      $this->dbConn->log_failed_login($username, $password);
      return array("location" => "index.php", "status" => "Could not log in with the supplied credentials.", 'class' => 'error');
    }
    if (!$bcrypt->verify($password, $findUsername['password_hash'])) {
      $this->dbConn->log_failed_login($username, $password);
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
      $updateUser = $this->dbConn->stdQuery("UPDATE `users` SET ".implode(", ", $params)."  WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
      return intval($this->id);
    } else {
      // add this facility.
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `users` SET ".implode(",", $params));
      if (!$insertUser) {
        return False;
      } else {
        return intval($this->dbConn->insert_id);
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
  public function getFacility() {
    // retrieves an id,name array corresponding to this users's facility.
    return $this->dbConn->queryFirstRow("SELECT `facilities`.`id`, `facilities`.`name` FROM `users` LEFT OUTER JOIN `facilities` ON `facilities`.`id` = `users`.`facility_id` WHERE `users`.`id` = ".intval($this->id));
  }
  public function getFormEntries() {
    // retrieves a list of id arrays corresponding to form entries belonging to this user.
    return $this->dbConn->queryAssoc("SELECT `id` FROM `form_entries` WHERE `user_id` = ".intval($this->id)." ORDER BY `updated_at` DESC");
  }
  public function getApprovals() {
    // retrieves a list of FormEntry objects that the user has approved, ordered by updated_at desc.
    $formEntryQuery = $this->dbConn->stdQuery("SELECT `id` FROM `form_entries` WHERE `approved_user_id` = ".intval($this->id)." ORDER BY `updated_at` DESC");
    $formEntries = [];
    while ($entry = $formEntryQuery->fetch_assoc()) {
      $formEntries[] = new FormEntry($this->dbConn, intval($entry['id']));
    }
    return $formEntries;
  }
  public function link($action="show", $text="Profile") {
    // returns an HTML link to the current user's profile, with text provided.
    return "<a href='/user.php?action=".urlencode($action)."&id=".intval($this->id)."'>".escape_output($text)."</a>";
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
  public function editForm($currentUser) {
    $output = "<form action='user.php".(($id === 0) ? "" : "?id=".intval($id))."' method='POST' class='form-horizontal'>\n".(($id === 0) ? "" : "<input type='hidden' name='user[id]' value='".intval($this->id)."' />")."
  <fieldset>
    <div class='control-group'>
      <label class='control-label' for='user[name]'>Name</label>
      <div class='controls'>
        <input name='user[name]' type='text' class='input-xlarge' id='user[name]'".(($id === 0) ? "" : " value='".escape_output($this->name)."'").">
      </div>
    </div>
    <div class='control-group'>
      <label class='control-label' for='user[name]'>Username</label>
      <div class='controls'>
        <input name='user[username]' type='text' class='input-xlarge' id='user[username]'".(($id === 0) ? "" : " value='".escape_output($this->username)."'").">
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
      <label class='control-label' for='user[name]'>Email</label>
      <div class='controls'>
        <input name='user[email]' type='email' class='input-xlarge' id='user[email]'".(($id === 0) ? "" : " value='".escape_output($this->email)."'").">
      </div>
    </div>\n";
    if ($currentUser->isAdmin()) {
      $output .= "      <div class='control-group'>
      <label class='control-label' for='user[userlevel]'>Role</label>
      <div class='controls'>\n".display_userlevel_dropdown($database, "user[userlevel]", ($id === 0) ? 0 : intval($this->userlevel))."      </div>
    </div>\n";
    }
    $output .= "    <div class='form-actions'>
      <button type='submit' class='btn btn-primary'>".(($id === 0) ? "Add User" : "Save changes")."</button>
      <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($id === 0) ? "Go back" : "Discard changes")."</a>
    </div>
  </fieldset>\n</form>\n";
    return $output;
  }
}

?>