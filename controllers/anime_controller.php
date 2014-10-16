<?php

class AnimeController extends Controller {
  public static $URL_BASE = "anime";
  public static $MODEL = "Anime";

  public function _beforeAction() {
    if ($this->_app->id !== "") {
      $this->_target = Anime::Get($this->_app, ['title' => str_replace("_", " ", rawurldecode($this->_app->id))]);
    } else {
      $this->_target = new Anime($this->_app, 0);
    }
  }
  public function _isAuthorized($action) {
    switch ($action) {
      /* cases where user must be staff. */
      case 'remove_tag':
      case 'approve':
      case 'edit':
      case 'delete':
        if ($this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;

      /* cases where this tag must be approved or user must be staff. */
      case 'feed':
      case 'show':
      case 'related':
      case 'stats':
      case 'tags':
        if ($this->_target->isApproved() || $this->_app->user->isStaff()) {
          return True;
        }
        return False;
        break;

      case 'index':
        if (isset($_POST['anime']) && is_array($_POST['anime'])) {
          if ($this->_app->user->isStaff()) {
            return True;
          }
          return False;
        }
        return True;
        break;

      /* public views. */
      case 'token_search':
        return True;
        break;
      default:
        return False;
        break;
    }   
  }
  public function delete() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "No such anime found.");
    }
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $cachedTitle = $this->_target->title;
    $deleteAnime = $this->_target->delete();
    if ($deleteAnime) {
      $this->_app->display_success(200, 'Successfully deleted '.$cachedTitle.'.');
    } else {
      $this->_app->display_error(500, 'An error occurred while deleting '.$cachedTitle.'.');
    }
  }
  public function edit() {
    if ($this->_target->id <= 0) {
      $this->_app->display_error(404, "The anime you've chosen to update doesn't exist.");
    }
    if (!isset($_POST['anime']) || !is_array($_POST['anime'])) {
      $this->_app->display_error(400, "Please provide parameters to update an anime.");
    }
    $updateAnime = $this->_target->create_or_update($_POST['anime']);
    if ($updateAnime) {
      // fetch the new ID.
      $newAnime = new Anime($this->_app, $updateAnime);
      $this->_app->display_success(200, "Successfully updated ".$newAnime->title.".", "success");
    } else {
      $this->_app->display_error(500, "An error occurred while updating ".$this->_target->title.".");
    }
  }
  public function feed() {
    $maxTime = isset($_REQUEST['maxTime']) ? new DateTime('@'.intval($_REQUEST['maxTime'])) : Null;
    $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime'])) : Null;
    foreach (array_sort_by_method($this->_target->entries($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc') as $entry) {
      $entries[] = $entry->serialize();
    }
    $this->_app->display_response(200, $entries);
  }
  public function index() {
    // handle creating.
    if (isset($_POST['anime']) && is_array($_POST['anime'])) {
      $updateAnime = $this->_target->create_or_update($_POST['anime']);
      if ($updateAnime) {
        // fetch the new ID.
        $newAnime = new Anime($this->_app, $updateAnime);
        $this->_app->display_success(200, "Successfully created ".$newAnime->title.".", "success");
      } else {
        $this->_app->display_error(500, "An error occurred while creating ".$this->_target->title.".");
      }
    }
    $perPage = 25;
    if (!isset($_REQUEST['search'])) {
      // top anime listing.
      $numPages = ceil(Anime::Count($this->_app)/$perPage);
      $this->_app->dbConn->table('( SELECT user_id, anime_id, MAX(time) AS time FROM anime_lists GROUP BY user_id, anime_id) p')
        ->fields('anime_lists.anime_id', 'AVG(score) AS avg', 'STDDEV(score) AS stddev', 'COUNT(*) AS count', '((((AVG(score)-1)/9) + ( POW(STDDEV(score), 2) / (2.0 * COUNT(*)) ) - STDDEV(score) * SQRT( ((AVG(score)-1)/9) * (1.0 - ((AVG(score)-1)/9)) / COUNT(*) + ( POW(STDDEV(score), 2) / ( 4.0 * POW(COUNT(*), 2) ) ) )) / (1.0 + (POW(STDDEV(score), 2) / COUNT(*))) * 9) + 1 AS wilson')
        ->join('anime_lists ON anime_lists.user_id=p.user_id && anime_lists.anime_id=p.anime_id && anime_lists.time=p.time');
      if (!$this->_app->user->isAdmin()) {
        $this->_app->dbConn->join('anime ON anime.id=anime_lists.anime_id')
          ->where(['anime.approved_on IS NOT NULL']);
      }
      $animeQuery = $this->_app->dbConn->where(['anime_lists.score != 0'])
          ->group('p.anime_id')
          ->having('COUNT(*) > 9')
          ->order('wilson DESC')
          ->offset(($this->_app->page-1)*$perPage)
          ->limit($perPage)
          ->query();
      $anime = [];
      $wilsons = [];
      while ($animeRow = $animeQuery->fetch()) {
        $anime[] = new Anime($this->_app, intval($animeRow['anime_id']));
        if (isset($animeRow['wilson'])) {
          $wilsons[$animeRow['anime_id']] = $animeRow['wilson'];
        }
      }
    } else {
      // user is searching for an anime.
      $blankAlias = new Alias($this->_app, 0, $this->_target);
      $searchResults = $blankAlias->search($_REQUEST['search']);
      $anime = array_slice($searchResults, (intval($this->_app->page)-1)*$perPage, intval($perPage));
      $aliases = [];
      foreach ($anime as $a) {
        $aliases[$a['anime']->id] = $a['alias'];
      }
      $anime = array_map(function($a) { return $a['anime']; }, $anime);
      $numPages = ceil(count($searchResults)/$perPage);
    }
    $group = new AnimeGroup($this->_app, $anime);
    $resultAnime = array_map(function ($a) use ($wilsons) {
      $serial = $a->serialize();
      if (isset($wilsons[$a->id])) {
        $serial['wilson_score'] = round(floatval($wilsons[$a->id]), 2);
      }
      return $serial;
    }, $group->load('info')->anime());
    $this->_app->display_response(200, [
      'page' => $this->_app->page,
      'pages' => $numPages,
      'anime' => $resultAnime,
    ]);    
  }
  public function related() {
    $perPage = 10;
    try {
      $anime = $this->_target->similar(($this->_app->page - 1) * $perPage, $perPage);
    } catch (CurlException $e) {
      $this->_app->display_error(503, "We couldn't fetch related anime for this show. Please try again later!");
    }
    $anime = array_map(function ($a) {
      return $a->serialize();
    }, $anime->anime());
    $this->_app->display_response(200, $anime);    
  }
  public function show() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "No such anime found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }
  public function tags() {
    // order the tags in this animeGroup's tags by tagType id
    $tagTypes = TagType::GetList($this->_app);
    $tagsByType = [];
    foreach ($tagTypes as $tagType) {
      $tagsByType[$tagType->name] = [];
    }

    foreach ($this->_target->tags as $tag) {
      $tagsByType[$tagTypes[$tag->type->id]->name][] = [
        'tag' => $tag->serialize(),
        'count' => $tag->numAnime()
      ];
    }
    $this->_app->display_response(200, $tagsByType);
  }
  public function token_search() {
    $blankAlias = new Alias($this->_app, 0, $this->_target);
    $searchResults = $blankAlias->search($_REQUEST['term']);
    $animus = [];
    foreach ($searchResults as $result) {
      $animus[] = ['id' => $result['anime']->id, 'title' => $result['alias']];
    }
    $this->_app->display_response(200, $animus);
  }
}
?>