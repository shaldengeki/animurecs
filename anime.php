<?php
include_once("global/includes.php");

if (isset($_POST['anime']) && is_array($_POST['anime'])) {
  // check to ensure that the user has perms to create or update an anime.
  try {
    $targetAnime = new Anime($database, intval($_POST['anime']['id']));
  } catch (Exception $e) {
    // this non-zero animeID does not exist.
    redirect_to(array('location' => 'anime.php', 'status' => 'This anime ID does not exist.', 'class' => 'error'));
  }
  if ($targetAnime->id === 0) {
    $authStatus = $targetAnime->allow($user, 'new');
  } else {
    $authStatus = $targetAnime->allow($user, 'edit');
  }
  if (!$authStatus) {
    redirect_to(array('location' => 'anime.php'.($targetAnime->id === 0 ? "" : "?action=show&id=".intval($targetAnime->id)), 'status' => "You're not allowed to do this.", 'class' => 'error'));
  }
  $updateAnime = $targetAnime->create_or_update($_POST['anime'], $user);
  if ($updateAnime) {
    redirect_to(array('location' => 'anime.php?action=show&id='.intval($targetAnime->id), 'status' => "Successfully updated.", 'class' => 'success'));
  } else {
    redirect_to(array('location' => 'anime.php'.($targetAnime->id === 0 ? "?action=new" : "?action=edit&id=".intval($targetAnime->id)), 'status' => "An error occurred while creating or updating this anime.", 'class' => 'error'));
  }
}

try {
  $targetAnime = new Anime($database, intval($_REQUEST['id']));
} catch (Exception $e) {
  $targetAnime = new Anime($database, 0);
}

if (!$targetAnime->allow($user, $_REQUEST['action'])) {
  $title = "Error: Insufficient privileges";
  $output = display_error("Error: Insufficient privileges", "You're not allowed to do this.");
} else {
  switch($_REQUEST['action']) {
    case 'token_search':
      $animus = [];
      if (isset($_REQUEST['term'])) {
        $animus = $database->queryAssoc("SELECT `id`, `title` FROM `anime` WHERE MATCH(`title`, `description`) AGAINST(".$database->quoteSmart($_REQUEST['term']).") ORDER BY MATCH(`title`, `description`) AGAINST(".$database->quoteSmart($_REQUEST['term']).") DESC LIMIT 15");
      }
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
      $output = "<h1>".escape_output($targetAnime->title).($targetAnime->allow($user, "edit") ? " <small>(".$targetAnime->link("edit", "edit").")</small>" : "")."</h1>\n".$targetAnime->profile();
      break;
    case 'delete':
      if ($targetAnime->id == 0) {
        $output = display_error("Error: Invalid anime", "The given anime doesn't exist.");
        break;
      }
      $deleteAnime = $targetAnime->delete();
      if ($deleteAnime === True) {
        redirect_to(array('location' => 'anime.php?action=index', 'status' => 'Successfully deleted '.urlencode($targetAnime->title).'.', 'class' => 'success'));
      } else {
        redirect_to(array('location' => 'anime.php?action=show&id='.intval($targetAnime->id), 'status' => 'An error occurred while deleting this anime.', 'class' => 'error'));
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