<?php
function humanize($str) {
  $str = trim(strtolower($str));
  $str = preg_replace('/_/', ' ', $str);
  $str = preg_replace('/[^a-z0-9\s+]/', '', $str);
  $str = preg_replace('/\s+/', ' ', $str);
  $str = explode(' ', $str);

  $str = array_map('ucwords', $str);

  return implode(' ', $str);
}

function unixToMySQLDateTime($timestamp=False) {
  if ($timestamp === False) {
    $timestamp = time();
  }
  $dateObject = new DateTime('@'.$timestamp);
  $outputTimeZone = new DateTimeZone(OUTPUT_TIMEZONE);
  $dateObject->setTimeZone($outputTimeZone);
  return $dateObject->format("Y-m-d H:i:s");
}

function format_mysql_timestamp($date) {
  return date('n/j/Y', strtotime($date));
}

function display_post_time($unixtime) {
  return date('Y/m/d H:i', $unixtime);
}

function escape_output($input) {
  if ($input == '' || $input == 'NULL') {
    return '';
  }
  return htmlspecialchars(html_entity_decode($input, ENT_QUOTES, "UTF-8"), ENT_QUOTES, "UTF-8");
}

function ago($dateInterval){
  $m = $dateInterval->s;
  if ($dateInterval->y > 0) {
    $o = $dateInterval->y."yr";
  } elseif ($dateInterval->m > 0) {
    $o = $dateInterval->m."mo";
  } elseif ($dateInterval->d > 0) {
    $o = $dateInterval->d."d";
  } elseif ($dateInterval->h > 0) {
    $o = $dateInterval->h."h";
  } elseif ($dateInterval->i > 0) {
    $o = $dateInterval->i."min";
  } elseif ($dateInterval->s > 0) {
    $o = $dateInterval->s."s";
  } else {
    $o = "just now";
  }
  return $o;
}

function redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : '/';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = "Location: ".$location;
  if ($status != "") {
    if (strpos($location, "?") === FALSE) {
      $redirect .= "?status=".$status."&class=".$class;
    } else {
      $redirect .= "&status=".$status."&class=".$class;
    }
  }
  header($redirect);
  exit;
}

function js_redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : '/';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = ROOT_URL."/".$location;
  if ($status != "") {
    if (strpos($location, "?") === FALSE) {
      $redirect .= "?status=".urlencode($status)."&class=".urlencode($class);
    } else {
      $redirect .= "&status=".urlencode($status)."&class=".urlencode($class);
    }
  }
  echo "window.location.replace(\"".$redirect."\");";
  exit;
}

function display_http_error($code=500, $contents="") {
  switch (intval($code)) {
    case 301:
      $subtitle = "Moved Permanently";
      $bodyText = $contents;
      break;
    case 403:
      $subtitle = "Forbidden";
      $bodyText = "I'm sorry, Dave. I'm afraid I can't do that.";
      break;
    case 404:
      $subtitle = "Not Found";
      $bodyText = "Oh geez. We couldn't find the page you were looking for; please check your URL and try again.";
      break;
    default:
    case 500:
      $subtitle = "Internal Server Error";
      $bodyText = "Whoops! We had problems processing your request. Please go back and try again!";
      break;
  }

  header('HTTP/1.0 '.intval($code).' '.$subtitle);
  echo $bodyText;
  exit;
}

function display_error($title="Error", $text="An unknown error occurred. Please try again.") {
  return "<h1>".escape_output($title)."</h1>
  <p>".escape_output($text)."</p>";
}

function paginate($baseLink, $currPage=1, $maxPages=1) {
  // displays a pagination bar.
  //baseLink should be everything up to, say, &page=
  $pageIncrement = 10;
  $displayFirstPages = 10;
  $output = "<div class='pagination pagination-small pagination-centered'>
  <ul>\n";
  $i = 1;
  if ($currPage > 1) {
    $output .= "    <li><a href='".$baseLink.($currPage-1)."'>«</a></li>\n";
  }
  while ($i <= $maxPages) {
  if ($i == $currPage) {
    $output .= "    <li class='active'><a href='#'>".$i."</a></li>";     
  } else {
    $output .= "    <li><a href='".$baseLink.$i."'>".$i."</a></li>";
  }
      if ($i < $displayFirstPages || abs($currPage - $i) <= $pageIncrement ) {
          $i++;
      } elseif ($i >= $displayFirstPages && $maxPages <= $i + $pageIncrement) {
          $i++;
      } elseif ($i >= $displayFirstPages && $maxPages > $i + $pageIncrement) {
          $i += $pageIncrement;
      }
  }
  if ($currPage < $maxPages) {
    $output .= "    <li><a href='".$baseLink.($currPage+1)."'>»</a></li>\n";
  }
  $output .= "  </ul>\n</div>\n";
    return $output;
}

