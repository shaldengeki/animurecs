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

function shortenText($text, $maxLen=100) {
  if (strlen($text) <= $maxLen) {
    return $text;
  } else {
    return substr($text, 0, $maxLen-3)."...";
  }
}

function unixToMySQLDateTime($timestamp=Null, $timezone=Config::OUTPUT_TIMEZONE) {
  if ($timestamp === Null) {
    $timestamp = time();
  }
  $dateObject = new DateTime('@'.$timestamp);
  $outputTimeZone = new DateTimeZone(Config::OUTPUT_TIMEZONE);
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

function ago(DateInterval $dateInterval){
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

function redirect_to($location, array $params=Null) {
  $paramString = "";
  if (strpos($location, "?") === False) {
    $connector = "?";
  } else {
    $connector = "&";
  }
  if ($params !== Null) {
    $paramString = $connector.http_build_query($params);
  }

  $redirect = "Location: ".$location.$paramString;
  header($redirect);
  exit;
}

function js_redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : '/';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = Config::ROOT_URL."/".$location;
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

function paginate($baseLink, $currPage=1, $maxPages=1) {
  // displays a pagination bar.
  //baseLink should be everything up to, say, &page=
  $pageIncrement = 10;
  $displayFirstPages = 10;
  $output = "<div class='pagination pagination-centered'>
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
      4 => "Dropped",
      6 => "Plan to Watch"
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

function display_register_form(DbConn $database, $action=".") {
  $output = "    <form class='form-horizontal' name='register' method='post' action=".$_SERVER['SCRIPT_NAME'].">
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
  return $output;
}

function tag_list($animes, $n=50) {
  // displays a list of tags for a list of anime, sorted by frequency of tag.
  if ($animes instanceof Anime) {
    $animes = array($animes);
  }
  if (!($animes instanceof AnimeGroup)) {
    if (count($animes) > 0) {
      $app = current($animes)->app;
    } else {
      return;
    }
    $animes = new AnimeGroup($app, $animes);
  }
  $tagCounts = $animes->tagCounts();
  $output = "<ul class='tagList'>\n";
  $i = 1;
  $animes->tags()->load('info');
  foreach ($tagCounts as $id=>$count) {
    $output .= "<li>".$animes->tags()[$id]->link("show", $animes->tags()[$id]->name)." ".intval($count)."</li>\n";
    if ($i >= $n) {
      break;
    }
    $i++;
  }
  $output .= "</ul>";
  return $output;
}

function display_recommendations(RecsEngine $recsEngine, User $user) {
  $recs = $recsEngine->recommend($user);

  $output = "<h1>Your Recs</h1>\n<ul class='item-grid recommendations'>\n";
  if (is_array($recs)) {
    $animeIDs = [];
    $recScores = [];
    foreach ($recs as $rec) {
      $recScores[intval($rec['id'])] = $rec['predicted_score'];
      $animeIDs[] = $rec['id'];
    }
    $animeGroup = new AnimeGroup($user->app, $animeIDs);
    foreach ($animeGroup->load('info') as $anime) {
      $output .= "<li>".$anime->link("show", "<h4>".escape_output($anime->title)."</h4>".$anime->imageTag, True, array('title' => $anime->description(True)))."<p><em>Predicted score: ".round($recScores[$anime->id], 1)."</em></p></li>\n";
    }
  }
  $output .= "</ul>";
  if (is_array($recs)) {
    $output .= tag_list($animeGroup);
  }
  return $output;
}

function display_tag_type_dropdown(DbConn $database, $select_id="tag[tag_type_id]", $selected=0) {
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

function display_userlevel_dropdown(DbConn $database, $select_id="userlevel", $selected=0) {
  $output = "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  for ($userlevel = 0; $userlevel <= 3; $userlevel++) {
    $output .= "  <option value='".intval($userlevel)."'".(($selected == intval($userlevel)) ? "selected='selected'" : "").">".escape_output(convert_userlevel_to_text($userlevel))."</option>\n";
  }
  $output .= "</select>\n";
  return $output;
}

function display_history_json(DbConn $database, User $user, array $fields = array(), array $machines=array()) {
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

function display_history_plot(DbConn $database, User $user, $form_id) {
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
?>