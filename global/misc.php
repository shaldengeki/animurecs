<?php
function dot($a, $b) {
  $sum = 0;
  foreach ($a as $key=>$val) {
    if (isset($b[$key])) {
      $sum += floatval($val) * floatval($b[$key]);
    }
  }
  return $sum;
}

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

function buildPropertyFilter($property, $value) {
  // returns a function that filters a list of objects on an property
  return function($a) use ($property, $value) {
    if (!property_exists($a, $property)) {
      return False;
    } elseif ($a->$property == $value) {
      return True;
    } else {
      return False;
    }
  };
}

function array_filter_by_property($a, $property, $value) {
  // filters a list of objects on a given property for a given value.
  return array_filter($a, buildPropertyFilter($property, $value));
}

function buildKeyPropertyFilter($key, $property, $value) {
  // returns a function that filters a list of objects on an property
  return function($a) use ($key, $property, $value) {
    if (!isset($a[$key]) || !property_exists($a[$key], $property)) {
      return False;
    } elseif ($a[$key]->$property == $value) {
      return True;
    } else {
      return False;
    }
  };
}

function array_filter_by_key_property($a, $key, $property, $value) {
  /* filters a list of associative arrays containing objects for a given key on a given property for a given value.
  ex. array(
    array(
      'user' => User (
        id: 2
      )
    )
    array(
      'user' => User (
        id: 3
      )
    )
    array(
      'user' => User (
        id: 4
      )
    )
  )
  we'd use array_filter_by_key_property($array, 'user', 'id', 3) to get the second array.
  */
  return array_filter($a, buildKeyPropertyFilter($key, $property, $value));
}

function buildKeyFilter($key, $value) {
  // returns a function that filters a list of arrays on a key.
  return function($a) use ($key, $value) {
    if (!isset($a[$key])) {
      return False;
    } elseif ($a[$key] == $value) {
      return True;
    } else {
      return False;
    }
  };
}

function array_filter_by_key($a, $key, $value) {
  // filters a list of associative arrays on a given key for a given value.
  return array_filter($a, buildKeyFilter($key, $value));
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