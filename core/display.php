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
?>