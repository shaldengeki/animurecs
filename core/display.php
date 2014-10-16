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
  if (mb_strlen($text) <= $maxLen) {
    return $text;
  } else {
    return mb_substr($text, 0, $maxLen-3)."...";
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

function escape_output($input) {
  if ($input === '' || $input === 'NULL') {
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

function js_redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : '/';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = Config::ROOT_URL."/".$location;
  if ($status != "") {
    if (mb_strpos($location, "?") === FALSE) {
      $redirect .= "?status=".rawurlencode($status)."&class=".rawurlencode($class);
    } else {
      $redirect .= "&status=".rawurlencode($status)."&class=".rawurlencode($class);
    }
  }
  echo "window.location.replace(\"".$redirect."\");";
  exit;
}

function paginate($baseLink, $currPage=1, $maxPages=Null, $ajaxTarget=Null) {
  // displays a pagination bar.
  //baseLink should be everything up to, say, &page=
  $pageIncrement = 10;
  $displayFirstPages = 10;
  if ($ajaxTarget) {
    $link = "<a class='ajaxLink' data-url='".$baseLink."[PAGE]' data-target='".$ajaxTarget."' href='".$baseLink."[PAGE]'>";
  } else {
    $link = "<a href='".$baseLink."[PAGE]'>";
  }

  $output = "<div class='center-horizontal'><ul class='pagination'>\n";
  $i = 1;
  if ($currPage > 1) {
    $output .= "    <li>".str_replace("[PAGE]", $currPage-1, $link)."«Previous</a></li>\n";
  }
  if ($maxPages !== Null) {
    while ($i <= $maxPages) {
      if ($i == $currPage) {
        $output .= "    <li class='active'><a href='#'>".$i."</a></li>";     
      } else {
        $output .= "    <li>".str_replace("[PAGE]", $i, $link).$i."</a></li>";
      }
      if ($i < $displayFirstPages || abs($currPage - $i) <= $pageIncrement ) {
        $i++;
      } elseif ($i >= $displayFirstPages && $maxPages <= $i + $pageIncrement) {
        $i++;
      } elseif ($i >= $displayFirstPages && $maxPages > $i + $pageIncrement) {
        $i += $pageIncrement;
      }
    }
  } else {
    while ($i < $currPage) {
        $output .= "    <li>".str_replace("[PAGE]", $i, $link).$i."</a></li>";
        $i++;
    }
    $output .= "<li class='active'><a href='#'>".$currPage."</a></li>";
  }
  if ($maxPages === Null || $currPage < $maxPages) {
    $output .= "    <li>".str_replace("[PAGE]", $currPage+1, $link)."Next»</a></li>\n";
  }
  $output .= "</ul></div>\n";
  return $output;
}

function statusArray() {
  return [
      0 => "Remove",
      1 => "Currently Watching",
      2 => "Completed",
      3 => "On Hold",
      4 => "Dropped",
      6 => "Plan to Watch"
  ];
}

function display_status_dropdown($select_id="anime_lists[status]", $class="", $selected=False) {
  $statuses = statusArray();
  $output = "<select class='".escape_output($class)."' id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  foreach ($statuses as $id => $text) {
    $output .= "<option value='".intval($id)."'".(($selected == intval($id)) ? "selected='selected'" : "").">".escape_output($text)."</option>\n";
  }
  $output .= "</select>\n";
  return $output;
}

function display_month_year_dropdown($select_id="", $select_name_prefix="form_entry", $selected=False) {
  if ($selected === false) {
    $selected = [0 => intval(date('n')), 1 => intval(date('Y'))];
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

function display_history_json(DatabaseConnection $database, User $user, array $fields = array(), array $machines=array()) {
  header('Content-type: application/json');
  $return_array = [];
  
  if (!$user->loggedIn()) {
    $return_array['error'] = "You must be logged in to view history data.";
  } elseif (!is_array($fields) || !is_array($machines)) {
    $return_array['error'] = "Please provide a valid list of fields and machines.";  
  } else {
    foreach ($fields as $field) {
      foreach ($machines as $machine) {
        $line_array = [];
        $values = $database->query("SELECT `form_field_id`, `form_entries`.`machine_id`, `form_entries`.`qa_month`, `form_entries`.`qa_year`, `value` FROM `form_values`
                                    LEFT OUTER JOIN `form_entries` ON `form_entry_id` = `form_entries`.`id`
                                    WHERE `form_field_id` = ".intval($field)." && `machine_id` = ".intval($machine)."
                                    ORDER BY `qa_year` ASC, `qa_month` ASC");
        while ($value = mysqli_fetch_assoc($values)) {
          $line_array[] = ['x' => intval($value['qa_month'])."/".intval($value['qa_year']),
                                  'y' => doubleval($value['value']),
                                  'machine' => intval($value['machine_id']),
                                  'field' => intval($value['form_field_id'])];
        }
        if (count($line_array) > 1) {
          $return_array[] = $line_array;
        }
      }
    }
  }
  echo json_encode($return_array);
}

function display_history_plot(DatabaseConnection $database, User $user, $form_id) {
  //displays plot for a particular form.
  $formObject = $database->firstRow("SELECT * FROM `forms` WHERE `id` = ".intval($form_id)." LIMIT 1");
  if (!$formObject) {
    echo "The form ID you provided was invalid. Please try again.\n";
  } else {
    $formFields = $database->query("SELECT `id`, `name` FROM `form_fields`
                                        WHERE `form_id` = ".intval($form_id)."
                                        ORDER BY `name` ASC");
    $machines = $database->query("SELECT `id`, `name` FROM `machines`
                                        WHERE `machine_type_id` = ".intval($formObject['machine_type_id'])."
                                        ORDER BY `name` ASC");
    echo "<div id='vis'></div>
  <form action='#'>
    <input type='hidden' id='form_id' name='form_id' value='".intval($form_id)."' />
    <div class='row'>
      <div class='col-md-4'>
        <div class='row'><h3 class='col-md-12' style='text-align:center;'>Machines</h3></div>
        <div class='row'>
          <select multiple='multiple' id='machines' class='col-md-12' size='10' name='machines[]'>\n";
    while ($machine = mysqli_fetch_assoc($machines)) {
      echo "           <option value='".intval($machine['id'])."'>".escape_output($machine['name'])."</option>\n";
    }
    echo "         </select>
        </div>
      </div>
      <div class='col-md-4'>
        <div class='row'><h3 class='col-md-12' style='text-align:center;'>Fields</h3></div>
        <div class='row'>
          <select multiple='multiple' id='form_fields' class='col-md-12' size='10' name='form_fields[]'>\n";
    while ($field = mysqli_fetch_assoc($formFields)) {
      echo "            <option value='".intval($field['id'])."'>".escape_output($field['name'])."</option>\n";
    }
    echo "          </select>
        </div>
      </div>
      <div class='col-md-4'>
        <div class='row'><h3 class='col-md-12' style='text-align:center;'>Time Range</h3></div>
        <div class='row'>
          <div class='col-md-12' style='text-align:center;'>(Coming soon)</div>
        </div>
      </div>
    </div>
    <div class='row'>
      <div class='col-md-12' style='text-align:center;'>As a reminder, you can highlight multiple fields by either clicking and dragging, or holding down Control and clicking on the fields you want.</div>
    </div>
    <div class='form-actions'>
      <a class='btn btn-xlarge btn-primary' href='#' onClick='drawLargeD3Plot();'>Redraw Plot</a>
    </div>
  </form>\n";
  }
}
?>