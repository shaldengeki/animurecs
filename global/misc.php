<?php
function joinPaths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }

    $paths = array_map(create_function('$p', 'return trim($p, "'.addslashes(DIRECTORY_SEPARATOR).'");'), $paths);
    $paths = array_filter($paths);
    return join(DIRECTORY_SEPARATOR, $paths);
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

function convert_userlevel_to_text($userlevel) {
  switch(intval($userlevel)) {
    case 0:
      return 'Normal';
      break;
    case 1:
      return 'Administrator';
      break;
    default:
      return 'Unknown';
  }
}

function udate($format, $utimestamp = null) {
  if (is_null($utimestamp)) {
    $utimestamp = microtime(true);
  }
  $timestamp = floor($utimestamp);
  $milliseconds = round(($utimestamp - $timestamp) * 1000000);
  return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}
?>