function start_html($database, $user, $title="Animurecs", $subtitle="", $status="", $statusClass="") {
  echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>\n<head>
	<meta http-equiv='content-type' content='text/html; charset=utf-8' />
	<title>".escape_output($title).($subtitle != '' ? ' - '.escape_output($subtitle) : '')."</title>
	<link rel='shortcut icon' href='/favicon.ico' />

	<link rel='stylesheet' href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' type='text/css' />
  <link rel='stylesheet' href='".ROOT_URL."/css/jquery.dataTables.css' type='text/css' />
  <link rel='stylesheet' href='".ROOT_URL."/css/token-input.css' type='text/css' />
  <link rel='stylesheet' href='".ROOT_URL."/css/animurecs.css' type='text/css' />

  <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js'></script>
  <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js'></script>

  <script type='text/javascript' src='".ROOT_URL."/jquery-ui-timepicker-addon.js'></script>
	<script type='text/javascript' language='javascript' src='".ROOT_URL."/js/jquery.dropdownPlain.js'></script>
	<script type='text/javascript' language='javascript' src='".ROOT_URL."/js/jquery.dataTables.min.js'></script>
  <script type='text/javascript' language='javascript' src='".ROOT_URL."/js/jquery.tokeninput.js'></script>
  <script type='text/javascript' language='javascript' src='".ROOT_URL."/js/jquery.json-2.3.min.js'></script>

  <script type='text/javascript' src='https://www.google.com/jsapi'></script>
  <script type='text/javascript' src='".ROOT_URL."/js/d3.v2.min.js'></script>
  <script type='text/javascript' src='".ROOT_URL."/js/d3-helpers.js'></script>

	<script type='text/javascript' language='javascript' src='".ROOT_URL."/js/bootstrap.min.js'></script>
	<script type='text/javascript' language='javascript' src='".ROOT_URL."/js/bootstrap-dropdown.js'></script>

	<script type='text/javascript' language='javascript' src='".ROOT_URL."/js/animurecs.js'></script>\n</head>\n<body>
  <div class='navbar navbar-inverse navbar-fixed-top'>
    <div class='navbar-inner'>
      <div class='container-fluid'>
        <a href='/' class='brand'>Animurecs</a>
        <ul class='nav'>\n";
  if ($user->loggedIn()) {
    echo "          <li class='divider-vertical'></li>
          <li><a href='/feed.php'><i class='icon-th-list icon-white'></i> Feed</a></li>
          <li class='divider-vertical'></li>
          <li>".$user->link("show", "<i class='icon-home icon-white'></i> You", True)."</li>
          <li class='divider-vertical'></li>
          <li><a href='/users/'><i class='icon-globe icon-white'></i> Connect</a></li>
          <li class='divider-vertical'></li>
          <li><a href='/discover/'><i class='icon-star icon-white'></i> Discover</a></li>
          <li class='divider-vertical'></li>\n";
  }
  echo "        </ul>
        <ul class='nav pull-right'>\n";
  if ($user->loggedIn()) {
    echo "<li id='navbar-alerts'>\n";
    if (count($user->friendRequests) > 0) {
      echo "            <span class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='#'><span class='badge badge-info'>".count($user->friendRequests)."</span></a>
              <ul class='dropdown-menu'>
                ".$user->friendRequestsList()."
              </ul>
            </span>\n";
    }
    echo "          </li>
          <li id='navbar-user' class='dropdown'>
            <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='icon-user icon-white'></i>".escape_output($user->username)."<b class='caret'></b></a>
            <ul class='dropdown-menu'>
              ".$user->link("show", "Profile");
    if ($user->isAdmin() && !isset($user->switchedUser)) {
      echo "            ".$user->link("switch_user", "Switch User")."\n";
    }
    if (isset($user->switchedUser) && is_numeric($user->switchedUser)) {
      echo "            ".$user->link("switch_back", "Switch Back");
    }
    echo "              <a href='/logout.php'>Sign out</a>
            </ul>\n";
  } else {
    echo "          <li>
          <form class='form-inline' accept-charset='UTF-8' action='/login.php' method='post'>
            <input name='username' type='text' class='input-small' placeholder='Username'>
            <input name='password' type='password' class='input-small' placeholder='Password'>
            <!--<label class='checkbox'>
              <input type='checkbox'> Remember me
            </label>-->
            <button type='submit' class='btn btn-primary btn-small'>Sign in</button>
          </form></li>
        </ul>\n";
  }
  echo "          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class='container-fluid'>\n";
  if ($status != '') {
    echo "<div class='alert alert-".escape_output($statusClass)."'>
  <button class='close' data-dismiss='alert' href='#'>×</button>
  ".escape_output($status)."\n</div>\n";
  }
}

