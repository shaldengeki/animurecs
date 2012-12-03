<?php
include_once("global/includes.php");

if (isset($_POST['anime']) && is_array($_POST['anime'])) {
  // check to ensure that the user has perms to create or update an anime.
  try {
    $targetAnime = new Anime($database, intval($_POST['anime']['id']));
  } catch (Exception $e) {
    // this non-zero animeID does not exist.
    redirect_to("/anime/", array('status' => 'This anime ID does not exist.', 'class' => 'error'));
  }
  if ($targetAnime->id === 0) {
    $authStatus = $targetAnime->allow($user, 'new');
  } else {
    $authStatus = $targetAnime->allow($user, 'edit');
  }
  if (!$authStatus) {
    redirect_to(($targetAnime->id === 0 ? $targetAnime->url("index") : $targetAnime->url("show")), array('status' => "You're not allowed to do this.", 'class' => 'error'));
  }
  $updateAnime = $targetAnime->create_or_update($_POST['anime']);
  if ($updateAnime) {
    redirect_to($targetAnime->url("show"), array('status' => "Successfully updated.", 'class' => 'success'));
  } else {
    redirect_to(($targetAnime->id === 0 ? $targetAnime->url("new") : $targetAnime->url("edit")), array('status' => "An error occurred while creating or updating this anime.", 'class' => 'error'));
  }
}

try {
  $targetAnime = new Anime($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  $targetAnime = new Anime($database, 0);
}

if (!$targetAnime->allow($user, $_REQUEST['action'])) {
  // TODO: make this kawaiier
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'feed':
      $maxTime = new DateTime('@'.intval($_REQUEST['maxTime']));
      $entries = $targetAnime->entries($maxTime, 50);
      echo $targetAnime->feed($entries, $user, 50, "");
      exit;
      break;
    case 'token_search':
      $blankAnime = new Anime($database, 0);
      $blankAlias = new Alias($database, 0, $blankAnime);
      $searchResults = $blankAlias->search($_REQUEST['term']);
      $animus = [];
      foreach ($searchResults as $anime) {
        $animus[] = array('id' => $anime->id, 'title' => $anime->title);
      }/*
      if (isset($_REQUEST['term'])) {
        $animus = $database->queryAssoc("SELECT `id`, `title` FROM `anime` WHERE MATCH(`title`, `description`) AGAINST(".$database->quoteSmart($_REQUEST['term']).") ORDER BY MATCH(`title`, `description`) AGAINST(".$database->quoteSmart($_REQUEST['term']).") DESC LIMIT 15");
      }*/
      echo json_encode($animus);
      exit;
      break;
    case 'new':
      $title = "Add an anime";
      $output = "<h1>Add an anime</h1>\n";
      $output .= $targetAnime->form($user);
      break;
    case 'edit':
      if ($targetAnime->id == 0) {
        $output = display_error("Error: Invalid anime", "The given anime doesn't exist.");
        break;
      }
      $title = "Editing ".escape_output($targetAnime->title);
      $output = "<h1>".escape_output($targetAnime->title)."</h1>\n";
      $output .= $targetAnime->form($user);
      break;
    case 'show':
      if ($targetAnime->id == 0) {
        $output = display_error("Error: Invalid anime", "The given anime doesn't exist.");
        break;
      }
      $title = escape_output($targetAnime->title);
      $output = $targetAnime->profile($recsEngine, $user);
      break;
    case 'delete':
      if ($targetAnime->id == 0) {
        $output = display_error("Error: Invalid anime", "The given anime doesn't exist.");
        break;
      }
      $animeTitle = $targetAnime->title;
      $deleteAnime = $targetAnime->delete();
      if ($deleteAnime) {
        redirect_to("/anime/", array('status' => 'Successfully deleted '.urlencode($animeTitle).'.', 'class' => 'success'));
      } else {
        redirect_to($targetAnime->url("show"), array('status' => 'An error occurred while deleting '.$animeTitle.'.', 'class' => 'error'));
      }
      break;
    default:
    case 'index':
      $title = "All Anime";
      $output = "<h1>All Anime</h1>\n".display_anime($database, $user);
      break;
  }
}
start_html($database, $user, "Animurecs", $title, $_REQUEST['status'], $_REQUEST['class']);
echo $output;
display_footer();
?>