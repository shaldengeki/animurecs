<?php

class UserController extends Controller {
  public static $URL_BASE = "users";
  public static $MODEL = "User";

  public function _beforeAction() {
    if ($this->_app->id !== "") {
      $this->_target = User::Get($this->_app, ['username' => rawurldecode($this->_app->id)]);
    } else {
      $this->_target = new User($this->_app, 0);
    }
  }
  public function _isAuthorized($action) {
    switch ($action) {
      /* cases where the targeted user must be the currently logged-in user */
      case 'log_out':
      case 'register_conversion':
        if ($this->_target->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* cases where we want only user+staff capable, keeping the first user public */
      case 'friend_recommendations':
      case 'recommendations':
      case 'groupwatches':
        if ($this->_target->id === 1 || ($this->_app->user->id == $this->_target->id || ( ($this->_app->user->isStaff()) && $this->_app->user->usermask > $this->_target->usermask) )) {
          return True;
        }
        return False;
        break;

      /* cases where we only want this user + staff capable of POSTing. */
      case 'edit':
        if (($this->_app->user->id === $this->_target->id && $this->_app->user->id === $_POST['users']['id']) || ( ($this->_app->user->isStaff()) && $this->_app->user->usermask > $this->_target->usermask) && ($this->_app->user->usermask <= array_sum($_POST['users']['usermask']))) {
          return True;
        }
        return False;
        break;

      /* cases where we want only this user + staff capable */
      case 'anime':
      case 'global_feed':
      case 'mal_import':
        if ($this->_app->user->id === $this->_target->id || ( ($this->_app->user->isStaff()) && $this->_app->user->usermask > $this->_target->usermask) ) {
          return True;
        }
        return False;
        break;

      /* cases where we only want non-logged-in users */
      case 'log_in':
      case 'activate':
        if (!$this->_app->user->loggedIn()) {
          return True;
        }
        return False;
        break;

      /* cases where we only want admins, and only target non-admins */
      case 'delete':
        if ($this->_app->user->isAdmin() && !$this->_target->isAdmin()) {
          return True;
        }
        return False;
        break;

      /* cases where we only want admins */
      case 'switch_user':
        if ($this->_app->user->isAdmin()) {
          return True;
        }
        return False;
        break;

      /* cases where we want only logged-in users who are not this user */
      case 'request_friend':
      case 'confirm_friend':
      case 'ignore_friend':
      case 'comment':
        if ($this->_target->id !== 0 && $this->_app->user->loggedIn() && $this->_app->user->id != $this->_target->id) {
          return True;
        }
        return False;
        break;

      /* cases where we want only non-logged-in users if they're POSTing, otherwise public. */
      case 'index':
        if (isset($_POST['users']) && is_array($_POST['users'])) {
          if (!$this->_app->user->loggedIn()) {
            return True;
          }
          return False;
        }
        return True;
        break;

      /* public views */
      case 'switch_back':
      case 'show':
      case 'feed':
      case 'anime_list':
      case 'stats':
      case 'friends':
      case 'achievements':
        return True;
        break;

      /* everything else is blacklisted by default */
      default:
        return False;
        break;

    }
  }

  public function achievements() {
    $achieves = array_map(function ($a) {
      return $a->serialize();
    }, array_values(array_filter($this->_app->achievements, function($a) {
        return $a->alreadyAwarded($this->_target);
      }))
    );
    $this->_app->display_response(200, $achieves);
  }
  public function activate() {
    if (!$this->_target->activationCode || !isset($_REQUEST['code']) || $_REQUEST['code'] != $this->_target->activationCode) {
      $this->_app->display_error(403, 'The activation code you provided was incorrect. Please check your email and try again.');
    } else {
      // $this->_app->dbConn->table(static::$TABLE)->set(['activation_code' => Null])->where(['id' => $this->_target->id])->update();
      $this->setCurrentSession();

      //update last IP address and last active.
      $currTime = new DateTime("now", $this->_app->serverTmeZone);
      $updateUser = [
        'activation_code' => Null,
        'last_ip' => $_SERVER['REMOTE_ADDR'], 
        'last_active' => $currTime->format("Y-m-d H:i:s")
      ];
      $this->_target->create_or_update($updateUser);

      $this->_app->display_response(200, $this->_target->serialize());
    }
  }
  public function anime() {
    if (!isset($_REQUEST['anime_id']) || !is_numeric($_REQUEST['anime_id'])) {
      $this->_app->display_error(400, "Please specify a valid anime ID.");
    }
    if (!isset($this->_target->animeList()->uniqueList[intval($_REQUEST['anime_id'])])) {
      $this->_app->display_response(200, []);
    }
    $latestEntry = $this->_target->animeList()->uniqueList[intval($_REQUEST['anime_id'])];
    $latestEntry['anime_id'] = $latestEntry['anime']->id;
    $latestEntry['episode_count'] = $latestEntry['anime']->episodeCount;
    unset($latestEntry['anime']);
    $this->_app->display_response(200, $latestEntry);    
  }
  public function anime_list() {
    $animeList = [
      'currently_watching' => [],
      'completed' => [],
      'on_hold' => [],
      'dropped' => [],
      'plan_to_watch' => []
    ];
    /* TODO: replace with dynamic names */
    $startedAnimeQuery = $this->_app->dbConn->query("SELECT al.anime_id, al.time FROM anime_lists al
      LEFT OUTER JOIN anime_lists al2 ON al.user_id = al2.user_id
        AND al.anime_id = al2.anime_id
        AND al.time < al2.time
        AND al2.status != 1
      WHERE al.user_id = ".intval($this->_target->id)."
      AND al.status = 1
      AND al2.time IS NULL
      ORDER BY anime_id ASC;");
    $startedAnime = [];
    while ($row = $startedAnimeQuery->fetch()) {
      $startedAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->_app->serverTimeZone);
    }

    $completedAnimeQuery = $this->_app->dbConn->query("SELECT al.anime_id, al.time FROM anime_lists al
      LEFT OUTER JOIN anime_lists al2 ON al.user_id = al2.user_id
        AND al.anime_id = al2.anime_id
        AND al.time < al2.time
        AND al2.status != 2
      WHERE al.user_id = ".intval($this->_target->id)."
      AND al.status = 2
      AND al2.time IS NULL
      ORDER BY anime_id ASC;");
    $completedAnime = [];
    while ($row = $completedAnimeQuery->fetch()) {
      $completedAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->_app->serverTimeZone);
    }
    $heldAnimeQuery = $this->_app->dbConn->query("SELECT al.anime_id, al.time FROM anime_lists al
      LEFT OUTER JOIN anime_lists al2 ON al.user_id = al2.user_id
        AND al.anime_id = al2.anime_id
        AND al.time < al2.time
        AND al2.status != 3
      WHERE al.user_id = ".intval($this->_target->id)."
      AND al.status = 3
      AND al2.time IS NULL
      ORDER BY anime_id ASC;");
    $heldAnime = [];
    while ($row = $heldAnimeQuery->fetch()) {
      $heldAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->_app->serverTimeZone);
    }
    $droppedAnimeQuery = $this->_app->dbConn->query("SELECT al.anime_id, al.time FROM anime_lists al
      LEFT OUTER JOIN anime_lists al2 ON al.user_id = al2.user_id
        AND al.anime_id = al2.anime_id
        AND al.time < al2.time
        AND al2.status != 4
      WHERE al.user_id = ".intval($this->_target->id)."
      AND al.status = 4
      AND al2.time IS NULL
      ORDER BY anime_id ASC;");
    $droppedAnime = [];
    while ($row = $droppedAnimeQuery->fetch()) {
      $droppedAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->_app->serverTimeZone);
    }
    $planAnimeQuery = $this->_app->dbConn->query("SELECT al.anime_id, al.time FROM anime_lists al
      LEFT OUTER JOIN anime_lists al2 ON al.user_id = al2.user_id
        AND al.anime_id = al2.anime_id
        AND al.time < al2.time
        AND al2.status != 6
      WHERE al.user_id = ".intval($this->_target->id)."
      AND al.status = 6
      AND al2.time IS NULL
      ORDER BY anime_id ASC;");
    $plannedAnime = [];
    while ($row = $planAnimeQuery->fetch()) {
      $plannedAnime[intval($row['anime_id'])] = new \DateTime($row['time'], $this->_app->serverTimeZone);
    }

    // now build up each section with serialized anime.
    foreach ($this->_target->animeList()->uniqueList() as $animeID => $entry) {
      switch (intval($entry['status'])) {
        case 1:
          $animeList['currently_watching'][] = [
            'anime' => Anime::FindById($this->_app, $animeID)->serialize(),
            'last_time' => $startedAnime[$animeID]
          ];
          break;
        case 2:
          $animeList['completed'][] = [
            'anime' => Anime::FindById($this->_app, $animeID)->serialize(),
            'last_time' => $completedAnime[$animeID]
          ];
          break;
        case 3:
          $animeList['on_hold'][] = [
            'anime' => Anime::FindById($this->_app, $animeID)->serialize(),
            'last_time' => $heldAnime[$animeID]
          ];
          break;
        case 4:
          $animeList['dropped'][] = [
            'anime' => Anime::FindById($this->_app, $animeID)->serialize(),
            'last_time' => $droppedAnime[$animeID]
          ];
          break;
        case 6:
          $animeList['plan_to_watch'][] = [
            'anime' => Anime::FindById($this->_app, $animeID)->serialize(),
            'last_time' => $plannedAnime[$animeID]
          ];
          break;
      }
    }
    $this->_app->display_response(200, $animeList);    
  }
  public function confirm_friend() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $confirmFriend = $this->_app->user->confirmFriend($this->_target);
    if ($confirmFriend) {
      $this->_app->display_response(200, ['id' => $this->_target->id, 'username' => $this->_target->username]);
    } else {
      $this->_app->display_error(500, "An error occurred while confirming this friend. Please try again.");
    }
  }
  public function delete() {
    if ($this->_target->id == 0) {
      $this->_app->display_error(404, "No such user found.");
    } elseif (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $username = $this->_target->username;
    $deleteUser = $this->_target->delete();
    if ($deleteUser) {
      $this->_app->display_success(200, 'Successfully deleted '.$username.'.');
    } else {
      $this->_app->display_error(500, 'An error occurred while deleting '.$username.'.');
    }
  }
  public function edit() {
    if (isset($_POST['users']) && is_array($_POST['users'])) {
      $updateUser = $this->_target->create_or_update($_POST['users']);
      if ($updateUser) {
        $this->_app->display_response(200, $this->_target->serialize());
      } else {
        $this->_app->display_error(500, "An error occurred while updating your profile.");
      }
    }
  }
  public function feed() {
    $entries = [];
    if (isset($_REQUEST['maxTime']) && is_numeric($_REQUEST['maxTime'])) {
      $maxTime = '@'.intval($_REQUEST['maxTime']);
    } else {
      $maxTime = "now";
    }
    $maxTime = new DateTime($maxTime, $this->_app->serverTimeZone);
    $minTime = isset($_REQUEST['minTime']) ? new DateTime('@'.intval($_REQUEST['minTime']) , $this->_app->serverTimeZone) : Null;
    foreach (array_sort_by_method($this->_target->profileFeed($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc') as $entry) {
      $entries[] = $entry->serialize();
    }
    $this->_app->display_response(200, $entries);    
  }
  public function friend_recommendations() {
    /* TODO */
    $this->_app->display_response(200, []);
  }
  public function friends() {
    $friends = [];
    foreach ($this->_target->friends() as $friend) {
      $friends[] = [
        'friend' => $friend['user']->serialize(),
        'compatibility' => round($this->_target->animeList()->compatibility($friend['user']->animeList()), 2)
      ];
    }
    $this->_app->display_response(200, $friends);
  }
  public function global_feed() {
    if (isset($_REQUEST['maxTime']) && is_numeric($_REQUEST['maxTime'])) {
      $maxTime = '@'.intval($_REQUEST['maxTime']);
    } else {
      $maxTime = "now";
    }
    $maxTime = new DateTime($maxTime, $this->_app->serverTimeZone);
    if (isset($_REQUEST['minTime']) && is_numeric($_REQUEST['minTime'])) {
      $minTime = new DateTime('@'.intval($_REQUEST['minTime']), $this->_app->serverTimeZone);
    } else {
      $minTime = Null;
    }
    $entries = array_map(function ($e) {
      return $e->serialize();
    }, array_sort_by_method($this->_target->globalFeed($minTime, $maxTime, 50)->load('comments')->entries(), 'time', [], 'desc'));

    $this->_app->display_response(200, $entries);    
  }
  public function groupwatches() {
    // get list of anime that this user and at least one other friend have on their list in the same category.
    $groupwatchCategories = [1 => "currently_watching", 6 => "plan_to_watch"];
    $groupwatches = [];
    $nonZeroGroupwatches = False;
    $anime = [];
    foreach ($groupwatchCategories as $category => $text) {
      $catGroupwatches = [];
      $ourSeries = array_keys($this->_target->animeList()->listSection($category));
      foreach ($this->_target->friends() as $friend) {
        $friendSeries = array_keys($friend['user']->animeList()->listSection($category));
        $intersect = array_intersect($ourSeries, $friendSeries);
        if ($intersect) {
          foreach ($intersect as $animeID) {
            if (!isset($catGroupwatches[$animeID])) {
              $anime[$animeID] = new Anime($this->_app, $animeID);
              $catGroupwatches[$animeID] = ['anime' => $anime[$animeID], 'users' => [$friend['user']]];
            } else {
              $catGroupwatches[$animeID]['users'][] = $friend['user'];
            }
          }
        }
      }
      $nonZeroGroupwatches = $nonZeroGroupwatches || $catGroupwatches;
      foreach ($catGroupwatches as $animeID => $groupwatch) {
        usort($groupwatch['users'], function($a, $b) use ($animeID) {
          return ($a->animeList()->uniqueList()[$animeID]['episode'] < $b->animeList()->uniqueList()[$animeID]['episode']) ? 1 : -1;
        });
        $catGroupwatches[$animeID] = $groupwatch; 
      }
      $groupwatches[$groupwatchCategories[$category]] = $catGroupwatches;
    }
    if ($nonZeroGroupwatches) {
      // try to fetch predicted ratings for each anime.
      try {
        $predictedRatings = $this->_app->recsEngine->predict($this->_target, $anime, 0, count($anime));
      } catch (CurlException $e) {
        $this->_app->log_exception($e);
        $predictedRatings = [];
      }
    }
    foreach ($groupwatches as $category=>$groupwatchList) {
      // sort each category's anime by the user's predicted rating (if it exists).
      usort($groupwatchList, function($a, $b) use ($predictedRatings) {
        if (!isset($predictedRatings[$a['anime']->id])) {
          if (!isset($predictedRatings[$b['anime']->id])) {
            return 0;
          } else {
            return 1;
          }
        } elseif (!isset($predictedRatings[$b['anime']->id])) {
          return -1;
        } else {
          return ($predictedRatings[$a['anime']->id] < $predictedRatings[$b['anime']->id]) ? 1 : -1;
        }
      });
      // go through and serialize all the anime and users for output.
      foreach ($groupwatchList as $animeID=>$info) {
        if (isset($predictedRatings[$animeID])) {
          $groupwatchList[$animeID]['predicted_rating'] = $predictedRatings[$animeID];
        }
        $groupwatchList[$animeID]['anime'] = $groupwatchList[$animeID]['anime']->serialize();
        foreach ($groupwatchList[$animeID]['users'] as $i=>$val) {
          $groupwatchList[$animeID]['users'][$i] = $groupwatchList[$animeID]['users'][$i]->serialize();
        }
      }
      $groupwatches[$category] = $groupwatchList;
    }
    $this->_app->display_response(200, $groupwatches);    
  }
  public function ignore_friend() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    $ignoreFriend = $this->_app->user->ignoreFriend($this->_target);
    if ($ignoreFriend) {
      $this->_app->display_response(200, ['id' => $this->_target->id, 'username' => $this->_target->username]);
    } else {
      $this->_app->display_error(500, "An error occurred while ignoring this friend request. Please try again.");
    }
  }
  public function index() {
    if (isset($_POST['users']) && is_array($_POST['users'])) {
      $updateUser = $this->_target->create_or_update($_POST['users']);
      if ($updateUser) {
        $this->_app->display_response(200, $this->_target->serialize());
      } else {
        $this->_app->display_error(500, "An error occurred while signing up. Please try again!");
      }
    }
    $userGroup = new UserGroup($this->_app, array_keys(User::GetList($this->_app)));
    $users = [];
    foreach ($userGroup->load('info') as $thisUser) {
      $users[] = $thisUser->serialize();
    }
    $this->_app->display_response(200, $users);

  }
  public function log_in() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
      $this->_app->display_error(400, "Please provide a username and password to log in.");          
    }
    $username = rawurldecode($_POST['username']);
    $password = rawurldecode($_POST['password']);

    if ($this->_app->user->logIn($username, $password)) {
      $this->_app->display_response(200, $this->_target->serialize());
    } else {
      $this->_app->display_error(403, "The username/password combination you specified is not correct. Please try again.");
    }    
  }
  public function log_out() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    if ($this->_app->user->logOut()) {
      $this->_app->display_response(200, ['id' => $this->_target->id, 'username' => $this->_target->username]);
    } else {
      $this->_app->display_error(500, "An error occurred while logging you out. Please try again.");
    }
  }
  public function mal_import() {
    // import a MAL list for this user.
    if (!isset($_POST['users']) || !is_array($_POST['users']) || !isset($_POST['users']['mal_username'])) {
      $this->_app->display_error(400, 'Please enter a MAL username.');          
    }
    $update = $this->_target->create_or_update([
      'mal_username' => $_POST['users']['mal_username'],
      'last_import_failed' => 0
    ]);
    if (!$update) {
      $this->_app->display_error(400, "The MAL username you provided is invalid. Please try again.");          
    }
    $this->_app->display_success(200, "Your MAL profile has been queued for update. Should take no longer than an hour or two!");    
  }
  public function recommendations() {
    $animePerPage = 20;
    $recommendations = [];
    try {
      $recs = $this->_app->recsEngine->recommend($this->_target, $animePerPage * ($this->_app->page - 1), $animePerPage);
    } catch (CurlException $e) {
      $this->_app->log_exception($e);
      $recs = [];
    }
    $animeGroup = new AnimeGroup($this->_app, array_map(function($a) {
      return $a['id'];
    }, $recs));
    // we need to be able to access this by anime ID.
    $recs_by_id = [];
    foreach ($recs as $rec) {
      $recs_by_id[$rec['id']] = $rec['predicted_score'];
    }
    foreach ($animeGroup as $anime) {
      $recommendations[] = [
        'anime' => $anime->serialize(),
        'predictedScore' => $recs_by_id[$anime->id]
      ];
    }
    $this->_app->display_response(200, $recommendations);
  }
  public function request_friend() {
    if (!$this->_app->checkCSRF()) {
      $this->_app->display_error(403, "The CSRF token you presented wasn't right. Please try again.");
    }
    if ($this->_target->id === $this->_app->user->id) {
      $this->_app->display_error(409, "You can't befriend yourself, silly!");
    }
    if (!isset($_POST['friend_request'])) {
      $_POST['friend_request'] = [];
    }
    $requestFriend = $this->_app->user->requestFriend($this->_target, $_POST['friend_request']);
    if ($requestFriend) {
      $this->_app->display_response(200, ['id' => $this->_target->id, 'username' => $this->_target->username]);
    } else {
      $this->_app->display_error(500, "An error occurred while requesting this friend. Please try again.");
    }
  }
  public function show() {
    if ($this->_target->id === 0) {
      $this->_app->display_error(404, "No such user found.");
    }
    $this->_app->display_response(200, $this->_target->serialize());
  }
  public function stats() {
    // first, get time range of this user's anime ratings.
    $interval = $this->_app->dbConn->table(AnimeList::$TABLE)
      ->fields("UNIX_TIMESTAMP(MIN(time)) AS start", "UNIX_TIMESTAMP(MAX(time)) AS end")
      ->where(['user_id' => $this->_target->id])
      ->firstRow();

    // get list of user's favourite tags, ordered by regularized average
    // regularize by mean tag mean, weighted by mean number of ratings per tag.
    $tags = [];

    foreach ($this->_target->animeList()->uniqueList() as $entry) {
      if (round(floatval($entry['score']), 2) != 0) {
        foreach ($entry['anime']->tags as $tag) {
          if (!isset($tags[$tag->type->id])) {
            $tags[$tag->type->id] = [];
          }
          $rating = round(floatval($entry['score']), 2);
          if (isset($tags[$tag->type->id][$tag->id])) {
            $tags[$tag->type->id][$tag->id]['ratings'][] = $rating;
          } else {
            $tags[$tag->type->id][$tag->id] = ['tag' => $tag, 'ratings' => [$rating]];
          }
        }
      }
    }

    $tagMeans = [];
    foreach ($tags as $typeID => $typeTags) {
      foreach ($typeTags as $tagKey=>$tag) {
        $sum = 0;
        $length = 0;
        foreach ($tag['ratings'] as $rating) {
          $sum += $rating;
          $length++;
        }
        if ($length < 3) {
          // we must be able to calculate variances.
          unset($tags[$typeID][$tagKey]);
          continue;
        }
        $mean = floatval($sum) / $length;
        $tagMeans[] = $mean;
        $tags[$typeID][$tag['tag']->id]['rating_sum'] = $sum;
        $tags[$typeID][$tag['tag']->id]['rating_count'] = $length;
        $tags[$typeID][$tag['tag']->id]['rating_mean'] = $mean;
      }
    }
    $tagMeansStats = array_statistics($tagMeans);

    $sortDescFunction = function($a, $b) {
      if ($a['rating'] === $b['rating']) {
        return 0;
      }
      return ($a['rating'] < $b['rating']) ? 1 : -1;
    };
    $sortAscFunction = function($a, $b) {
      if ($a['rating'] === $b['rating']) {
        return 0;
      }
      return ($a['rating'] < $b['rating']) ? -1 : 1;
    };

    $favoriteTags = [];
    foreach ($tags as $typeID => $typeTags) {
      $flatTags = [];
      $numTags = 0;
      foreach ($typeTags as $tag) {
        $tagRatingVariance = array_variance($tag['ratings']);
        $priorWeight = $tagRatingVariance / $tagMeansStats['variance'];
        $flatTags[] = ['tag' => $tag['tag']->serialize(), 'count' => $tag['rating_count'], 'rating' => ($tagMeansStats['mean'] * $priorWeight + $tag['rating_sum']) / ($priorWeight + $tag['rating_count'])];
        $numTags++;
      }
      usort($flatTags, $sortDescFunction);
      $favoriteTags[$typeID] = [
        'liked' => array_slice($flatTags, 0, $numTags >= 10 ? 10 : floor($numTags/2), True),
        'hated' => array_slice($flatTags, $numTags >= 10 ? -10 : -1 * floor($numTags/2), Null, True)
      ];
      usort($favoriteTags[$typeID]['hated'], $sortAscFunction);
    }
    $this->_app->display_response(200, [
      'start' => $interval['start'],
      'end' => $interval['end'],
      'favoriteTags' => $favoriteTags
    ]);    
  }
  public function switch_back() {
    $switchUser = $this->_app->user->switchUser($_SESSION['switched_user'], False);
    if ($switchUser) {
      $this->_app->display_response(200, [
        'id' => $this->_app->user->id,
        'username' => $this->_app->user->username,
        'from_id' => $this->_target->id,
        'from_username' => $this->_target->username
      ]);
    } else {
      $this->_app->display_error(500, "An error occurred while switching you back. Please try again.");
    }
  }
  public function switch_user() {
    if (isset($_POST['switch_username'])) {
      try {
        $desiredUser = User::Get($this->_app, ['username' => $_POST['switch_username']]);
      } catch (NoDatabaseRowsRetrievedException $e) {
        $this->_app->display_error(404, "The desired user (".$_POST['switch_username'].") doesn't exist.");
      }
      $switchUser = $this->_app->user->switchUser($desiredUser->id);
      if ($switchUser) {
        $this->_app->display_response(200, [
          'id' => $this->_app->user->id,
          'username' => $this->_app->user->username,
          'from_id' => $this->_target->id,
          'from_username' => $this->_target->username
        ]);
      } else {
        $this->_app->display_error(500, "An error occurred while switching your user session. Please try again.");
      }
    } else {
      $this->_app->display_error(400, "Please provide a username to switch to.");          
    }
  }
}
?>