function display_login_form() {
  echo "<form id='login_form' class='form' accept-charset='UTF-8' action='/login.php' method='post'>
  <input id='username' name='username' size='30' type='text' placeholder='Username' />
  <input id='password' name='password' size='30' type='password' placeholder='Password' />
  <input class='btn btn-primary' name='commit' type='submit' value='Sign in' />\n</form>\n";
}

function display_status_dropdown($select_id="anime_list[status]", $class="", $selected=False) {
  $statuses = array(
      0 => "Remove",
      1 => "Currently Watching",
      2 => "Completed",
      3 => "On Hold",
      4 => "Plan to Watch",
      6 => "Dropped"
  );
  $output = "<select class='".escape_output($class)."' id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  foreach ($statuses as $id => $text) {
    $output .= "<option value='".intval($id)."'".(($selected == intval($id)) ? "selected='selected'" : "").">".escape_output($text)."</option>\n";
  }
  $output .= "</select>\n";
  return $output;
}

function display_month_year_dropdown($select_id="", $select_name_prefix="form_entry", $selected=False) {
  if ($selected === false) {
    $selected = array( 0 => intval(date('n')), 1 => intval(date('Y')));
  }
  echo "<select id='".escape_output($select_id)."' name='".escape_output($select_name_prefix)."[qa_month]'>\n";
  for ($month_i = 1; $month_i <= 12; $month_i++) {
    echo "  <option value='".$month_i."'".(($selected[0] === $month_i) ? "selected='selected'" : "").">".htmlentities(date('M', mktime(0, 0, 0, $month_i, 1, 2000)), ENT_QUOTES, "UTF-8")."</option>\n";
  }
  echo "</select>\n<select id='".escape_output($select_id)."' name='".escape_output($select_name_prefix)."[qa_year]'>\n";
  for ($year = 2007; $year <= intval(date('Y', time())); $year++) {
    echo "  <option value='".$year."'".(($selected[1] === $year) ? "selected='selected'" : "").">".$year."</option>\n";
  }
  echo "</select>\n";
}

function display_ok_notok_dropdown($select_id="ok_notok", $selected=0) {
  echo "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>
                    <option value=1".((intval($selected) == 1) ? " selected='selected'" : "").">OK</option>
                    <option value=0".((intval($selected) == 0) ? " selected='selected'" : "").">NOT OK</option>\n</select>\n";
}

function display_register_form($database, $action=".") {
  echo "    <form class='form-horizontal' name='register' method='post' action=".$_SERVER['SCRIPT_NAME'].">
      <fieldset>
        <legend>Signing up is easy! Fill in a few things...</legend>
        <div class='control-group'>
          <label class='control-label'>A username:</label>
          <div class='controls'>
            <input type='text' class='' name='username' id='username' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Your password:</label>
          <div class='controls'>
            <input type='password' class='' name='password' id='password' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Repeat that password:</label>
          <div class='controls'>
            <input type='password' class='' name='password_confirmation' id='password_confirmation' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>Your email:</label>
          <div class='controls'>
            <input type='text' class='' name='email' id='email' />
          </div>
        </div>
        <div class='control-group'>
          <label class='control-label'>... And you're done!</label>
          <div class='controls'>
            <button type='submit' class='btn btn-primary'>Sign up</button>
          </div>
        </div>
      </fieldset>
    </form>\n";
}

