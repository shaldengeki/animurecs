<?php
function joinPaths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }

    $trimmedPaths = array_map(create_function('$p', 'return trim($p, "'.addslashes(DIRECTORY_SEPARATOR).'");'), $paths);
    if (count($trimmedPaths) > 0) {
      $trimmedPaths[0] = rtrim($paths[0], addslashes(DIRECTORY_SEPARATOR));
    }
    $trimmedPaths = array_filter($trimmedPaths);
    return join(DIRECTORY_SEPARATOR, $trimmedPaths);
}

function getNormalizedFILES() {
    $newfiles = array();
    foreach($_FILES as $fieldname => $fieldvalue)
        foreach($fieldvalue as $paramname => $paramvalue)
            foreach((array)$paramvalue as $index => $value)
                $newfiles[$fieldname][$index][$paramname] = $value;
    return $newfiles;
}

function get_numeric($val) { 
  if (is_numeric($val)) { 
    return $val + 0; 
  } else {
    return false;
  }
}

function convert_usermask_to_text($usermask) {
  $usermask = intval($usermask);
  $roles = [];
  if ($usermask == 0) {
    return "Guest";
  }
  if ($usermask & 4) {
    $roles[] = "Administrator";
  }
  if ($usermask & 2) {
    $roles[] = "Moderator";
  }
  if ($usermask & 1) {
    $roles[] = "User";
  }
  if (count($roles) == 0) {
    return "Unknown";
  }
  return implode(", ", $roles);
}

function udate($format, $utimestamp = null) {
  if (is_null($utimestamp)) {
    $utimestamp = microtime(true);
  }
  $timestamp = floor($utimestamp);
  $milliseconds = round(($utimestamp - $timestamp) * 1000000);
  return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}

function buildKeySorter($key, $order) {
  // returns a function that sorts two input arrays on a key in a given order.
  // order=1 corresponds to ascending, order=-1 corresponds to descending.
  return function($a, $b) use ($key, $order) {
    if (!isset($a[$key])) {
      if (!isset($b[$key])) {
        return 0;
      } else {
        return -1*$order;
      }
    } else {
      if (!isset($b[$key])) {
        return 1*$order;
      } else {
        return (($a[$key] < $b[$key]) ? -1 : 1)*$order;
      }
    }
  };
}

function array_sort_by_key($a, $key, $order="desc") {
  // sorts a list of associative arrays by a given key in a given order.
  switch($order) {
    case 'asc':
      $orderVal = 1;
      break;
    default:
    case 'desc':
      $orderVal = -1;
      break;
  }
  uasort($a, buildKeySorter($key, $orderVal));
  return $a;
}
?>