function display_users($database, $user) {
  //lists all users.
  $output = "<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Username</th>
      <th>Role</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>\n";
  if ($user->isAdmin()) {
    $users = $database->stdQuery("SELECT `users`.`id` FROM `users` ORDER BY `users`.`username` ASC");
  } else {
    $users = $database->stdQuery("SELECT `users`.`id` FROM `users` ORDER BY `users`.`username` ASC");
  }
  while ($thisID = $users->fetch_assoc()) {
    $thisUser = new User($database, intval($thisID['id']));
    $output .= "    <tr>
      <td>".$thisUser->link("show", $thisUser->username)."</td>
      <td>".escape_output(convert_usermask_to_text($thisUser->usermask))."</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisUser->link("edit", "Edit"); } $output .= "</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisUser->link("delete", "Delete"); } $output .= "</td>
    </tr>\n";
  }
  $output .= "  </tbody>\n</table>\n";
  return $output;
}

function display_anime($database, $user) {
  // lists all anime.
  $resultsPerPage = 25;
  $newAnime = new Anime($database, 0);
  if ($user->isAdmin()) {
    $anime = $database->stdQuery("SELECT `anime`.`id` FROM `anime` ORDER BY `anime`.`title` ASC LIMIT ".((intval($_REQUEST['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $animePages = ceil($database->queryCount("SELECT COUNT(*) FROM `anime`")/$resultsPerPage);
  } else {
    $anime = $database->stdQuery("SELECT `anime`.`id` FROM `anime` WHERE `approved_on` != '' ORDER BY `anime`.`title` ASC LIMIT ".((intval($_REQUEST['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $animePages = ceil($database->queryCount("SELECT COUNT(*) FROM `anime` WHERE `approved_on` != ''")/$resultsPerPage);
  }
  $output = paginate($newAnime->url("index", "page="), intval($_REQUEST['page']), $animePages);
  $output .= "<table class='table table-striped table-bordered dataTable' data-recordsPerPage='25'>
  <thead>
    <tr>
      <th>Title</th>
      <th>Description</th>
      <th>Length</th>\n";
  if ($newAnime->allow($user, 'edit')) {
    $output .= "      <th></th>\n";
  }
  if ($newAnime->allow($user, 'delete')) {
    $output .= "      <th></th>\n";
  }
  $output .= "    </tr>
  </thead>
  <tbody>\n";
  while ($thisID = $anime->fetch_assoc()) {
    $thisAnime = new Anime($database, intval($thisID['id']));
    $output .= "    <tr>
      <td>".$thisAnime->link("show", $thisAnime->title)."</td>
      <td>".escape_output($thisAnime->description)."</td>
      <td>".intval($thisAnime->episodeCount * $thisAnime->episodeLength)." minutes</td>\n";
    if ($newAnime->allow($user, 'edit')) { 
      $output .= "      <td>".$thisAnime->link("edit", "Edit")."</td>\n";
    }
    if ($newAnime->allow($user, 'delete')) { 
      $output .= "      <td>".$thisAnime->link("delete", "Delete")."</td>\n";
    }
    $output .= "    </tr>\n";
  }
  $output .= "  </tbody>\n</table>\n".($newAnime->allow($user, 'new') ? $newAnime->link("new", "Add an anime") : "")."\n";
  $output .= paginate($newAnime->url("index", "page="), intval($_REQUEST['page']), $animePages)."\n";
  return $output;
}

function display_recommendations($recsEngine, $user) {
  $recs = $recsEngine->recommend($user);

  $output = "<h1>Your recommendations</h1>\n<ul class='recommendations'>\n";
  foreach ($recs as $key=>$rec) {
    $anime = new Anime($user->dbConn, intval($rec->id));
    $output .= "<li>".$anime->link("show", "<img src='".$anime->imagePath."' /><div>".escape_output($anime->title)."</div>", True)."<em>Predicted score: ".round($rec->predicted_score, 1)."</em></li>\n";
  }
  $output .= "</ul>";

  return $output;
}

function display_tags($database, $user) {
  // lists all tags.
  $resultsPerPage = 25;
  $newTag = new Tag($database, 0);
  if ($user->isAdmin()) {
    $tag = $database->stdQuery("SELECT `tags`.`id` FROM `tags` ORDER BY `tags`.`name` ASC LIMIT ".((intval($_REQUEST['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagPages = ceil($database->queryCount("SELECT COUNT(*) FROM `tags`")/$resultsPerPage);
  } else {
    $tag = $database->stdQuery("SELECT `tags`.`id` FROM `tags` WHERE `approved_on` != '' ORDER BY `tags`.`name` ASC LIMIT ".((intval($_REQUEST['page'])-1)*$resultsPerPage).",".intval($resultsPerPage));
    $tagPages = ceil($database->queryCount("SELECT COUNT(*) FROM `tags` WHERE `approved_on` != ''")/$resultsPerPage);
  }
  $output = paginate($newTag->url("index", "page="), intval($_REQUEST['page']), $tagPages);
  $output .= "<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Type</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>\n";
  while ($thisID = $tag->fetch_assoc()) {
    $thisTag = new Tag($database, intval($thisID['id']));
    $output .= "    <tr>
      <td>".$thisTag->link("show", $thisTag->name)."</td>
      <td>".escape_output($thisTag->description)."</td>
      <td>".$thisTag->type->link("show", $thisTag->type->name)."</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisTag->link("edit", "Edit"); } $output .= "</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisTag->link("delete", "Delete"); } $output .= "</td>
    </tr>\n";
  }
  $output .= "  </tbody>\n</table>\n".$newTag->link("new", "Add a tag");
  $output .= paginate($newTag->url("index", "page="), intval($_REQUEST['page']), $tagPages)."\n";
  return $output;
}

function display_tag_types($database, $user) {
  // lists all tag types.
  $newTagType = new TagType($database, 0);
  $output = "<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Created By</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>\n";
  $tagTypes = $database->stdQuery("SELECT `tag_types`.`id` FROM `tag_types` ORDER BY `tag_types`.`name` ASC");
  while ($thisID = $tagTypes->fetch_assoc()) {
    $thisTagType = new TagType($database, intval($thisID['id']));
    $output .= "    <tr>
      <td>".$thisTagType->link("show", $thisTagType->name)."</td>
      <td>".escape_output($thisTagType->description)."</td>
      <td>".$thisTagType->createdUser->link("show", $thisTagType->createdUser->username)."</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisTagType->link("edit", "Edit"); } $output .= "</td>
      <td>"; if ($user->isAdmin()) { $output .= $thisTagType->link("delete", "Delete"); } $output .= "</td>
    </tr>\n";
  }
  $output .= "  </tbody>\n</table>\n".$newTagType->link("new", "Add a tag type");
  return $output;
}

function display_tag_type_dropdown($database, $select_id="tag[tag_type_id]", $selected=0) {
  $output = "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  $allTypes = $database->stdQuery("SELECT `id`, `name` FROM `tag_types` ORDER BY `name` ASC");
  while ($type = $allTypes->fetch_assoc()) {
    $output .= "<option value='".intval($type['id'])."'".(($selected == intval($type['id'])) ? "selected='selected'" : "").">".escape_output($type['name'])."</option>\n";
  }
  $output .= "</select>\n";
  return $output;
}

function display_user_roles_select($select_id="user[usermask][]", $mask=0) {
  $output = "";
  for ($usermask = 0; $usermask <= 2; $usermask++) {
    $output .= "<label class='checkbox'>
  <input type='checkbox' name='".escape_output($select_id)."' value='".intval(pow(2, $usermask))."'".(($mask & intval(pow(2, $usermask))) ? "checked='checked'" : "")." />".escape_output(convert_usermask_to_text(pow(2, $usermask)))."\n</label>\n";
  }
  return $output;
}

function display_userlevel_dropdown($database, $select_id="userlevel", $selected=0) {
  $output = "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  for ($userlevel = 0; $userlevel <= 3; $userlevel++) {
    $output .= "  <option value='".intval($userlevel)."'".(($selected == intval($userlevel)) ? "selected='selected'" : "").">".escape_output(convert_userlevel_to_text($userlevel))."</option>\n";
  }
  $output .= "</select>\n";
  return $output;
}

function display_user_edit_form($database, $user, $id=false) {
}

function display_history_json($database, $user, $fields = array(), $machines=array()) {
  header('Content-type: application/json');
  $return_array = array();
  
  if (!$user->loggedIn()) {
    $return_array['error'] = "You must be logged in to view history data.";
  } elseif (!is_array($fields) || !is_array($machines)) {
    $return_array['error'] = "Please provide a valid list of fields and machines.";  
  } else {
    foreach ($fields as $field) {
      foreach ($machines as $machine) {
        $line_array = array();
        $values = $database->stdQuery("SELECT `form_field_id`, `form_entries`.`machine_id`, `form_entries`.`qa_month`, `form_entries`.`qa_year`, `value` FROM `form_values`
                                    LEFT OUTER JOIN `form_entries` ON `form_entry_id` = `form_entries`.`id`
                                    WHERE `form_field_id` = ".intval($field)." && `machine_id` = ".intval($machine)."
                                    ORDER BY `qa_year` ASC, `qa_month` ASC");
        while ($value = mysqli_fetch_assoc($values)) {
          $line_array[] = array('x' => intval($value['qa_month'])."/".intval($value['qa_year']),
                                  'y' => doubleval($value['value']),
                                  'machine' => intval($value['machine_id']),
                                  'field' => intval($value['form_field_id']));
        }
        if (count($line_array) > 1) {
          $return_array[] = $line_array;
        }
      }
    }
  }
  echo json_encode($return_array);
}

function display_history_plot($database, $user, $form_id) {
  //displays plot for a particular form.
  $formObject = $database->queryFirstRow("SELECT * FROM `forms` WHERE `id` = ".intval($form_id)." LIMIT 1");
  if (!$formObject) {
    echo "The form ID you provided was invalid. Please try again.\n";
  } else {
    $formFields = $database->stdQuery("SELECT `id`, `name` FROM `form_fields`
                                        WHERE `form_id` = ".intval($form_id)."
                                        ORDER BY `name` ASC");
    $machines = $database->stdQuery("SELECT `id`, `name` FROM `machines`
                                        WHERE `machine_type_id` = ".intval($formObject['machine_type_id'])."
                                        ORDER BY `name` ASC");
    echo "<div id='vis'></div>
  <form action='#'>
    <input type='hidden' id='form_id' name='form_id' value='".intval($form_id)."' />
    <div class='row-fluid'>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Machines</h3></div>
        <div class='row-fluid'>
          <select multiple='multiple' id='machines' class='span12' size='10' name='machines[]'>\n";
    while ($machine = mysqli_fetch_assoc($machines)) {
      echo "           <option value='".intval($machine['id'])."'>".escape_output($machine['name'])."</option>\n";
    }
    echo "         </select>
        </div>
      </div>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Fields</h3></div>
        <div class='row-fluid'>
          <select multiple='multiple' id='form_fields' class='span12' size='10' name='form_fields[]'>\n";
    while ($field = mysqli_fetch_assoc($formFields)) {
      echo "            <option value='".intval($field['id'])."'>".escape_output($field['name'])."</option>\n";
    }
    echo "          </select>
        </div>
      </div>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Time Range</h3></div>
        <div class='row-fluid'>
          <div class='span12' style='text-align:center;'>(Coming soon)</div>
        </div>
      </div>
    </div>
    <div class='row-fluid'>
      <div class='span12' style='text-align:center;'>As a reminder, you can highlight multiple fields by either clicking and dragging, or holding down Control and clicking on the fields you want.</div>
    </div>
    <div class='form-actions'>
      <a class='btn btn-xlarge btn-primary' href='#' onClick='drawLargeD3Plot();'>Redraw Plot</a>
    </div>
  </form>\n";
  }
}

function display_footer() {
  echo "    <hr />
    <p>Created and maintained by <a href='".ROOT_URL."/users/1/show/'>shaldengeki</a>.</p>\n";
  if (DEBUG_ON) {
    echo "<pre>".escape_output(print_r($GLOBALS['database']->queryLog, True))."</pre>\n";
    echo "<pre>".escape_output(print_r($GLOBALS, True))."</pre>\n";
  }
  echo "  </div>\n</body>\n</html>";
}
?>